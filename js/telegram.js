jQuery(function ($) {
  var tgData = $('tg-data');
  var login = tgData.data('login');
  var redirectUrl = tgData.data('redirect-url');
  $('#alternative-logins').before('<script src="https://telegram.org/js/telegram-widget.js?5" data-size="large" data-telegram-login="'+login+'" data-auth-url="'+redirectUrl+'"/>');
});
