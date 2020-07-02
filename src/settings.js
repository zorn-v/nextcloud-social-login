import { showError, showInfo } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import E from 'oui-dom-events'

document.addEventListener('DOMContentLoaded', function () {
  var appName = 'sociallogin'

  document.getElementById('sociallogin_settings').onsubmit = function (e) {
    e.preventDefault()
    axios.post(this.action, new FormData(this))
      .then(function (res) {
        if (res.data.success) {
          showInfo(t(appName, 'Settings for social login successfully saved'));
        } else {
          showError(res.data.message);
        }
      })
      .catch(function () {
        showError(t(appName, 'Some error occurred while saving settings'));
      })
  }

  var disableReg = document.getElementById('disable_registration')
  disableReg.onchange = function () {
    document.getElementById('prevent_create_email_exists').disabled = this.checked
  }
  disableReg.onchange()

  initProviderType('openid')
  initProviderType('custom_oidc')
  initProviderType('custom_oauth2')

  function initProviderType(providerType){
    createDelegate(providerType)
    createAdd(providerType)
  }

  function createDelegate(providerType){
    var providersEl = document.getElementById(providerType+'_providers')
    E.delegate(providersEl, '.'+providerType+'-remove', 'click', function () {
      var provider = this.parentNode
      var providerTitle = provider.querySelector('[name$="[title]"]').value
      var needConfirm = function () {
        var inputs = provider.querySelectorAll('input')
        for (var i = 0; i < inputs.length; ++i) {
          if (inputs[i].value) {
            return true
          }
        }
        return false
      }
      if (needConfirm()) {
        OC.dialogs.confirm(
          t(appName, 'Do you realy want to remove {providerTitle} provider ?', {'providerTitle': providerTitle}),
          t(appName, 'Confirm remove'),
          function (confirmed) {
            if (!confirmed) {
              return;
            }
            provider.parentNode.removeChild(provider)
          },
          true
        )
      } else {
        provider.parentNode.removeChild(provider)
      }
    })
    E.delegate(providersEl, '.group-mapping-add', 'click', function () {
      var provider = this.parentNode
      var tpl = provider.querySelector('.group-mapping-tpl');
      var div = document.createElement('div')
      div.innerHTML = tpl.innerHTML
      provider.appendChild(div)
    })
    E.delegate(providersEl, '.group-mapping-remove', 'click', function () {
      this.parentNode.parentNode.removeChild(this.parentNode)
    })
    E.delegate(providersEl, '.foreign-group', 'input', function () {
      var newName = this.value ? this.dataset.nameTpl+'['+this.value+']' : ''
      this.nextElementSibling.name = newName
    });
  }

  function createAdd(providerType){
    document.getElementById(providerType+'_add').onclick = function () {
      var tpl = document.getElementById(providerType+'_provider_tpl')
      var newId = tpl.dataset.newId
      tpl.dataset.newId = parseInt(newId) + 1
      var html = tpl.innerHTML.replace(/{{provider_id}}/g, newId)
      var div = document.createElement('div')
      div.className = 'provider-settings'
      div.innerHTML = html
      document.getElementById(providerType+'_providers').appendChild(div)
    }
  }
})
