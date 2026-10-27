(function(w, taylor, _, $) {
  'use strict';

  var documentType = w.taylor.documentType;
  var documentHandlers = {
    'saddlestitch': function(pageMode) {
      toggleColorPageInserts();
      toggleCoverPlusDetails();
      // toggleCountImprints();
    },
    'digest-saddlestitch': function(pageMode) {
      toggleColorPageInserts();
      toggleCoverPlusDetails();
      // toggleCountImprints();
    },
    'perfect-bind': function(pageMode) {
      toggleColorPageInserts();
    }
  };

  var handlerFunc = _.get(documentHandlers, documentType, _.noop);
  handlerFunc(taylor.pageMode);

  switch (taylor.pageMode) {
    case 'EDIT':
      $('#is_digital_only').prop('checked', Boolean(taylor.isDigitalOnly));
      break;
  }

  function toggleCountImprints() {
    var $groupCountImprints = $('#form-group-count-imprints');
    var $field = $('#cover_type');
    $field.on('change.taylorCountImprints', function() {
      if ($field.val() === 'self') {
        $groupCountImprints.hide();
      } else {
        $groupCountImprints.show();
      }
    });
    $field.triggerHandler('change.taylorCountImprints');
  }

  function toggleColorPageInserts() {
    var $groupColorPagesInserts = $('#form-group-count-color-inserts');
    var $fieldTextPrint = $('#text_print');
    var $fieldColourInserts = $('#count_color_inserts');
    $fieldTextPrint.on('change.taylorColorInserts', function() {
      if ($fieldTextPrint.val() === 'Black') {
        $groupColorPagesInserts.show();
      } else {
        $groupColorPagesInserts.hide();
        $fieldColourInserts.val(0);
      }
    });
    $fieldTextPrint.triggerHandler('change.taylorColorInserts');
  }

  function toggleCoverPlusDetails() {
    var $fieldCoverType = $('#cover_type');
    var $groupPlusCoverDetails = $('#group-plus-cover-details');
    $fieldCoverType.on('change.taylorPlusCoverDetails', function() {
      if ($fieldCoverType.val() === 'plus') {
        $groupPlusCoverDetails.css({ 'opacity': 1 });
        enableGroupFields($groupPlusCoverDetails, 'taylorSelfCover');
      } else {
        $groupPlusCoverDetails.css({ 'opacity': .5 });
        disableGroupFields($groupPlusCoverDetails, 'taylorSelfCover');
      }
    });
    $fieldCoverType.triggerHandler('change.taylorPlusCoverDetails');
  }

  function disableGroupFields($group, namespace) {
    $group.find('input, select').each(function(i, elem) {
      if (elem.getAttribute('disabled')) {
        return;
      }
      elem.setAttribute('disabled', namespace);
    });
  }

  function enableGroupFields($group, namespace) {
    $group.find('input, select').each(function(i, elem) {
      if (elem.getAttribute('disabled') === namespace) {
        elem.removeAttribute('disabled');
      }
    });
  }
}(window, globalThis.taylor, globalThis._, globalThis.jQuery));
