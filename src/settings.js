import { createApp } from 'vue'
import SettingsView from './components/Settings.vue'

createApp(SettingsView)
  .use((app) => app.config.globalProperties.t = t)
  .mount('#sociallogin_settings_app')
