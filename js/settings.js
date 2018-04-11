jQuery(function ($) {
  var appName = 'sociallogin';
  $('#sociallogin_settings').submit(function (e) {
    e.preventDefault();
    $.post(this.action, $(this).serialize())
      .success(function (data) {
        if (data && data.success) {
          OC.Notification.showTemporary(t(appName, 'Settings for social login successfully saved'));
        }
      });
  });
});
