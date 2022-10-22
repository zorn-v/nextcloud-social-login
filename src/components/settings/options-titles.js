import { appName } from '../../common'

export default {
  disable_registration: t(appName, 'Disable auto create new users'),
  create_disabled_users: t(appName, 'Create users with disabled account'),
  allow_login_connect: t(appName, 'Allow users to connect social logins with their account'),
  prevent_create_email_exists: t(appName, 'Prevent creating an account if the email address exists in another account'),
  update_profile_on_login: t(appName, 'Update user profile every login'),
  no_prune_user_groups: t(appName, 'Do not prune not available user groups on login'),
  auto_create_groups: t(appName, 'Automatically create groups if they do not exists'),
  restrict_users_wo_mapped_groups: t(appName, 'Restrict login for users without mapped groups'),
  restrict_users_wo_assigned_groups: t(appName, 'Restrict login for users without assigned groups'),
  disable_notify_admins: t(appName, 'Disable notify admins about new users'),
  hide_default_login: t(appName, 'Hide default login'),
  button_text_wo_prefix: t(appName, 'Button text without prefix'),
}
