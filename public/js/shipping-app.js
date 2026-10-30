(function(w) {
    var taylor = w.taylor;
    var shipping = taylor.shipping = {};
    var $shipAddressList = $('#ship-address-list');
    var $dropsAppTable = $('#drops-app-table');
    var $notifications = $('#app-notifications');

    shipping.lastCreatedDrop = {};
    shipping.updatedDropInstances = [];

    $(document).ready(function() {
      shipping.renderAddressList();
      for(var i = 0; i < taylor.dropInstances.length; i++) {
        var dropInstance = taylor.dropInstances[i];
        createDropInstance(dropInstance['shipto_id'], dropInstance);
      }
      shipping.displayInitErrorsConditional();
    });

    shipping.renderAddressList = function() {
      var isPrintOrder = taylor.estimate.is_print_order;
      var $_tableBody = $('<tbody></tbody>');
      taylor.shiptos.forEach(function(shipto, i) {
        var addressTitle = shipto.addr1 + ', ' + shipto.prov_state + ', ' + shipto.postal_zip;
        $_tableBody.append('<tr>\
            <td><span class="ship-list-indx">' + (i + 1) + '</span></td>\
            <td>' + shipto.company + '</td>\
            <td><span title="' + addressTitle + '" style="cursor: default;">' + (shipto.city || '') + '</span></td>\
            <td><a class="btn-add-drop ' + (isPrintOrder ? 'disabled' : '') + ' cursor-pointer bg-gray-100 hover:bg-gray-200 active:bg-gray-900 text-gray-800 font-medium\
             py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150" \
              style="text-wrap: nowrap;"\
              data-shipto-id="' + shipto.id + '">Add Drop</a>\
            </td>\
        </tr>');
      });
      $shipAddressList.find('tbody').replaceWith($_tableBody);
    }

    // Main storage table
    shipping.documentsQtyAvailable = taylor.documents.map(function(document) {
      return {
        documentId: document.id,
        printQty: document.quantity,
        shipQty: 0 // to be populated
      }
    });

    // easiest way to reload available qty data after any change
    shipping.refreshQuantitiesAll = function() {
      w.taylor.documents.forEach(function(document) {
        shipping.updateDocumentQtyAvailable(document.id);
      });
    }

    shipping.updateDocumentQtyAvailable = function(documentId) {
      var documentQtyAvailable = _.find(shipping.documentsQtyAvailable, {
        documentId: documentId
      });
      // all rows of the current document across all drops
      var $documents = shipping.jqGetRowsForDocument(documentId);
      var shipQtySum = 0;
      // get current sum of all ship qtys for current document
      $documents.each(function() {
        var $document = $(this);
        var dropShipQty = shipping.parseQty($document.find('input.input-drop-qty').val()) || 0;
        shipQtySum += dropShipQty;
      });
      documentQtyAvailable.shipQty = shipQtySum;
      var updatedAvailableQty = documentQtyAvailable.printQty - shipQtySum;
      $documents.each(function() {
        var $document = $(this);
        $document.find('.document-qty-available').text(updatedAvailableQty);
      });
    }

    shipping.getDocumentQtyAvailable = function(documentId) {
      try {
        return _.find(shipping.documentsQtyAvailable, {
          documentId: documentId
        })['qty'];
      } catch (err) {
        return NaN;
      }
    }

    shipping.jqGetRowForDocument = function(documentId, $context) {
    $context = $context || $('#drops-app-table');
    return $context.find('.drop-inline-document-row')
      .filter(function() {
        return $(this).data('document-id') == documentId;
      });
    }

    shipping.jqGetRowsForDocument = function(documentId) {
      return shipping.jqGetRowForDocument(documentId, $('#drops-app-table'));
    }

    var saveDropButtonHtml = (taylor.estimate.is_print_order) ? '' : '<a class="btn-save-drop absolute mt-2 bg-blue-500 hover:bg-blue-600 text-white font-medium py-2\
     px-4 rounded-md border border-gray-600 transition-colors duration-150 cursor-pointer">Save</a>';
    var removeDropButtonHtml = (taylor.estimate.is_print_order) ? '&nbsp;' : '<a class="btn-remove-drop text-blue-800 underline" style="display: block; margin-left: 20px; cursor: pointer;">Remove</a>';

    // Base structure; behavior and data attributes set dynamically after clone
    shipping.$dropRowTemplate = $('<tr class="shipto-destination-row collapsed">\
        <td><div class="drop-n" style="color: #aaa; font-size: 16px; padding-left: 10px;">\
        <!-- val -->\
        </div></td>\
        <td style="width: 120px"><a class="btn-expand-drop-documents bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md\
         border border-gray-300 transition-colors duration-150 cursor-pointer">Expand</a></td>\
        <td class="shipto-company-cell"><!-- val --></td>\
        <td class="shipto-city-cell" style="cursor: default;"><!-- val --></td>\
        <td class="date-cell">\
            <input type="checkbox" class="date-apply-mode-checkbox" style="float: left; margin-top: 4px; margin-right: 5px;" />\
            <span class="shipto-date-span">not set</span>\
            <a class="fa fa-calendar btn-edit-delivery-date" title="Edit Delivery Date"></a>\
        </td>\
        <td style="text-align: center;" class="shipto-document-count-cell"><!-- val --></td>\
        <td style="text-align: center; display: none;" class="shipto-qty-cell"><!-- val --></td>\
        <td style="text-align: center;" class="drop-cost-cell"><!-- val --></td>\
        <td>' + removeDropButtonHtml + '</td>\
    </tr>\
    <tr class="drop-documents-row inactive" style="display: none;">\
        <td>&nbsp;</td>\
        <td class="buttons-control-cell cell-align-top" style="width: 120px;">\
            ' + saveDropButtonHtml + '\
            <div class="msg-errors" style="display: none; color: red; margin-top: 10px; cursor: default; font-size: 12px; font-style: italic; margin-top: 58px"></div>\
            <div class="msg-success" style="display: none; color: green; cursor: default; font-size: 12px; margin-top: 10px; font-style: italic; margin-top: 58px"></div>\
            <div class="msg-unsaved-changes" style="display: none; color: blue; cursor: default; margin-top: 10px; font-style: italic; font-size: 12px; margin-top: 58px"\
             title="You have made changes to this drop that have not been saved yet">Unsaved Changes</div>\
        </td>\
        <td colspan="6">\
            <table class="document-table-inline">\
                <thead>\
                    <th>&nbsp;</th>\
                    <th class="text-left">Document</th>\
                    <th class="text-left">Description</th>\
                    <th class="text-left">Ship Qty</th>\
                    <th>Available</th>\
                    <th>Print Qty</th>\
                    <th style="width: 30px;">&nbsp;</th>\
                </thead>\
                <tbody>\
                <!-- content -->\
                </tbody>\
            </table>\
        </td>\
    </tr>');

    shipping.$documentsInlineTemplate = $('<tr class="drop-inline-document-row">\
            <td><span style="display: block; padding-right: 4px; color: #aaa; font-size: 14px; text-align: center;" class="doc-indx"><!-- val --></span></td>\
            <td>\
                &bull; <span class="document-report-description"><!--val--></span>\
            </td>\
            <td style="max-width: 160px;">\
                <span class="document-user-description"><!--val--></span>\
            </td>\
            <td>\
                <input type="hidden" class="" value="" />\
                <input type="text" class="input-drop-qty" value="0" />\
            </td>\
            <td style="text-align: center;">\
                <span class="document-qty-available"></span>\
            </td>\
            <td style="text-align: center;">\
                <span class="document-qty-total"></span>\
            </td>\
            <td>&nbsp;</td>\
        </tr>\
    </tbody>');

    // Delivery Destination row (i.e. Company/City/Delivery Date)
    function createDropRow(shiptoId, dropInstance) {
      var $dropRow = shipping.$dropRowTemplate.clone();
      var shiptoDropDoc = _.find(taylor.shiptos, { id: shiptoId });
      if (!shiptoDropDoc) {
        displayNotice('Attempted to create delivery row for an unknown shipping location (' + shiptoId + ') <a class="btn btn-primary btn-reassign-shipto" data-drop-instance-id="' + dropInstance.id + '">Reassign</a>');
        return;
      }
      var indx = $('#drops-tbody').children('tr.shipto-destination-row').length + 1;
      $dropRow.data('shipto-id', shiptoDropDoc.id);
      $dropRow.data('hex-id', Math.random().toString(16).substr(3));
      $dropRow.find('.drop-n').text(indx);
      $dropRow.find('.shipto-company-cell').text(shiptoDropDoc.company);
      if (shiptoDropDoc['uses_custom_addresses']) {
        $dropRow.find('.shipto-city-cell').html('<a class="btn-set-custom-address text-blue-800 underline" style="cursor: pointer;">Set Address</a>');
      }
      else {
        $dropRow.find('.shipto-city-cell').text(shiptoDropDoc.city).attr('title', shiptoDropDoc['addr1'] + ', ' + shiptoDropDoc['prov_state'] + ', ' + shiptoDropDoc['postal_zip']);
      }
      $dropRow.find('.shipto-document-count-cell').text('0');
      $dropRow.find('.shipto-qty-cell').text(numeral(0).format('0,0'));
      var $deliveryDateBtn = $dropRow.find('.btn-edit-delivery-date');
      shipping.bindDatePicker($dropRow, $deliveryDateBtn);
      return $dropRow;
    };

    shipping.populateDropRowWithDocuments = function($dropRow) {
      for(var i = 0; i < taylor.documents.length; i++) {
        var document = taylor.documents[i];
        // row element
        var $document = shipping.$documentsInlineTemplate.clone();

        $document.data({
          'document-id': document.id
        });

        $document.find('.doc-indx').text(i + 1);
        $document.find('.document-report-description').text(document.name).attr('title', document.report_description);
        $document.find('.document-user-description').text(document.description);
        $document.find('.document-qty-available').text(
            shipping.getDocumentQtyAvailable(document.id) || '0'
        );
        $document.find('.document-qty-total').text(document.quantity);

        $dropRow.find('.document-table-inline tbody').append($document);

        (function(document, $document, $dropRow) {
          $document.find('.input-drop-qty')
              .data('document-id', document.id)
              .addClass('document-id-' + document.id)
              .on('keyup', function(event) {
                  shipping.onDropQtyChange(event, document, $document, $dropRow);
              })
        })(document, $document, $dropRow);
      }
    };

    // Context is the qty text field element
    shipping.onDropQtyChange = function(event, document, $document, $dropRow) {
      var newShipQty = parseInt($(event.target).val());
      if (_.isNaN(newShipQty) || newShipQty < 0) {
        return;
      }
      shipping.updateDocumentQtyAvailable(document.id);
      // Updates '# Documents' header cell value
      shipping.updateDropHeaderFields($dropRow);
      // Clear success message when any change occurs
      $document.closest('tr').find('.msg-success').text('');

      // A change occurred by the user; assume there are unsaved changes
      if ($document.is(':visible')) {
        $document.closest('tr').find('.msg-unsaved-changes').show();

        //if (!_.isFunction($._data(window).events.beforeunload)) {
          jQuery(window).on('beforeunload.unsavedChanges', function() {
            if (!confirm('There are unsaved changes. Are you sure you want to leave this page?')) {
              return false;
            }
          });
        //}
      }
    }

    shipping.parseQty = function(userQtyVal) {
      if (!userQtyVal) return 0;
      else return _.isNumber(userQtyVal) ? userQtyVal : parseInt(userQtyVal.replace(/[^0-9]+/g, ''));
    }

    function getDropShipCostTotal(dropInstance) {
      dropInstance = dropInstance || { drop_cost: 0 };
      // For newly added shipping drop instances (before user data entry)
      // Shipping information (including zone used for calculation variables) available after instance has been saved
      if (!dropInstance['shipto_id']) {
        return 0;
      }
      dropInstance.drop_cost = parseFloat(dropInstance.drop_cost);
      var handlingCost = 0;
      var storedHandlingCost = parseFloat(dropInstance.drop_handling_cost);
      if (storedHandlingCost > 0) {
        handlingCost = storedHandlingCost;
      } else {
        handlingCost = (dropInstance.drop_cost * getZoneHandlingFactorDynamic(dropInstance));
      }
      return dropInstance.drop_cost + handlingCost;
    }

    function getZoneHandlingFactorDynamic(dropInstance) {
      try {
        var shipto = _.find(w.taylor.shiptos, { id: dropInstance['shipto_id'] });
        var zone = _.find(w.taylor.zones, { zone: shipto['zone'] });
        var handlingFactor = parseFloat(zone.handling);

        if (_.isNaN(handlingFactor)) {
          throw new Error("Delivery cost calculation failed for drop #" + dropInstance.id + ". Please notify Taylor-Demers.");
        }

        return handlingFactor;
      } catch (err) {
        console.error('getZoneHandlingFactorDynamic', err);
        console.log('Drop Instance:', dropInstance);
        displayErrorMessage(err.message, { blockInteraction: true });
      }
    }

    function displayErrorMessage(message, params) {
      displayNotice(message, _.extend(params, { textLabel: 'Error' }));
    }

    function displayNotice(message, params) {
      params = _.extend({
        blockInteraction: false,
        textLabel: 'Notice'
      }, params);

      var bgColor = (params.textLabel === 'Notice') ? '#e8e0d4' : 'inherit';
      var border = '1px solid hsla(0, 0%, 0%, .25)';

      $notifications.append('<div class="estimate-alerts-message ship-notice" \
        style="text-align: left; font-size: 18px; border-radius: 0; background-color: ' + bgColor + '; border: ' + border + '; display: relative;">\
        <strong>' + params.textLabel + ':</strong> \
        ' + message + '\
      </div>');

      if (params.blockInteraction) {
        $dropsAppTable.find('.btn-expand-drop-documents').addClass('disabled');
        $shipAddressList.find('.btn-add-drop').addClass('disabled');
      }
    }

    shipping.populateDropRowData = function($dropRow, dropInstance) {
      // This is the case when adding a new drop
      $dropRow.find('.drop-cost-cell').text(function() {
        var dropCostTotal = getDropShipCostTotal(dropInstance);
        return (dropCostTotal === 0) ? '–' : '$' + numeral(dropCostTotal).format('0,0.00');
      });

      if (_.isEmpty(dropInstance) || _.isNaN(dropInstance.drop_cost)) {
        // Update 'Available' quantities
        $dropRow.find('.input-drop-qty').trigger('keyup');
        return;
      }

      var dropDocumentsQty = $.parseJSON(dropInstance['documents_qty']);
      var hasDeliveryDate = !!dropInstance['delivery_date'];
      var dateText = (hasDeliveryDate)
          ? moment(dropInstance['delivery_date']).format('DD/MM/YYYY h:mma') : null;

      $dropRow.find('.shipto-date-span').text(dateText || 'not set');

      // Where each key is a documentId
      Object.keys(dropDocumentsQty).forEach(function(documentId) {
        var documentShipQty = dropDocumentsQty[documentId];
        // Set the value of the 'Ship Qty' field
        var $documentRow = shipping.jqGetRowForDocument(documentId, $dropRow);
        $documentRow.find('.input-drop-qty')
            .val(documentShipQty)
            .trigger('keyup'); // much easier way to update the 'Available' and 'Printy Qty' values as opposed to traversing dom and calling functions.
      });
    }

    // Updates the '# Documents' value in the Destination row cell
    shipping.updateDropHeaderFields = function($dropRow) {
      var $dropDocuments = $dropRow.find('.drop-inline-document-row');

      var numDocumentsWithValues = 0;
      var shipQtySum = 0;

      $dropDocuments.each(function(i, elem) {
        var $document = $(this);
        var shipQty = parseInt($document.find('.input-drop-qty').val());

        if (!_.isNaN(shipQty) && shipQty > 0) {
          shipQtySum += shipQty;
          numDocumentsWithValues++;
        }
      });

      $dropRow.find('.shipto-document-count-cell').text(numDocumentsWithValues);
    };

    shipping.collapseAllDropRows = function() {
      $('.btn-expand-drop-documents').each(function(i, elem) {
        var $btn = $(this);
        if ($btn.text() === 'Collapse') {
          $btn.trigger('click');
        }
      });
    };

    function createDropInstance(shiptoId, dropInstance) {
      $('#empty-drops-block-row').hide(); // always hide empty block spacer message
      dropInstance = dropInstance || {};
      // This is named singularly, however it contains both the shipto/destination row as well as the documents container row. 'data' assignment below, which is necessary to perform updates instead of creations, applied drop-instance-id to both rows (which is fine.)
      var $dropRow = createDropRow(shiptoId, dropInstance);
      if (!$dropRow || $dropRow.length === 0) {
        console.debug('createDropInstance $dropRow not created for shiptoId ' + shiptoId + ' (' + typeof shiptoId + '). Skipping.');
        return;
      }
      if (dropInstance['id']) {
        $dropRow.data('drop-instance-id', dropInstance.id);
        $dropRow.find('tr.drop-documents-row')
                .data('drop-instance-id', dropInstance.id);
        $dropRow.find('.btn-edit-delivery-date') // used for date value
                .data('drop-instance-id', dropInstance.id);

        if (dropInstance['custom_ship_address']) {
          _.delay(function() {
            populateCustomAddress(dropInstance.id, dropInstance['custom_ship_address']);
          }, 500);
        }
      }

      $dropRow.appendTo('#drops-tbody');
      shipping.populateDropRowWithDocuments($dropRow);
      shipping.populateDropRowData($dropRow, dropInstance);
      return $dropRow;
    }

    // `Add Drop` buttons / Delivery Locations
    $shipAddressList.on('click', '.btn-add-drop', function(event) {
      var shiptoId = parseInt($(this).data('shipto-id'));
      console.log('Creating drop instance with ship ID: ' + shiptoId);
      var $dropRow = createDropInstance(shiptoId);
      //shipping.collapseAllDropRows();
      shipping.refreshQuantitiesAll();
      shipping.showRowDocuments($dropRow);
      $dropRow.find('.input-drop-qty:first').focus();
    });

    // Save drop
    $('.shipping-drops-table').on('click', '.btn-save-drop', function(event) {
      var $dropDocumentsRow = $(this).closest('tr.drop-documents-row');
      var $dropShiptoRow = $dropDocumentsRow.prev('tr.shipto-destination-row');
      var _dropInstanceId = $dropDocumentsRow.data('drop-instance-id') || null;
      var dropQtyData = {};
      var estimateId = taylor.estimateId;
      // create a hash table { documentId: dropQty }
      $dropDocumentsRow.find('.input-drop-qty').each(function(i, elem) {
        var $dropQty = $(this);
        var documentId = $dropQty.closest('tr').data('document-id');
        dropQtyData[documentId] = shipping.parseQty($dropQty.val());
      });
      // a copy of the dropInstance before changes occurred
      var originalDropInstance = _.cloneDeep(_.find(taylor.dropInstances, { id: _dropInstanceId }));
      $.ajax({
          method: 'post',
          url: location.origin + '/shipping/estimate/' + estimateId + '/processDropInstance',
          data: withLaravelCSRF({
            estimateId: estimateId,
            dropInstanceId: _dropInstanceId,
            shiptoId: $dropShiptoRow.data('shipto-id'),
            dropData: dropQtyData
          }),
          success: function(dropInstance) {
            console.debug(dropInstance);

            taylor.dropInstances = taylor.dropInstances.concat(taylor.dropInstances, [dropInstance]);

            // Whether or not any modifications were made to the given drop (note that this save procedure can often occur on drops that have not been modified)
            var changesDidOccur = $.inverse(_.isEqual(dropInstance, originalDropInstance));

            // Determine the date for the currently saved drop as it exists in the DOM. This is used to persist the date if it was set before the drop was created in the database. Otherwise these variables are not used.
            var deliveryDateText = $dropShiptoRow.find('.shipto-date-span').text();
            var hasDeliveryDateText = (deliveryDateText && deliveryDateText != 'not set');

            if (_.isEmpty(dropInstance.delivery_date) && hasDeliveryDateText) {
              var deliveryDateUnix = moment(deliveryDateText, 'DD/MM/YYYY h:mma').format('X');
              shipping.persistDeliveryDate($dropDocumentsRow, estimateId, deliveryDateUnix, {
                dropInstanceId: dropInstance.id
              });
            }

            try {
              $dropDocumentsRow.data('drop-instance-id', dropInstance.id);
              // shipto row; redundent but jquery apps are a mess
              $dropDocumentsRow.prev('tr')
                               .data('drop-instance-id', dropInstance.id)
                               .find('.btn-edit-delivery-date').data('drop-instance-id', dropInstance.id);

              // a `_dropInstance` value will not exist before the $.ajax occurs for newly created drop instances
              if (!_dropInstanceId) {
                shipping.lastCreatedDrop = dropInstance;
              }
            }
            catch(err) {
              console.error(err);
              $dropDocumentsRow.find('.msg-errors')
                  .text('An error occurred while saving the delivery values. Please contact the technical staff.').show();
              return;
            }

            // Update drop cost value in the drop header row
            var dropCostTotal = getDropShipCostTotal(dropInstance);
            $dropShiptoRow.find('.drop-cost-cell').text(
              (dropCostTotal === 0) ? '–' : '$' + numeral(dropCostTotal).format('0,0.00')
            );
            $dropDocumentsRow.find('.msg-success').text('Drop Saved').show();
            $dropDocumentsRow.find('.msg-unsaved-changes').hide();
            jQuery(window).off('beforeunload.unsavedChanges');
            if (changesDidOccur) {
              shipping.updatedDropInstances.push(dropInstance);
              shipping.updateEstimateShipState(dropInstance);
            }
          },
          error: function(response) {
            console.error(arguments);
            $dropDocumentsRow.find('.msg-errors')
                .text('An error occurred while saving the delivery values. Please contact the technical staff.').show();
          }
      });
    });

    $('.shipping-drops-table').on('click', '.btn-expand-drop-documents', function(event) {
      var $btnExpand = $(this);
      $btnExpand.text(function(i, text) {
        return (text === 'Expand') ? 'Collapse' : 'Expand';
      });
      $btnExpand.closest('tr.shipto-destination-row')
          .toggleClass('collapsed expanded')
          .next('tr.drop-documents-row')
          .toggleClass('inactive active')
          .toggle();
    });

    // Remove drop
    $('.shipping-drops-table').on('click', '.btn-remove-drop', function(event) {
      var $removeDropBtn = $(this);
      var $shiptoRow = $removeDropBtn.closest('tr.shipto-destination-row');
      var $dropDocumentsRow = $shiptoRow.next('tr.drop-documents-row');
      var shiptoCompanyName = $shiptoRow.find('.shipto-company-cell').text();
      if (!confirm('Confirm drop deletion (' + shiptoCompanyName + ')?')) {
        return;
      }
      var dropInstanceId = $dropDocumentsRow.data('drop-instance-id');
      var shiptoId = $dropDocumentsRow.data('shipto-id');
      $shiptoRow.remove();
      $dropDocumentsRow.remove();
      shipping.refreshQuantitiesAll();
      var estimateId = w.taylor.estimateId;
      $.ajax({
        method: 'get',
        url: location.origin + '/shipping/estimate/' + estimateId + '/deleteDropInstance',
        data: {
          estimateId: estimateId,
          dropInstanceId: dropInstanceId,
          shiptoId: shiptoId
        },
        success: function() {
          console.debug('deleteDropInstance success:', arguments);
        },
        error: function() {
          console.error('deleteDropInstance err:', arguments);
        }
      });
  });

    shipping.saveAllDropInstances = function() {
      $dropsAppTable.find('.btn-save-drop:visible').each(function(i, btnSaveDropElem) {
        $(btnSaveDropElem).trigger('click');
      });
    }

    // Save all convenience button
    $('#btn-save-all-drops').on('click', shipping.saveAllDropInstances);

    // @todo ISO is preferred here
    shipping.persistDeliveryDate = function($dropRow, estimateId, deliveryDateUnix, params) {
      params = params || {};
      var dropInstanceId;
      // dropinstanceid can be explicitly set, or inferred from dom context data
      if (params.dropInstanceId) {
        dropInstanceId = params.dropInstanceId;
      } else {
        dropInstanceId = $dropRow.data('drop-instance-id');
      }
      $.ajax({
        method: 'get',
        url: location.origin + '/shipping/estimate/' + estimateId + '/setDropInstanceDeliveryDate',
        data: {
          estimateId: estimateId,
          dropInstanceId: dropInstanceId,
          shiptoId: $dropRow.data('shipto-id'),
          deliveryDate: deliveryDateUnix
        },
        success: function() {
          console.debug('SUCCESS setDropInstanceDeliveryDate', arguments);
        },
        error: function() {
          console.error('shipping.bindDatePicker::xhrSaveDeliveryDate err:', arguments);
        }
      });
    };

    shipping.dateApplyModeActive = false;
    shipping.toggleDateApplyMode = function($shippingRow) {
      shipping.dateApplyModeActive = !shipping.dateApplyModeActive;

      $shippingRow.toggleClass('date-apply-mode-active');

      if (shipping.dateApplyModeActive) {
        $shippingRow.find('input.date-apply-mode-checkbox').prop('checked', true);

        $('tr.shipto-destination-row').each(function(i, shipRowElem) {
            shipRowElem.classList.add('date-apply-mode-active');
        });
      }
      else {
        // deactivate all
        $('tr.shipto-destination-row').each(function(i, shipRowElem) {
          shipRowElem.classList.remove('date-apply-mode-active');
          shipRowElem.querySelector('input.date-apply-mode-checkbox').checked = false;
        });
      }
    };

    shipping.updateEstimateShipState = function(_dropInstance) {
      var dropInstance = _dropInstance || {};
      var estimateId = window.taylor.estimateId;
      return $.ajax({
        method: 'get',
        url: location.origin + '/shipping/estimate/' + estimateId + '/updateEstimateShipState',
        data: {
          usesCustomAddresses: $.number($.bool(_.size(shipping.dropInstancesWithCustomAddresses()))),
          customAddressWasChanged: $.number(shipping.dropUsesCustomAddress(dropInstance))
        },
        success: function(response) {
          console.debug('RESPONSE:', response);
        },
        error: function(response) {
          console.error('RESPONSE ERR:', response);
        }
      });
    }

    shipping.dropInstancesWithCustomAddresses = function() {
      return _.filter(taylor.dropInstances, function(dropInstance) {
        return shipping.dropUsesCustomAddress(dropInstance);
      });
    }

    // Determine whether or not a drop uses a shipto that allows for custom addresses. (Could also evaluate `dropInstance.custom_ship_address` to determine whether or not a drop has a custom address, but this system is fine.)
    shipping.dropUsesCustomAddress = function(dropInstance) {
      if (_.isEmpty(dropInstance)) { return false }
      // List of all shiptos rows using custom addresses in the system
      var customShiptoIds = _.filter(taylor.shiptos, function(shipto) {
        return $.bool(shipto['uses_custom_addresses']);
      });
      return $.bool(_.find(customShiptoIds, { id: dropInstance['shipto_id'] }));
    }

    shipping.bindDatePicker = function($dropRow, $deliveryDateBtn) {
      var estimateId = taylor.estimateId;

      $deliveryDateBtn.on('click', function(event) {
        $deliveryDateBtn.tooltipster('instance').open();
      });

      $deliveryDateBtn.tooltipster({
          trigger: 'custom',
          content: (function() {
            var $dateTip = $('<div style="position: relative;">\
            <input type="text" class="delivery-date" style="width: 160px; padding: 7px;" placeholder="Delivery Date" />\
             <a class="btn btn-default btn-save-delivery-date">Save</a>\
             <a class="btn btn-default btn-apply-delivery-dates">Apply To...</a>\
            </div>');
            return $dateTip;
          }()),
          theme: 'tooltipster-noir taylor-date-tipster',
          interactive: true,
          delay: [0, 0],
          animationDuration: 0,
          functionAfter: function(tipster, elems) {
            var $shippingRow = $(elems.origin).closest('tr');
            $shippingRow.removeClass('date-picker-open');
            $(window).off('click.closeTip');
            if (shipping.dateApplyModeActive) {
              shipping.toggleDateApplyMode($shippingRow);
            }
          },
          // when the date btn is clicked
          functionReady: function(tipster, elems) {
            var $dateBtn = $(elems.origin);
            var $tooltip = $(elems.tooltip);

            var $deliveryDate = $tooltip.find('.delivery-date'); // field
            var $shippingRow = $dateBtn.closest('tr');

            var dropInstanceId = $dateBtn.closest('tr').data('drop-instance-id');
            var dropInstance = _.find(taylor.dropInstances, { id: dropInstanceId });
            var shipto;
            if (dropInstance && dropInstance['shipto_id']) {
              shipto = _.find(w.taylor.shiptos, { id: dropInstance['shipto_id'] });
            } else {
              alert('Save your shipping instance before setting a delivery date.');
              $deliveryDateBtn.tooltipster('instance').close();
              return;
            }
            var dropSetDeliveryDate;

            if (dropInstance && dropInstance.delivery_date) {
              dropSetDeliveryDate = moment(dropInstance.delivery_date);
            }

            $shippingRow.addClass('date-picker-open');
            var defaultDeliveryDate;

            if (/[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}/.test(taylor.estimate['client_reference_mail_date'])) {
              defaultDeliveryDate = moment(taylor.estimate['client_reference_mail_date'], "YYYY-MM-DD");
            }
            else {
              defaultDeliveryDate = moment(taylor.estimate['client_reference_mail_date'], "MMM D, YYYY");
            }
            var defaultDeliveryDate = defaultDeliveryDate.subtract(7, 'days').hours(17).minutes(0).seconds(0);

            $deliveryDate.datetimepicker({
              format: 'DD/MM/YYYY h:mma',
              useCurrent: false,
              defaultDate: dropSetDeliveryDate || defaultDeliveryDate,
              //disabledDates: getStatHolidayDates(),
              debug: true,
              viewDate: dropSetDeliveryDate || moment().hours(17).minutes(0).seconds(0),
              //minDate: minDate
            })
            .on('dp.show', function(event) {
              $calendarHighlightStatHolidays(shipto);
            })
            .on('dp.change', function(event) {
              // autosave after clicking a date on the datetimepicker
              //$('.btn-save-delivery-date:visible').trigger('click');
            })
            .on('dp.update', function(event) {
              $calendarHighlightStatHolidays(shipto);
            });

            // tooltip 'Save' button
            $tooltip.on('click', '.btn-save-delivery-date', function(event) {
              var deliveryDate = $deliveryDate.val();
              // @todo ISO is preferred here
              var deliveryDateUnix = moment(deliveryDate, 'DD/MM/YYYY h:mma').format('X');
              var updateRows = [$dropRow];
              // 'Apply to...' checkboxes
              $('input.date-apply-mode-checkbox:checked').each(function(i, checkbox) {
                var $_dropRow = $(checkbox).closest('tr');
                updateRows.push($_dropRow);
              });

              _.each(updateRows, function($_dropRow) {
                shipping.persistDeliveryDate($_dropRow, taylor.estimateId, deliveryDateUnix);
                $_dropRow.find('.shipto-date-span').text(deliveryDate);
              });
              $deliveryDateBtn.tooltipster('hide');
            });

            $tooltip.on('click', '.btn-apply-delivery-dates', function(event) {
              shipping.toggleDateApplyMode($shippingRow);
            });
            $deliveryDate.data("DateTimePicker").show();

            $(window).on('click.closeTip', function(event) {
              var $clickTarget = $(event.target);
              if ($clickTarget.closest('.tooltipster-box').length || $clickTarget.closest('.date-cell').length) {
                return;
              }
              $deliveryDateBtn.tooltipster('instance').close();
            });
          }
      });
    }

    function $calendarHighlightStatHolidays(shipto) {
      var isUSA;
      if (shipto && _.isString(shipto.zone)) {
        isUSA = /^USA/.test(shipto.zone);
      }
      var statHolidaysCalendarFormat = _.map(getStatHolidayDates(isUSA), function(statMoment) {
        return statMoment.format('MM/DD/YYYY');
      });

      $('.bootstrap-datetimepicker-widget').find('.day').each(function(i, dayElem) {
        var dayElemDate = dayElem.getAttribute('data-day');
        if (~statHolidaysCalendarFormat.indexOf(dayElemDate)) {
            dayElem.classList.add('stat-holiday');
        }
      });
    }

    function getStatHolidayDates(isUSA) {
      return _.compact(_.map(w.taylor.statHolidaysDb, function(customDateObj) {
        if (isUSA === true && customDateObj['observance_can'] == 1) {
          return;
        }
        if (isUSA === false && customDateObj['observance_usa'] == 1) {
          return;
        }
        return moment(customDateObj['date_string'], 'D-MMM-YYYY');
      }));
    }

    // Prevent user from unchecking 'Apply To' date change for currently active row.
    $dropsAppTable.on('click', '.date-apply-mode-checkbox[type="checkbox"]', function(event) {
      var $checkbox = $(event.target);
      if ($checkbox.closest('.shipto-destination-row').hasClass('date-picker-open')) {
        return event.preventDefault();
      }
    });

    $dropsAppTable.on('click', '.btn-set-custom-address', function(event) {
      var isNewDropInstance = false;
      var saveDelay = 0;
      var $dropDocumentsRow = $(getShippingRow(event));
      var $dropShiptoRow = $dropDocumentsRow.next('tr.drop-documents-row');
      var shiptoId = $dropShiptoRow.data('shipto-id');
      var dropInstanceId = $dropDocumentsRow.data('drop-instance-id');
      var dropHexId = $dropDocumentsRow.data('hex-id');
      var existingDropInstance = _.find(taylor.dropInstances, { id: dropInstanceId }) || {};

      // The Drop Instance record must exist for this function to work
      if (!dropInstanceId || dropInstanceId == 0) {
        console.debug('Drop instance record doesn\'t yet exist for item. Attempting creation...');
        shipping.saveAllDropInstances();
        isNewDropInstance = true;
        saveDelay = 800;
      }

      var customAddress = new taylor.CustomAddress(existingDropInstance['custom_ship_address']);
      var $addressOverlay = $(['<div style="width: 640px;">',
        '<h3 style="background: #202a2f; color: #eee; margin-left: -25px; width: calc(100% + 50px); margin-top: -25px; padding: 16px 25px; font-weight: bold; font-size: 1.3rem;">Set Custom Address</h3>',
        '<div class="custom-address-container" style="margin: 0 -12px;"></div>',
        '<hr style="border-color: #93b3c3; clear: both; margin-top: 12px; padding-top: 12px;" />',
        '<div style="margin-top: 10px;"><a class="btn-save-address bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md\
         border border-gray-600 transition-colors duration-150 no-underline cursor-pointer">Save</a>',
        '<a style="float: right;" class="btn-cancel bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4\
         rounded-md border border-gray-300 transition-colors duration-150 cursor-pointer">Cancel</a></div>',
      '</div>'].join(''));
      $addressOverlay.find('.custom-address-container').append(customAddress.$form);

      var addrFeatherlight = $.featherlight($addressOverlay, {
          afterOpen: function() {
            var featherlight = this;
            featherlight.$instance.on('click', '.btn-cancel', function(event) {
              event.preventDefault();
              addrFeatherlight.close();
            })
            .on('click', '.btn-save-address', function(event) {
                var $addressForm = featherlight.$instance.find('form.custom-address');
                // if ($.isFalsy(address)) { return }
                _.delay(function() {
                  if (isNewDropInstance) {
                    dropInstanceId = $(getShippingRow(dropHexId)).data('drop-instance-id');
                  }
                  var formData = {
                    attention: $addressForm.find('[name="attention"]').val(),
                    country: $addressForm.find('[name="country"]').val(),
                    company: $addressForm.find('[name="company"]').val(),
                    address1: $addressForm.find('[name="address1"]').val(),
                    address2: $addressForm.find('[name="address2"]').val(),
                    city: $addressForm.find('[name="city"]').val(),
                    provState: $addressForm.find('[name="provState"]').val(),
                    postalZip: $addressForm.find('[name="postalZip"]').val()
                  };
                  $.ajax({
                    method: 'get',
                    url: location.origin + '/shipping/estimate/' + taylor.estimateId + '/setCustomDropAddress',
                    data: {
                      estimateId: taylor.estimateId,
                      dropInstanceId: dropInstanceId,
                      shiptoId: $dropShiptoRow.data('shipto-id'),
                      address: JSON.stringify(formData)
                    },
                    success: function(dropInstance) {
                      var savedAdderess = dropInstance['custom_ship_address'];
                      populateCustomAddress(dropInstance.id, savedAdderess);
                      taylor.dropInstances = _.reject(taylor.dropInstances, function(instance) {
                        return Boolean(instance.id === dropInstance.id);
                      });
                      taylor.dropInstances.push(dropInstance);
                      console.debug(dropInstance);
                    },
                    error: function(response) {
                      console.error(arguments);
                    }
                  });
                }, saveDelay);
                _.delay(function() {
                  addrFeatherlight.close();
                }, 100);
            });
          }
      });
    });

    function populateCustomAddress(dropInstanceId, customAddress) {
      try {
        customAddress = JSON.parse(customAddress);
      } catch (e) {}
      var address1 = (typeof customAddress === 'string') ? customAddress : customAddress.address1;
      var incompleteLabel = '(incomplete)';
      address1 = address1 || incompleteLabel;
      var $dropInstanceRow = $dropsAppTable.find('tr.shipto-destination-row').dataFilter('drop-instance-id=' + dropInstanceId);
      var addressShortDisplay = _.slice(address1, 0, 24).join('');
      var $shipCell = $dropInstanceRow.find('.shipto-city-cell');
      var ellipses = (addressShortDisplay != address1) ? '…' : '';
      var textColor = (address1 === incompleteLabel) ? 'color: hsl(143, 2%, 51%)' : 'color: hsl(143, 34%, 51%)';
      $shipCell.empty().append(_.join(['<span title="' + address1 + '" style="' + textColor + '; cursor: pointer;"',
       'class="custom-address-value btn-set-custom-address">',
          addressShortDisplay + ellipses,
       '</span>'], ''));
    }

    shipping.showRowDocuments = function(rowRef) {
      var $row = (!_.isObject(rowRef)) ? $(getShippingRow(rowRef)) : rowRef;
      $row = $row.is('.shipto-destination-row') ? $row : $row.closest('.shipto-destination-row');
      if ($row.length === 0) {
        return console.debug('[shipping-app] `showRowDocuments` No row found for supplied ref:', rowRef);
      }
      if ($row.is('.expanded')) {
        return;
      }
      $row.find('.btn-expand-drop-documents').trigger('click');
    }

    function getShippingRow(dropRefId) {
      if (dropRefId instanceof Event || dropRefId.currentTarget) {
        return _.first($(dropRefId.currentTarget).closest('.shipto-destination-row').toArray());
      }
      var refField = (_.isNumber(dropRefId)) ? 'dropInstanceId' : 'hexId';
      return _.first($dropsAppTable.find('tbody#drops-tbody > tr.shipto-destination-row').toArray().filter(function(row) {
        return $(row).data(refField) === dropRefId;
      }));
    }

    $('section.drop-page-content').on('click', _.once(function(event) {
      var filesReqAdjustment = $.string(window.taylor.estimate['files_require_adjustment']);
      var hasFilesRequireAdjustment = $.notEmpty(filesReqAdjustment) && $.bool(!/^0000/.test(filesReqAdjustment));
      if (hasFilesRequireAdjustment) {
        alert('The file required date for this estimate has been set manually. Any change to shipping will recalculate the file required date.');
      }
    }));

    $notifications.on('click', '.btn-reassign-shipto', function(event) {
      var $btn = $(event.currentTarget);
      var dropInstanceId = $btn.data('drop-instance-id');
      var dropInstance = _.find(taylor.dropInstances, { id: dropInstanceId });

      var $shipNotice = $btn.closest('.ship-notice');
      var $reassign = $('<div style="background: #f8f4ee; font-size: 15px; padding: 12px;">\
        <div style="margin-bottom: 12px;"><strong>Original Assignment:</strong> <span class="from-shipto-display">--</span></div>\
        <div style="margin-bottom: 16px;"><strong>Reassign To:</strong> &nbsp;<span class="reassign-select-container"></span></div>\
        <input class="btn btn-primary btn-save-reassignment" type="submit" value="Save" />\
      </div>');

      $reassign.find('.reassign-select-container').append('<select name="shipto_reassign_id">' +
        taylor.shiptos.reduce(function(acc, shipto, i) {
          var city
          return acc + '<option value="' + shipto.id + '">' + (i + 1) + '. ' + shipto.company + '</option>'
        }, '')
      + '</select>');

      var originShiptoURI = location.origin + '/shipping/shipto/' + dropInstance.shipto_id;
      var reassignShiptoURI = location.origin + '/shipping/estimate/' + taylor.estimate.id + '/reassignDropInstance';

      $.getJSON(originShiptoURI).then(function(response) {
        $btn.addClass('disabled');
        $reassign.find('.from-shipto-display').text(response.company + ' (' + response.addr1 + ', ' + response.city + ', ' + response.prov_state + ')');
        $shipNotice.append('<hr style="border-color: #222; margin-bottom: 0;" />');
        $shipNotice.append($reassign);
      });

      $shipNotice.on('click', '.btn-save-reassignment', function(event) {
        var shiptoReassignId = parseInt($shipNotice.find('select[name="shipto_reassign_id"]').val());

        $.ajax({
          method: 'POST',
          url: reassignShiptoURI,
          data: withLaravelCSRF({
            drop_instance_id: dropInstanceId,
            drop_instance_token: dropInstance.token,
            shipto_reassign_id: shiptoReassignId
          }),
          success: function(response) {
            console.log('$.ajax success response:', response);

            if (response && response.shipto_id == shiptoReassignId) {
              var updatedDropInstance = response;
              createDropInstance(shiptoReassignId, updatedDropInstance);
              $shipNotice.remove();
            }
          },
          error: function(err) {
            console.error('$.ajax err response:', err);
          }
        });
      });
    });

    shipping.displayInitErrorsConditional = function() {
      if (taylor.initNotification.message) {
        displayNotice(taylor.initNotification.message, {
          blockInteraction: Boolean(taylor.initNotification.blockInteraction)
        });
      }
    }

    function withLaravelCSRF(obj) {
      return Object.assign({}, obj, {
        _token: getLaravelCSRF()
      });
    }

    function getLaravelCSRF() {
      return $('input[name="_token"]').val();
    }
}(window));
