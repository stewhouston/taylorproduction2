(function(w, taylor, $) {
  function submitParameters(data) {
    $.ajax({
      method: 'POST',
      data: data,
      url: '/estimate/' + taylor.estimate.id + '/document/set_custom_parameters',
      success: function(response) {
        console.log('response:', response);
        location.reload();
      },
      error: function(err) {
        console.error('$.ajax err:', err);

        if (err.status === 409) {
          $('.featherlight .error-messages').text(err.responseJSON.error || err.responseText);
        }
      }
    });
  }

  function fieldNames() {
    return [
      'document_id', 'type', 'size', 'finish', 'custom_description_1', 'custom_description_2',
      'weight', 'hour_range', 'sell_total', 'sell_print', 'sell_bind', 'approve',
    ];
  }

  function $fields($lightbox) {
    $lightbox = $lightbox || $('body > .featherlight');

    return fieldNames().map(function(fieldName) {
      return $lightbox.find('[name="' + fieldName + '"]');
    });
  }

  function fieldValues() {
    return $fields().map(function($field) {
      if ($field.is('[type="checkbox"]')) return $field.is(':checked');
      return $field.val();
    });
  }

  $('.btn-set-custom-parameters').on('click', function(event) {
    var $btn = $(event.target);
    var documentId = parseInt($btn.data('document-id'));
    var document = _.find(taylor.documents, { id: documentId });

    var types = ['Saddlestitch', 'Single Sheet', 'Multi Sheet', 'Folder', 'Perfect Bind'];
    var sizes = ['8 1/2 x 11', '5 1/2 x 8 1/2'];
    var finishes = ['No Finishing', 'Saddlestitch', 'Corner Stitch', 'Perfect Bind', 'Fold(1/2)', 'Letter Fold', '4pg Folder', '4pg Folder-1/4 Fold', '4pg Folder-Letter Fold'];
    // var hour_range = ['<= 10', '<= 16', '<= 28', '<= 34', '<= 44', '<= 50', '<= 56', '<= 62', '<= 68', '<= 74', '<= 82', '<= 88', '>= 89'];

    var selectType = '<select name="type"><option></option>' + _.join(types.map(function(type) { return '<option value="' + type + '">' + type + '</option>'; }), '') + '</select>';
    var selectSize = '<select name="size"><option></option>' + _.join(sizes.map(function(size) { return '<option value="' + size + '">' + size + '</option>'; }), '') + '</select>';
    var selectFinish = '<select name="finish"><option></option>' + _.join(finishes.map(function(finish) { return '<option value="' + finish + '">' + finish + '</option>'; }), '') + '</select>';
    // var selecthour_range = '<select name="hour_range"><option></option>' + _.join(hour_range.map(function(hours) { return '<option value="' + hours.replace(/[^0-9]+/, "") + '">' + hours + ' hours</option>'; }), '') + '</select>';

    var LIGHTBOX_HTML = '<form style="width: 640px; min-height: 230px;" id="taylor-lightbox">\
      <h3 style="margin-top: 0; font-weight: bold; margin-left: 6px;">Custom Document Estimate Detail</h3>\
      <input type="hidden" name="document_id" value="' + document.id + '" />\
      <pre class="specification-embed-container visible" style="margin-left: 6px;">' + String(document.specification).replace(/[\r\n]+/g, '<br />') + '</pre>\
      <table class="padded">\
      <tr><td style="font-weight: bold; text-align: right;">Document Name:</td>\
      <td>' + document.name + ' - Custom</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Type:</td>\
      <td>' + selectType + '</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Size:</td>\
      <td>' + selectSize + '</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Finish:</td>\
      <td>' + selectFinish + '</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Component Description (1):</td>\
      <td>' + '<input name="custom_description_1" type="text" style="width: 360px;" />' + '</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Component Description (2):</td>\
      <td>' + '<input name="custom_description_2" type="text" style="width: 360px;" />' + '</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Document Weight:</td>\
      <td>' + '<input class="decimal02" name="weight" type="text" step="0.01" style="width: 80px;" /><span class="ns">' + ' lbs per unit</span></td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Production Hours:</td>\
      <td>' + '<input class="decimal02" name="hour_range" type="text" step="0.01" style="width: 80px;" /><span class="ns"> hours</span>' + '</td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">Document Sell:</td>\
      <td>' + '<div style="display: flex; align-items: center;">\
      <span style="padding-right: 4px;">Total:</span>\
      <input class="decimal02" name="sell_total" type="text" step="0.01" style="width: 80px; margin-right: 4px;" placeholder="Total $" />\
      <span style="padding-right: 4px;">Bind:</span>\
      <input class="decimal02" name="sell_bind" type="text" step="0.01" style="width: 80px; margin-right: 4px;" placeholder="Bind $" />\
      <span style="padding-right: 4px;">Print:</span>\
      <input class="decimal02" name="sell_print" type="text" step="0.01" style="width: 80px; margin-right: 4px;" placeholder="Print $" /> \
      </div></td></tr>\
      <tr><td style="font-weight: bold; text-align: right;">&nbsp;</td>\
      <td>' + '<label for="cb-approve"><input name="approve" type="checkbox" id="cb-approve" style="padding: 3px">\
        <span style="margin-left: 5px; font-weight: normal;">Approve</span></label>' + '</td></tr>\
      <tr><td>&nbsp;</td><td>\
      <div class="error-messages" style="color: red; padding: 5px 0;"></div></td></tr>\
      <tr><td>&nbsp;</td><td>\
      <button type="submit" class="btn-apply-parameters btn-apply-adjustments bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 no-underline">Apply</button>\
      <button class="btn-cancel bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150">Cancel</button>\
      </td></tr>\
      </table>\
    </form>';

    var $lightbox = $(LIGHTBOX_HTML);

    // Initialize values
    _.delay(function() {
      var databaseFieldMap = {
        'sell_total': 'sell',
        'sell_bind': 'bind_sell',
        'sell_print': 'print_sell'
      };
      $fields().forEach(function($field) {
        var fieldName = $field.attr('name');
        var docProd = document.production || {};
        var value = document[fieldName] || docProd[fieldName] || docProd[databaseFieldMap[fieldName]] || $field.val();
        $field.val(value);
      });
    }, 100);

    $lightbox.on('click', '.btn-cancel', function(event) {
      event.preventDefault();
      $.featherlight.close();
    });

    $lightbox.on('click', '.btn-apply-parameters', function(event) {
      event.preventDefault();
      var customDocumentFields = _.zipObject(fieldNames(), fieldValues());
      customDocumentFields._token = $('[name="_token"]').val();
      submitParameters(customDocumentFields);
    });

    $lightbox.on('keyup',
      'input[name="sell_total"], input[name="sell_bind"], input[name="sell_print"]', function(event) {
      var $container = $(event.target).closest('form#taylor-lightbox');
      var $sellBind = $container.find('input[name="sell_bind"]');
      var $sellPrint = $container.find('input[name="sell_print"]');
      var $sellTotal = $container.find('input[name="sell_total"]');
      var sellTotal = +$sellTotal.val();
      var sellBind = +$sellBind.val();
      var sellPrint = +$sellPrint.val();

      if (!sellTotal && sellBind) {
        sellTotal = sellBind + sellPrint;
        $sellTotal.val(sellTotal);
      }

      if ((sellTotal && sellPrint && sellBind) && $(event.target).is($sellTotal)) {
        $sellBind.val('');
        $sellPrint.val('');
        return;
      }

      if ($(event.target).is($sellPrint) && +$sellPrint.val() === 0) {
        return $sellTotal.val(sellBind);
      }

      if ((sellTotal && sellPrint && sellBind) && (sellPrint + sellBind > sellTotal)) {
        if ($(event.target).is($sellBind)) {
          sellPrint = sellTotal - sellBind;
          $sellPrint.val(sellPrint);
        } else {
          sellTotal = sellPrint + sellBind;
          $sellTotal.val(sellTotal);
        }
      }

      if (sellTotal && sellBind) {
        sellPrint = Math.max(0, sellTotal - sellBind);
        $sellPrint.val(sellPrint);
      }

      if (sellPrint < 0) {
        sellTotal = sellTotal + Math.max(0, sellPrint);
        $sellTotal.val(sellTotal);
      }

      if (sellPrint + sellBind > sellTotal) {
        sellTotal = sellPrint + sellBind;
        $sellTotal.val(sellTotal);
      }
    });

    $lightbox.find('input.decimal02').on('keyup', function(event) {
      var strVal = String(event.target.value).trim();
      if (/\.0?$/.test(strVal)) {
        return;
      }
      return (event.target.value = parseFloat(strVal) || 0);
    });

    $.featherlight($lightbox);
  });
}(window, window.taylor, jQuery));
