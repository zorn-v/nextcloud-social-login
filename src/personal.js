import confirmPassword from '@nextcloud/password-confirmation'
import axios from '@nextcloud/axios'
import { appName, showError } from './common'

document.addEventListener('DOMContentLoaded', function () {
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
          showError(t(appName, 'Some error occurred while saving settings'))
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
