import '@nextcloud/dialogs/styles/toast.scss'
import { showError } from '@nextcloud/dialogs'
import confirmPassword from '@nextcloud/password-confirmation'
import axios from '@nextcloud/axios'

document.addEventListener('DOMContentLoaded', function () {
  var appName = 'sociallogin';
  var form = document.getElementById('sociallogin_personal_settings')
  function saveSettings() {
    confirmPassword().then(function () {
      axios.post(form.action, new FormData(form))
        .then(function (res) {
          if (!res.data.success) {
            showError(res.data.message);
          }
        })
        .catch(function () {
          var msg = 'Some error occurred while saving settings'
          showError(t(appName, msg));
        })
    })
  }
  form.onsubmit = function (e) {
    e.preventDefault()
    saveSettings()
  }
  var inputs = document.querySelectorAll('#sociallogin_personal_settings input')
  for (var i = 0; i < inputs.length; ++i) {
    inputs[i].onchange = function () {
      saveSettings()
    }
  }
})
