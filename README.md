# Social login

Make it possible to create users and log in via Telegram, OAuth, or OpenID.

For OAuth, you must create an app with certain providers. Login buttons will appear on the login page if an app ID is specified. Settings are located in the "Social login" section of the settings page.

## Installation

Log in to your Nextcloud installation as an administrator. Under "Apps", click "Download and enable" next to the "Social Login" app.

See below for setup and configuration instructions.

## Custom OAuth2/OIDC groups

You can use groups from your custom provider. For this, specify the "Groups claim" in the custom OAuth2/OIDC provider settings. This claim should be returned from the provider in the `id_token` or at the user info endpoint. The format should be an `array` or a comma-separated string. E.g., (with a claim named `roles`):

```json
{"roles": ["admin", "user"]}
```
or
```json
{"roles": "admin,user"}
```

Nested claims are also supported. For example, `resource_access.client-id.roles` for:

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

DisplayName support is also available:
```json
{"roles": [{"gid": 1, "displayName": "admin"}, {"gid": 2, "displayName": "user"}]}
```

You can use provider groups in two ways:

1. Map provider groups to existing Nextcloud groups.
2. Create provider groups in Nextcloud and associate them with users (if the appropriate option is enabled).

To sync groups on every login, ensure the "Update user profile every login" setting is checked.

## Examples for groups

* Configure WSO2IS to return a roles claim with OIDC [here](https://medium.com/@dewni.matheesha/claim-mapping-and-retrieving-end-user-information-in-wso2is-cffd5f3937ff).
* [GitLab OIDC configuration to allow specific GitLab groups](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/gitlab.md).

## Built-in OAuth providers

Copy the link from a specific login button to get the correct "redirect URL" for OAuth app settings.

* [Amazon](https://developer.amazon.com/loginwithamazon/console/site/lwa/overview.html)
* [Apple](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/apple.md)
* [Codeberg](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/codeberg.md)
* [Discord](#configure-discord)
* [Facebook](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/facebook.md)
* [GitHub](https://github.com/settings/developers)
* [GitLab](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/gitlab.md)
* [Google](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/google.md)
* [Keycloak](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/keycloak.md)
* [Mail.ru](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/mailru.md)
* **PlexTv**: Use any title as the app ID.
* [Telegram](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/telegram.md)
* [Twitter](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/twitter.md)

For details about Google's "Allow login only from specified domain" setting, see [#44](https://github.com/zorn-v/nextcloud-social-login/issues/44). Use a comma-separated list for multiple domains.

## Config

Add `'social_login_auto_redirect' => true` to `config.php` to automatically redirect unauthorized users to social login if only one provider is configured. To temporarily disable this (e.g., for local admin login), add `noredir=1` to the login URL: `https://cloud.domain.com/login?noredir=1`.

Configure HTTP client options using:
```php
  'social_login_http_client' => [
    'timeout' => 45,
    'proxy' => 'socks4://127.0.0.1:9050', // See <https://curl.se/libcurl/c/CURLOPT_PROXY.html> for allowed formats
  ],
```
in `config.php`.

### Configure a provider via CLI

Use the `occ` utility to configure providers via the command line. Replace variables and URLs with your deployment values:
```bash
php occ config:app:set sociallogin custom_providers --value='{"custom_oidc": [{"name": "gitlab_oidc", "title": "Gitlab", "authorizeUrl": "https://gitlab.my-domain.org/oauth/authorize", "tokenUrl": "https://gitlab.my-domain.org/oauth/token", "userInfoUrl": "https://gitlab.my-domain.org/oauth/userinfo", "logoutUrl": "", "clientId": "$my_application_id", "clientSecret": "$my_super_secret_secret", "scope": "openid", "groupsClaim": "groups", "style": "gitlab", "defaultGroup": ""}]}'
```
For Docker, prepend `docker exec -t -uwww-data CONTAINER_NAME` to the command or run interactively via `docker exec -it -uwww-data CONTAINER_NAME sh`.

To inspect configurations:
```sql
mysql -u nextcloud -p nextcloud
Password: <yourpassword>

> SELECT * FROM oc_appconfig WHERE appid='sociallogin';
```
Or run:
```bash
docker exec -t -uwww-data CONTAINER_NAME php occ config:app:get sociallogin custom_providers
```

### Configure Discord

1. Create a Discord application at [Discord Developer Portal](https://discord.com/developers/applications).
2. Navigate to `Settings > OAuth2 > General`. Add a redirect URL: `https://nextcloud.mydomain.com/apps/sociallogin/oauth/discord`.
3. Copy the `CLIENT ID` and generate a `CLIENT SECRET`.
4. In Nextcloud, go to `Settings > Social Login`. Paste the `CLIENT ID` into "App id" and `CLIENT SECRET` into "Secret".
5. Select a default group for new users.
6. For group mapping, see [#395](https://github.com/zorn-v/nextcloud-social-login/pull/395).

## Hint

### Callback (Reply) URL
Copy the link from a login button on the Nextcloud login page and use it as the callback URL on your provider's site. To make the button visible temporarily, fill provider settings with placeholder data and update later.

If you encounter callback URL errors despite correct settings, ensure your Nextcloud server generates HTTPS URLs by adding `'overwriteprotocol' => 'https'` to `config.php`.
