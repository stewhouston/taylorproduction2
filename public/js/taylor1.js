window.taylor = window.taylor || {};

taylor.DEFAULT_MOMENT_FORMAT = 'MMM D, YYYY - h:mma';

window.taylor1 = {};

taylor1.launchPopout = function(url, windowName, windowParams) {
    windowParams = jQuery.extend({
        width: 1180,
        height: 830,
        resizable: 1,
        scrollbars: 1,
        toolbar: 0,
        menubar: 0,
        left: 22,
        top: 22
    }, windowParams);


    var windowParamsString = jQuery.param(windowParams).replace(/&/g, ',');

    var windowRef = window.open(url, windowName, windowParamsString);
};

if (!window.location.origin) {
  window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '');
}
