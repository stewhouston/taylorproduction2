$.extend({
    estimateArchiveFeatherlight: function(container, btnElem, paramsFn) {
        $(container).on('click', btnElem, function(event) {
            var featherlightParams = paramsFn(event, $(event.target));

            if (featherlightParams === false)  {
                return;
            }

            var $content = $('<div class="archive-featherlight-inner" style="width: 400px; height: 200px; padding-left: 30px;">\
                <h2 class="text-2xl font-semibold">Estimate Status</h2>\
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
                <div style="margin-top: 16px;">\
                    <button class="btn-archive-submit bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150">Archive</button> \
                    <button class="btn-archive-cancel bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150">Cancel</button>\
                </div>\
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
