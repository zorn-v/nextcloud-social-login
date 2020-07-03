document.addEventListener('DOMContentLoaded', function () {
  var tgData = document.getElementById('tg-data')
  if (!tgData) {
    return
  }
  var login = tgData.dataset.login
  var redirectUrl = tgData.dataset.redirectUrl

  var altLogins = document.getElementById('alternative-logins')
  if (altLogins) {
    var script = document.createElement('script')
    script.src = 'https://telegram.org/js/telegram-widget.js?9'
    script.dataset.size = 'large'
    script.dataset.telegramLogin = login
    script.dataset.authUrl = redirectUrl
    altLogins.parentNode.insertBefore(script, altLogins.nextElementSibling)
  }
})
