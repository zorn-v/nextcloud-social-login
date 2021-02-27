# GitLab OIDC allowing specific GitLab groups

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
