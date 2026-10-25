$.extend({
    estimateArchiveFeatherlight: function(container, btnElem, paramsFn) {
        $(container).on('click', btnElem, function(event) {
            var featherlightParams = paramsFn(event, $(event.target));

            if (featherlightParams === false)  {
                return;
            }

            var $content = $('<div class="archive-featherlight-inner" style="width: 400px; height: 210px; padding-left: 30px;">\
                <h2>Estimate Status</h2>\
                <label for="estimate_status_won" style="display: block;">\
                    <input type="radio" id="estimate_status_won" name="estimate_status" value="won"> Won\
                </label>\
                <label for="estimate_status_lost" style="display: block;">\
                    <input type="radio" id="estimate_status_lost" name="estimate_status" value="lost"> Lost\
                </label>\
                <label for="estimate_status_version_only" style="display: block;">\
                    <input type="radio" id="estimate_status_version_only" name="estimate_status" value="version_only"> Version Only\
                </label>\
                <label for="estimate_status_unknown" style="display: block;">\
                    <input type="radio" id="estimate_status_unknown" name="estimate_status" value="unknown"> Unknown\
                </label>\
                <div style="margin-top: 16px;"><button class="btn btn-primary btn-archive-submit">Archive</button> <button class="btn btn-default btn-archive-cancel">Cancel</button></div>\
            </div>');

            $content.on('click', '.btn-archive-submit', function(event) {
                var status = $(event.target).closest('.archive-featherlight-inner')
                    .find('input[name="estimate_status"]:checked').attr('value');

                $.featherlight.close({
                    action: 'submit',
                    status: status
                });
            });

            $content.on('click', '.btn-archive-cancel', function(event) {
                $.featherlight.close({
                    action: 'cancel'
                });
            })

            $.featherlight($content, featherlightParams);
        });
    }
});
