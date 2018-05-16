jQuery(function ($) {
  var appName = 'sociallogin';
  var showError = function(text) {
    OC.Notification.showTemporary('<div style="font-weight:bold;color:red">'+text+'<div>', {isHTML: true});
  }
  $('#sociallogin_personal_settings').submit(function (e) {
    e.preventDefault();
    var self = this;
    var saveSettings = function () {
      if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
        OC.PasswordConfirmation.requirePasswordConfirmation(saveSettings);
        return;
      }

      $.post(self.action, $(self).serialize())
        .success(function (data) {
          if (!data || !data.success) {
            showError(data.message);
          }
        })
        .error(function (e) {
          var msg = e.responseJSON && e.responseJSON.message ? e.responseJSON.message : 'Some error occurred while saving settings';
          showError(t(appName, msg));
        });
    }
    saveSettings();
  });
  $('#sociallogin_personal_settings input').change(function () {
    $(this.form).submit();
  })
});
