export const appName = 'sociallogin'

import { showMessage } from '@nextcloud/dialogs'
export function showError(text) {
  showMessage(text, { type: 'dialogs toast-error' })
}
export function showInfo(text) {
  showMessage(text, { type: 'dialogs toast-info' })
}
