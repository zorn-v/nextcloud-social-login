import { createApp } from 'vue'
import { appName } from './common'
import SettingsView from './components/Settings.vue'

createApp(SettingsView)
  .use((app) => {
    app.config.globalProperties.t = t
    app.config.globalProperties.appName = appName
  })
  .mount('#sociallogin_settings_app')
