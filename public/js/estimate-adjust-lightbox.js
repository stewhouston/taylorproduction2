(function(w) {
    // Adjustments lightbox
    // split `adjustment_reason` to `print_sell_adjust_reason`, `drop_sell_adjust_reason`, `file_require_adjust_reason`
    $('.btn-adjustments-handle').click(function(event) {
        var lightboxHtml = ['<form style="width: 700px; height: 400px; position: relative; margin-top: -6px;" autocomplete="off">',
            '<table>',
                '<thead><tr>\
                    <th style="font-size: 25px; padding-bottom: 10px;">Overrides</th><th>&nbsp;</th><th>&nbsp;</th>\
                </thead>',
                '<tbody>',
                    // row
                    '<tr><td style="font-weight: bold; padding-top: 8px; padding-right: 30px; vertical-align: top;">\
                            Print Cost Adjustment:\
                         </td><td><div class="input-group flex" style="padding-bottom: 10px;">\
                            <span class="input-group-addon">$</span>\
                            <input type="text" class="form-control" placeholder="0.00" name="print_sell_adjustment">\
                        </div></td><td>\
                            <div class="input-group" style="padding-bottom: 10px;">\
                               <input type="text" class="form-control" name="print_sell_adjust_reason" placeholder="Adjustment Reason" style="margin-left: 5px; font-size: 12px; width: 250px;">\
                           </div>\
                        </td></tr>',
                    // row
                    '<tr><td style="font-weight: bold; padding-top: 8px; padding-right: 30px; vertical-align: top;">\
                            Ship Cost Adjustment:\
                        </td><td><div class="input-group flex" style="padding-bottom: 10px;">\
                            <span class="input-group-addon">$</span>\
                            <input type="text" class="form-control" placeholder="0.00" name="drop_sell_adjustment">\
                        </div></td><td>\
                            <div class="input-group" style="padding-bottom: 10px;">\
                               <input type="text" class="form-control" name="drop_sell_adjust_reason" placeholder="Adjustment Reason" style="margin-left: 5px; font-size: 12px; width: 250px;">\
                           </div>\
                        </td></tr>',
                        // row
                        '<tr><td style="font-weight: bold; padding-top: 8px; padding-right: 30px; vertical-align: top; white-space: nowrap;">\
                                File Require Adjustment:\
                            </td><td><div class="input-group" style="padding-bottom: 10px;">',
                                // File Require Datepicker
                                '<input type="text" name="files_require_adjustment" class="form-control" value="" style="width: 218px;">',
                                '</div></td><td>\
                                    <div class="input-group" style="padding-bottom: 10px;">\
                                       <input type="text" class="form-control" name="file_require_adjust_reason" placeholder="Adjustment Reason" style="margin-left: 5px; font-size: 12px; width: 250px;">\
                                   </div>\
                                </td></tr>',
                        // add alert row
                        '<tr><td style="font-weight: bold; padding-top: 8px; padding-right: 30px; vertical-align: top;">\
                                Custom Alert:\
                            </td><td colspan="2"><div class="input-group" style="padding-bottom: 10px;">',
                                '<input type="text" name="custom_alert" class="form-control" value="" style="width: 475px;">',
                                '</div></td><td>&nbsp;</td></tr>',
                        // estimate comment
                        '<tr><td style="font-weight: bold; padding-top: 8px; padding-right: 30px; vertical-align: top;">\
                                Estimate Comment:\
                            </td><td colspan="2"><div class="input-group" style="padding-bottom: 10px;">',
                                // File Require Datepicker
                                '<input type="text" name="estimate_comment" class="form-control" value="" style="width: 475px;">',
                                '</div></td><td>&nbsp;</td></tr>',
                '</tbody>',
                '<tfoot>',
                '<tr><td style="padding-top: 15px;">\
                    <button type="submit" class="btn-apply-adjustments bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 no-underline">Apply</button>\
                    <button class="btn-cancel ml-4 text-blue-800 underline">Cancel</button>\
                </td>\
                <td colspan="2">\
                    <button class="btn-clear-alerts bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150" style="float: right;">Clear Alerts</button>\
                    <span class="alerts-cleared-msg" style="display: none; margin-right: 10px; margin-top: 7px; float: right; font-style: italic;">\
                        Alerts cleared\
                    </span>\
                </td>\
                </tr>',
                '</tfoot>',
            '</table>',
        '</form>'].join('');

        var $adjustmentsLightbox = $(lightboxHtml);
        var estimate = w.taylor.estimate || {};

        // populate data
        $adjustmentsLightbox.find('input[name="print_sell_adjust_reason"]').val(estimate.print_sell_adjust_reason);
        $adjustmentsLightbox.find('input[name="print_sell_adjustment"]').val(estimate.print_sell_adjustment);
        $adjustmentsLightbox.find('input[name="drop_sell_adjust_reason"]').val(estimate.drop_sell_adjust_reason);
        $adjustmentsLightbox.find('input[name="drop_sell_adjustment"]').val(estimate.drop_sell_adjustment);
        $adjustmentsLightbox.find('input[name="estimate_comment"]').val(estimate.estimate_comment);
        $adjustmentsLightbox.find('input[name="file_require_adjust_reason"]').val(estimate.file_require_adjust_reason);

        $adjustmentsLightbox.on('click', '.btn-apply-adjustments', function(event) {
            var $featherlightInner = $(this).closest('.featherlight-content');

            submitApplyAdjustmentsHandler(event, $featherlightInner);
        });

        $adjustmentsLightbox.on('click', '.btn-cancel', function(event) {
            event.preventDefault();
            $.featherlight.close();
        });

        $adjustmentsLightbox.on('click', '.btn-clear-alerts', function(event) {
            var $featherlightInner = $(this).closest('.featherlight-content');

            $.ajax({
                method: 'POST',
                url: location.origin + '/estimate/' + w.taylor.estimateId + '/set_alerts',
                data: {
                    clearAll: true
                },
                success: function() {
                    console.log('clear alerts success:', arguments);

                    $featherlightInner.find('.alerts-cleared-msg').show();
                    $('.estimate-alerts-message').hide(); // alert on main estimate view

                    window.location.reload();
                },
                error: function() {
                    console.error('clear alerts error', arguments);
                }
            });
        });

        var fileReqAjustment = taylor.estimate.files_require_adjustment;
        var filesRequiredDate = (fileReqAjustment && fileReqAjustment != "0000-00-00 00:00:00") ? moment(fileReqAjustment) : null;
        var blockedDates = getStatHolidayDates();

        $.featherlight($adjustmentsLightbox, {
            variant: 'taylor-adjustments',
            afterOpen: function() {
                var $featherlightInner = this.$content;

                $featherlightInner.closest('.featherlight-content').css({ overflow: 'visible' });

                $featherlightInner.find('input[name="files_require_adjustment"]')
                    .val(function() {})
                    .datetimepicker({
                        dateFormat: 'yy-mm-dd',
                        timeFormat: 'h:mmtt',
                        defaultDate: filesRequiredDate ? filesRequiredDate.format('YYYY-MM-DD h:mma') : '', // @todo confirm this works
                        beforeShowDay: function(date) {
                            var stringDate = $.datepicker.formatDate('yy-mm-dd', date);
                            if ($.inArray(stringDate, blockedDates) !== -1) {
                                return [false, "blocked-date-css", "This date is unavailable"]; 
                            }
                            return [true, ''];
                        }
                    });

            }
        });
    });

    function submitApplyAdjustmentsHandler(event, $featherlightInner) {
        var $printSellAdjustValue = $featherlightInner.find('input[name="print_sell_adjustment"]');
        var $printSellAdjustReason = $featherlightInner.find('input[name="print_sell_adjust_reason"]');

        var $dropSellAdjustValue = $featherlightInner.find('input[name="drop_sell_adjustment"]');
        var $dropSellAdjustReason = $featherlightInner.find('input[name="drop_sell_adjust_reason"]');

        var $filesReqAdjustDate = $featherlightInner.find('input[name="files_require_adjustment"]');
        var $filesReqAdjustReason = $featherlightInner.find('input[name="file_require_adjust_reason"]');

        var $customAlert = $featherlightInner.find('input[name="custom_alert"]');
        var $estimateComment = $featherlightInner.find('input[name="estimate_comment"]');

        var filesReqAdjustDate = $filesReqAdjustDate.val();

        if (filesReqAdjustDate) {
            filesReqAdjustDate = moment($filesReqAdjustDate.val(), 'YYYY-MM-DD h:mma').format();
        }

        var formData = {
            priceAdjustReason: $printSellAdjustReason.val(),
            // no commas in prices
            priceAdjustValue: $printSellAdjustValue.val().replace(/,/g, ''),
            shipAdjustReason: $dropSellAdjustReason.val(),
            shipAdjustValue: $dropSellAdjustValue.val().replace(/,/g, ''),
            filesReqAdjustDate: filesReqAdjustDate,
            filesReqAdjustReason: $filesReqAdjustReason.val(),
            customAlert: encodeURIComponent($customAlert.val()),
            estimateComment: encodeURIComponent($estimateComment.val())
        };

        $.ajax({
            method: 'POST',
            url: location.origin + '/estimate/' + w.taylor.estimateId + '/set_overrides',
            data: formData,
            success: function(updatedEstimate) {
                console.log('OVERRIDES SUCCESS:', updatedEstimate);
                if (_.isObject(updatedEstimate) && updatedEstimate.client_reference_number) {
                    w.taylor.estimate = updatedEstimate;
                    // w.taylor.applyAdjustOverrides(updatedEstimate); // Page reloads now, this gets swept away, moved to Blade template
                    location.reload();
                }
            },
            error: function(err) {
                console.log('OVERRIDES ERROR:', arguments);
                var errmsg = JSON.stringify(err).substr(0, 200);
                var ok = confirm('An error occurred -- overrides could not be saved. Please contact the technical staff. The error for reference: ' + errmsg);
                if (ok) {
                    $.featherlight.close();
                }
            }
        });
    }

    // display adjust override data in the show.blade dom
    w.taylor.applyAdjustOverrides = function(estimate) {
        var printSellAdjust = parseFloat(estimate.print_sell_adjustment);
        var shipSellAdjust = parseFloat(estimate.drop_sell_adjustment);

        if (printSellAdjust) {
            $('#price-adjust-row').show();

            // populateShortenedReasonText('#price-adjust-reason', estimate.print_sell_adjust_reason);

            if (estimate.print_sell_adjustment > 0) {
                $('#price-adjust-value').text('$' + numeral(estimate.print_sell_adjustment).format('0,0.00'));
                $('#price-adjust-value').closest('td').css({ 'color': 'green' });
            }
            else {
                $('#price-adjust-value').text('(' + numeral(estimate.print_sell_adjustment*-1).format('0,0.00') + ')');
                $('#price-adjust-value').closest('td').css({ 'color': 'red' });
            }
        }

        if (shipSellAdjust) {
            $('#shipping-adjust-row').show();

            // populateShortenedReasonText('#shipping-adjust-reason', estimate.drop_sell_adjust_reason);

            if (estimate.drop_sell_adjustment > 0) {
                $('#shipping-adjust-value').text('$' + numeral(estimate.drop_sell_adjustment).format('0,0.00'));
                $('#shipping-adjust-value').closest('td').css({ 'color': 'green' });
            }
            else {
                $('#shipping-adjust-value').text('(' + numeral(estimate.drop_sell_adjustment*-1).format('0,0.00') + ')');
                $('#shipping-adjust-value').closest('td').css({ 'color': 'red' });
            }
        }

        if (estimate.files_require_adjustment && estimate.files_require_adjustment != "0000-00-00 00:00:00") {
            var filesReqAdjust = moment(estimate.files_require_adjustment).format('MMM D, YYYY - h:mma');
            $('.files-required-by').text(filesReqAdjust).css({'color': '#c32a07'});
        }
    }

    function populateShortenedReasonText(containerSelector, textValue) {
        textValue = textValue || '';
        var words = textValue.split(' ');
        var textValueFull = textValue;
        if (words.length > 8) {
            textValue = words.slice(0, 8).join(' ') + '…';
        }
        var $textSpan = jQuery('<span><strong>Reason:</strong> ' + textValue + '</span>');
        $(containerSelector).append($textSpan);
        $textSpan.tooltipster({
            content: $('<div style="max-width: 300px;">' + textValueFull + '</div>'),
            theme: 'tooltipster-noir',
            interactive: true
        });
    }

    function getStatHolidayDates() {
        // from static content ShippingService::getStatHolidays
        return _.map(w.taylor.statHolidaysDb, function(customDateObj) {
            return moment(customDateObj['date_string'], 'D-MMM-YYYY').format('YYYY-MM-DD');
        });
    }
}(window));
