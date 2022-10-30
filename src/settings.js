import Vue from 'vue'
import SettingsView from './components/Settings.vue'

Vue.prototype.t = t
new Vue({
  el: '#sociallogin_settings_app',
  render: h => h(SettingsView),
})
