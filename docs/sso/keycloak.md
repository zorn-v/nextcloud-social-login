# Keycloak
This small doc will help you to configure Keycloak, map keycloak/nextcloud groups and even prevent login if you are not member of any group.

## Create clientID in Keycloak
Here the settings:
```
Client ID:                       <keycloak-client-id> # Your choice
Root URL:                        https://<your-nextcloud-url.fr>
Home URL:
Valid redirect URIs:             https://<your-nextcloud-url.fr>/apps/sociallogin/custom_oidc/keycloak>
                                 https://<your-nextcloud-url.fr>/apps/user_oidc/code

Valid post logout redirect URIs: https://<your-nextcloud-url.fr>/*

Web origins:
Admin URL:
```

In Capability config, you must select:
```
Client authentication   On
Authorization           Off
Authentication flow     [x] Standard flow                        [x] Direct access grants
                        [ ] Implicit flow                        [ ] Service accounts roles
                        [ ] OAuth 2.0 Device Authorization Grant
                        [ ] OIDC CIBA Grant
```

## Configure Client scopes
Select on the left the menu `Client scopes`, click on `Create client scope` button and fill with:
```
Name:                      groups-nextcloud
Type:                      None
Protocol:                  OpenID Connect
Display on consent screen: On
Include in token scope:    On
```

In `Mappers` tab, click on `Add mapper` and `By configuration`, select a `Group Membership` and fill with:
```
Mapper type:                Group Membership
Name:                       groups-mapper
Token Claim Name:           groups
Full group path:            Off
Add to ID token:            Off
Add to access token:        On
Add to userinfo:            On
Add to token introspection: Off
```

## Add your new client scope to your client
Select on the left the menu `Clients`, click on the previously created Client, select `Client scopes` tab in your client (not on the left menu).

Click on `Add client scope`, select your previously created client scope (groups-nextcloud), click on `Add` and select `Default`.

## Groups creation
Select on the left the menu `Groups`, click on `Create group` button and create groups:
- nextcloud-admins
- nextcloud-users
- nextcloud-youpi
- ...

Do not forget to add Users to Groups..

# Nextcloud
Select `Social login` from `https://<nextcloud.your-domain.fr>/settings/admin/sociallogin`

* [ ] Disable auto create new users
* [ ] Create users with disabled account
* [ ] Allow users to connect social logins with their account
* [x] Prevent creating an account if the email address exists in another account
* [x] Update user profile every login
* [ ] Do not prune not available user groups on login
* [ ] Automatically create groups if they do not exists
* [x] Restrict login for users without mapped groups
* [x] Restrict login for users without assigned groups
* [ ] Disable notify admins about new users
* [ ] Hide default login
* [ ] Button text without prefix


Add a new `Custom OpenID Connect` and fill with:
```
Internal name: keycloak
Title:         Keycloak
Authorize url: https://<your-keycloak-url.fr>/auth/realms/<your-realm>/protocol/openid-connect/auth
Token url:     https://<your-keycloak-url.fr>/auth/realms/<your-realm>/protocol/openid-connect/token
Display name claim (optional):
User info URL: https://<your-keycloak-url.fr>/auth/realms/<your-realm>/protocol/openid-connect/userinfo
Logout URL:    https://<your-keycloak-url.fr>/auth/realms/<your-realm>/protocol/openid-connect/logout?post_logout_redirect_uri=https%3A%2F%2F<your-nextcloud-url.fr>&client_id=<keycloak-client-id>
Client Id:     <keycloak-client-id>
Client Secret: <keycloak-client-secret>
Scope:         openid groups-nextcloud profile
Groups claim:  groups
Button style:  Keycloak
Default group: None
Group mapping: nextcloud-admins <--> admin
               nextcloud-users  <--> users
               nextcloud-youpi  <--> youpi
```

## Automation
Here an example to configure with `occ` command:
```bash
#!/usr/bin/env bash
#set -x

NEXTCLOUD_HOSTNAME=<your-nextcloud-url.fr>
SSO_REALM_URL=https://<your-keycloak-url.fr>/auth/realms/<your-realm>
SSO_CLIENT_ID=<keycloak-client-id>
SSO_CLIENT_SECRET=<keycloak-client-secret>
GROUP_MAPPING={"nextcloud-users":"users","nextcloud-admins":"admin","nextcloud-youpi":"youpi"}

# Group needed for SSO mapping
php occ group:add users
php occ group:add youpi

# Install Social login
php occ app:install sociallogin

# SSO config
read -r -d '' sso <<-EOF
{"custom_oidc": [{"name": "keycloak", "title": "keycloak", "authorizeUrl": "${SSO_REALM_URL}/protocol/openid-connect/auth", "tokenUrl": "${SSO_REALM_URL}/protocol/openid-connect/token", "userInfoUrl": "${SSO_REALM_URL}/protocol/openid-connect/userinfo", "logoutUrl": "${SSO_REALM_URL}/protocol/openid-connect/logout?post_logout_redirect_uri=https%3A%2F%2F${NEXTCLOUD_HOSTNAME}&client_id=${SSO_CLIENT_ID}", "clientId": "${SSO_CLIENT_ID}", "clientSecret": "${SSO_CLIENT_SECRET}", "scope": "openid groups-nextcloud profile", "groupsClaim": "groups", "style": "keycloak", "defaultGroup": "", "groupMapping": ${GROUP_MAPPING}}]}
EOF
php occ config:app:set sociallogin custom_providers --value="${sso}"

# Config to restrict access
php occ config:app:set sociallogin prevent_create_email_exists --value=1
php occ config:app:set sociallogin update_profile_on_login --value=1
php occ config:app:set sociallogin restrict_users_wo_mapped_groups --value=1
php occ config:app:set sociallogin restrict_users_wo_assigned_groups --value=1
```
