import { appName } from '../../common'

export default {
  disable_registration: t(appName, 'Disable automated creation of new users'),
  create_disabled_users: t(appName, 'Create users with disabled accounts'),
  allow_login_connect: t(appName, 'Allow users to connect social logins to their accounts'),
  prevent_create_email_exists: t(appName, 'Prevent creating an account if the user\'s email address is already registered with another account'),
  update_profile_on_login: t(appName, 'Update user profile upon every login'),
  no_prune_user_groups: t(appName, 'Do not purge unavailable user groups upon login'),
  auto_create_groups: t(appName, 'Automatically create groups if they do not exist'),
  restrict_users_wo_mapped_groups: t(appName, 'Reject login for users without mapped groups'),
  restrict_users_wo_assigned_groups: t(appName, 'Reject login for users without assigned groups'),
  disable_notify_admins: t(appName, 'Disable notification of admins about new users'),
  hide_default_login: t(appName, 'Hide default login'),
  button_text_wo_prefix: t(appName, 'Button text without prefix'),
}
