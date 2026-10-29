(function(w) {
    $(document).ready(function() {
      populateDropInstances();
      toggleDeliveryInstancesDisplay();
    });

    // Manual tab system between Documents / Debug
    $('#btn-toggle-documents').on('click', function(event) {
        event.preventDefault();
        $('#documents-container').show();
        $('#debug-container').hide();
        $('#btn-toggle-documents').addClass('font-bold');
        $('#btn-toggle-debug').removeClass('font-bold');
    });

    $('#btn-toggle-debug').on('click', function(event) {
        event.preventDefault();
        $('#documents-container').hide();
        $('#debug-container').show();
        $('#btn-toggle-debug').addClass('font-bold');
        $('#btn-toggle-documents').removeClass('font-bold');
    });

    // Mail date to human readable format
    $('#mail-date-text-value').text(function(i, basicDate) {
        if (!basicDate.trim() || basicDate == '--') {
        return '--';
        }
        return moment(basicDate, 'YYYY-MM-DD').format('MMM D, YYYY');
    });

    // Estimate updated at values to human readable format
    $(".timeago").timeago();

    $('#files-required-by').text(function(i, sqlDate) {
        sqlDate = sqlDate.trim();
        if (!sqlDate || sqlDate == '—') {
            var msg = sqlDate;
            $(this).css('color', '#999');
            return msg || 'No drops created';
        }
        else {
            $(this).css('color', '#c32a07');
            return moment(sqlDate).format('MMM D, YYYY - h:mma');
        }
    });

    $('#btn-toggle-as-print-order').on('click', function(event) {
      var $toggleBtn = $(this);
      toggleMarkAsPrintOrder(event, $toggleBtn);
    });

    function toggleMarkAsPrintOrder(event, $toggleBtn) {
    $toggleBtn.toggleClass('btn-default');
    $toggleBtn.toggleClass('btn-warning'); // orange

    // orange 'Print Order' header centered
    $('.h-print-order').toggleClass('text-orange-600 text-3xl font-bold text-center hidden');
    $('#taylor-button-nav-bar').toggleClass('nav-as-print-order');

    w.estimate.isPrintOrder = !w.estimate.isPrintOrder;

    if (w.estimate.isPrintOrder) {
      $toggleBtn.text('Unmark as Print Order');
    } else {
      $toggleBtn.text('Mark as Print Order');
    }

    var printOrderValue = w.estimate.isPrintOrder ? 1 : 0;
    persistPrintOrderState(printOrderValue);
  }

  function persistPrintOrderState(printOrderValue) {
    var qs = 'is_print_order=' + printOrderValue;
    $.ajax({
      method: 'POST',
      url: w.estimate.url + '/set_as_print_order?' + qs,
      success: function(ok) {
        console.log(arguments);

        // toggleUserActionLinks();
        // sendPrintOrderEmails();
      },
      error: function() {
        console.error(arguments);
      }
    });
  }

  $('.toggle-specification').on('click', function(event) {
    event.preventDefault();
    var $btn = $(event.target);
    $btn.closest('td').find('.specification-embed-container').toggleClass('hidden');
  });

    $('#btn-add-document, #btn-close-add-document').on('click', function(event) {
        event.preventDefault();
        $('#add-document-menu').toggleClass('active');
    });

    $('#btn-delete-estimate').click(function(event) {
        if (!confirm('Are you absolutely sure you want to delete this estimate?')) {
            return;
        }
    });

    $('#toggle-rush-fee').on('click', function(event) {
        if (w.estimate.isPrintOrder) {
            return confirm('To enable or disable a rush fee, the estimate must be unmarked as a Print Order.');
        }
        event.preventDefault();
        toggleRushFee(event);
    });

    $('#documents-container').on('click', '.cost-text', function(event) {
        $(this).closest('.cost-cell').find('.cost-details').toggle();
    });

    // Production Details popout
    $('.btn-production-details').click(function(event) {
      event.preventDefault();

      var $btn = $(event.target);
      var documentId = $btn.data('document-id'),
          estimateId = $btn.data('estimate-id');

      var url = '/estimate/' + estimateId + '/document/' + documentId + '/production_details';
      var windowName = 'production_details_' + documentId;

      w.taylor1.launchPopout(url, windowName, {});
    });

    $.estimateArchiveFeatherlight('#taylor-button-nav-bar', '#btn-archive-estimate', function(event, $btn) {
        event.preventDefault();

        if ($btn.parent().hasClass('active')) {
            setEstimateArchiveState(taylor.estimateId, false, 'active');
            $btn.parent().removeClass('active');
            $('#archived-estimate-message').hide();
            return false;
        }

        return {
            beforeClose: function(params) {
                var feather = this;

                if (params.action === 'cancel') {
                    return;
                }
                else if (params.action === 'submit') {
                    $btn.parent().addClass('active');
                    $('#archived-estimate-message').show();
                    $('#archived-estimate-status').text(_.upperFirst(params.status));
                    setEstimateArchiveState(taylor.estimateId, true, params.status);
                }
            }
        };
    });

    function setEstimateArchiveState(estimateId, estimateIsArchived, status) {
        var estimateUrl = location.origin + '/estimate/' + estimateId;
        var qs = 'is_archived=' + (estimateIsArchived ? 1 : 0);
        qs += '&status=' + status;

        return $.ajax({
            method: 'POST',
            url: estimateUrl + '/set_archive_state?' + qs,
        done: function() {
            console.log(arguments);
        },
        error: function() {
            console.error(arguments);
        }
        });
    }

    function toggleRushFee(e) {
        // Primary toggle mechanism.
        w.estimate.hasRushFee = !w.estimate.hasRushFee;

        $('#toggle-rush-fee').closest('li').toggleClass('active');
        $('#rush-fee-quote-row').toggle();

        var qs = 'has_rush_fee=' + (w.estimate.hasRushFee ? 1 : 0);

        $.ajax({
            method: 'POST',
            url: w.estimate.url + '/set_rush_fee?' + qs,
            success: function(estimateCosts) {
                console.log(arguments);
                updateAllCosts(estimateCosts);
            },
            error: function() {
                console.error(arguments);
            }
        });
    }

    // @param estimateCosts should be an array of all costs associated with the estimate generated by the Estimate::getAllCosts method.
    function updateAllCosts(estimateCosts) {
      console.log('estimateCosts:', estimateCosts);

      w.estimate.taxes = estimateCosts.taxes;
      w.estimate.subTotal = estimateCosts.sub_total_with_ship;
      w.estimate.total = estimateCosts.total;

      $('#shipping-cost-value').text(numeral(estimateCosts.shipping_cost).format('$0,0.00'));
      $('#cell-print-total-value').text(numeral(estimateCosts.sub_total_with_rush_fee).format('$0,0.00'));
      $('#cell-estimate-subtotal-value').text(numeral(estimateCosts.sub_total_with_ship).format('$0,0.00'));
      $('#cell-tax-value').text(numeral(w.estimate.taxes).format('$0,0.00'));
      $('#cell-price-total').text(numeral(w.estimate.total).format('$0,0.00'));
    }

    // Drop instance expand/collapse
  var shippingDropInstancesPopulated = false;

  function toggleDeliveryInstancesDisplay() {
    $('#drop-instances-row').toggle();
  }

  $('.btn-toggle-drop-instances').on('click', function(event) {
    toggleDeliveryInstancesDisplay();
  });

  function populateDropInstances() {
    _.each(w.taylor.dropInstances, function(dropInstance) {
      var shipto = _.find(taylor.shiptos, { id: dropInstance.shipto_id }) || {};
      var documentsQty = $.parseJSON(dropInstance.documents_qty);
      var numDocuments = _.reduce(_.values(documentsQty), function(memo, currentValue, i) {
          var hasShipQty = (parseInt(currentValue) > 0) ? 1 : 0;
          return memo + hasShipQty;
      }, 0);

      var hasDeliveryDate = !!dropInstance['delivery_date'];
      var dateText = (hasDeliveryDate)
          ? moment(dropInstance['delivery_date']).format('DD/MM/YYYY h:mma') : 'not set';

      var $instanceRow = $('<div class="drop-instance-item clearfix" style="padding-left: 60px;">\
          <div style="float: left; padding: 4px; min-width: 260px;">• <a href="/shipping/estimate/' + taylor.estimate.id + '" style="color: #000; text-decoration: underline;">' + shipto.company + '</a></div>\
          <div style="float: left; padding: 4px;"><strong>Documents:</strong> ' + numDocuments + '</div>\
          <div style="float: left; padding: 4px;">&ndash;</div>\
          <div style="float: left; padding: 4px;"><strong>Drop Weight Total:</strong> ' + numeral(dropInstance.drop_weight_total).format('0.00') + 'lbs</div>\
          <div style="float: left; padding: 4px;">&ndash;</div>\
          <div style="float: left; padding: 4px;"><strong>Delivery Date:</strong> ' + dateText + '</div>\
          <div class="custom-ship-address" style="clear: left; display: none; padding-left: 30px; color: blue; cursor: default;"></div>\
      </div>');

      $instanceRow.appendTo('#drop-instances-row .drops-content-cell');

      if (dropInstance['custom_ship_address']) {
        var customShipAddress;
        try {
          customShipAddress = JSON.parse(dropInstance['custom_ship_address']);
        } catch (e) {
          customShipAddress = {};
        }
        var customShipAddressDisplay = _.join(_.filter([
          customShipAddress['company'], customShipAddress['address1'], customShipAddress['address2'],
          customShipAddress['city'], customShipAddress['provState'], customShipAddress['postalZip'],
          customShipAddress['country']
        ]), ', ');
        $instanceRow.find('.custom-ship-address').text(customShipAddressDisplay).show()
            .on('click', function(event) {
                if (typeof customShipAddress === 'object' && !_.isEmpty(customShipAddress)) {
                  return;
                }
                var customAddressWithBreaks = String(dropInstance['custom_ship_address']).replace(/[\r\n]+/g, '<br/>');
                $instanceRow.find('.custom-ship-address').html(customAddressWithBreaks);
            });
      }
    });

    shippingDropInstancesPopulated = true;
  }
})(window);