# Social login

Make possible create users and login via Telegram, OAuth or OpenID

For OAuth you must create app for certain providers. Login button appear at login page if app id specified. Settings are in "Social login" section of settings page.

## Installation

Login to your NextCloud installation as an administrator and under "Apps" click "Download and enable" next to the "Social Login" app.

See below for setup and configuration instructions.



## Custom OAuth2/OIDC groups

You can use groups from your custom provider. For that you should specify "Groups claim" in custom OAuth2/OIDC provider settings. That claim should be returned from provider in `id_token` or at user info endpoint. Format should be `array` or comma separated string. Eg (with claim named `roles`)

```json
{"roles": ["admin", "user"]}
or
{"roles": "admin,user"}
```

Also nested claims is supported. For example `resource_access.client-id.roles` for

```json
"resource_access": {
   "client-id": {
     "roles": [
       "client-role-1",
       "client-role-2"
     ]
   }
}
```

There is also support for setting the displayName:
```
{"roles": [{gid: 1, displayName: "admin"}, {gid: 2, displayName: "user"}]}
```


You can use provider groups in two ways:

1. Map provider groups to existing nextcloud groups
2. Create provider groups in nextcloud and associate it to user (if appropriate option specified)

If you want sync groups on every login do not forget to check "Update user profile every login" setting

You can find example how to configure WSO2IS for return roles claim with OIDC at https://medium.com/@dewni.matheesha/claim-mapping-and-retrieving-end-user-information-in-wso2is-cffd5f3937ff

### Example: GitLab OIDC allowing specific GitLab groups

This example shows one way to configure [GitLab as an OpenID Connect (OIDC) identity provider](https://docs.gitlab.com/ee/integration/openid_connect_provider.html), so that only a specific GitLab group is allowed to login. In the example, the GitLab group is named `gitlab-group` with GitLab group ID `12345678` and the Nextcloud server is `https://nextcloud.example.com`.

**Social Login settings**

Social Login app settings at `https://nextcloud.example.com/settings/admin/sociallogin`:

* [ ] Disable auto create new users
* [ ] Create users with disabled account
* [ ] Allow users to connect social logins with their account
* [ ] Prevent creating an account if the email address exists in another account
* [x] Update user profile every login
* [ ] Do not prune not available user groups on login
* [ ] Automatically create groups if they do not exists
* [x] Restrict login for users without mapped groups
* [ ] Disable notify admins about new users

Custom OpenID Connect
```
Internal name: gitlab_oidc
Title: GitLab
Authorize url: https://gitlab.com/oauth/authorize
Token url: https://gitlab.com/oauth/token
User info URL: https://gitlab.com/oauth/userinfo
Logout URL: 
Client Id: [gitlab_application_client_id]
Client Secret:  [gitlab_application_client_secret]
Scope: openid
Groups claim: groups
Button style: Gitlab
Default group: my_existing_nextcloud_group
Group mapping: gitlab-group <--> my_existing_nextcloud_group
```

**GitLab Application settings**

The corresponding GitLab application settings (https://gitlab.com/oauth/applications):
```
Application ID: [gitlab_application_client_id]
Secret: [gitlab_application_client_secret]
Scopes: openid
Confidential: No
Callback URL: https://nextcloud.example.com/apps/sociallogin/custom_oidc/gitlab_oidc
```

## Built-in OAuth providers

You can copy link of certain login button to get proper "redirect url" for OAuth app setting.

* [Google](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/google.md)
* [Amazon](https://developer.amazon.com/loginwithamazon/console/site/lwa/overview.html)
* [Facebook](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/facebook.md)
* [Twitter](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/twitter.md)
* [GitHub](https://github.com/settings/developers)
* [Discord](https://discordapp.com/developers/applications/me#top)
* [Telegram](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/telegram.md)

Details about "Allow login only from specified domain" google setting you can find here [#44](https://github.com/zorn-v/nextcloud-social-login/issues/44)
You can use comma separated list for multiple domains

## Config

You can use `'social_login_auto_redirect' => true` setting in `config.php` for auto redirect unauthorized users to social login if only one provider is configured.
If you want to temporary disable this function (e.g. for login as local admin), you can add `noredir=1` query parameter in url for login page. Something like `https://cloud.domain.com/login?noredir=1`

To set timeout for http client, you can use
```php
  'social_login_http_client' => [
    'timeout' => 45,
  ],
```
in `config.php`

## Hint

### About Callback(Reply) Url
You can copy link from specific login button on login page and paste it on provider's website as callback url. To make proper button visible, just fill certain provider settings with random data and change it later.

Some users may get strange reply(Callback) url error from provider even if you pasted the right url, that's because your nextcloud server may generate http urls when you are actually using https.
Please set 'overwriteprotocol' => 'https', in your config.php file.
