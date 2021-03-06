document.addEventListener('DOMContentLoaded', function () {
  var tgData = document.getElementById('tg-data')
  if (!tgData) {
    return
  }
  var login = tgData.dataset.login
  var redirectUrl = tgData.dataset.redirectUrl

  var mainEl = document.getElementsByTagName('main')[0]
  if (mainEl && (document.querySelector('form[name="login"]') || document.querySelector('.section.sociallogin-connect'))) {
    var script = document.createElement('script')
    script.src = 'https://telegram.org/js/telegram-widget.js?9'
    script.dataset.size = 'large'
    script.dataset.telegramLogin = login
    script.dataset.authUrl = redirectUrl
    mainEl.appendChild(script)
  }
})
