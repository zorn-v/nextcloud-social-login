# GitLab OIDC: Allowing Specific GitLab Groups

This example demonstrates one way to configure [GitLab as an OpenID Connect (OIDC) identity provider](https://docs.gitlab.com/ee/integration/openid_connect_provider.html) so that only a specific GitLab group is allowed to log in. In this example, the GitLab group is named `gitlab-group` with GitLab group ID `12345678`, and the Nextcloud server is `https://nextcloud.example.com`.

**Social Login Settings**

Social Login app settings at `https://nextcloud.example.com/settings/admin/sociallogin`:

* [ ] Disable automated creation of new users
* [ ] Create users with disabled accounts
* [ ] Allow users to connect social logins to their accounts
* [ ] Prevent creating an account if the user's email address is already registered with another account
* [x] Update user profile upon every login
* [ ] Do not purge unavailable user groups upon login
* [ ] Automatically create groups if they do not exist
* [x] Reject login for users without mapped groups
* [ ] Disable notification of admins about new users

Custom OpenID Connect

```
Internal name: gitlab_oidc
Title: GitLab
Authorize url: https://gitlab.com/oauth/authorize
Token url: https://gitlab.com/oauth/token
User info URL: https://gitlab.com/oauth/userinfo
Logout URL:
Client Id: [gitlab_application_client_id]
Client Secret: [gitlab_application_client_secret]
Scope: openid
Groups claim: groups
Button style: Gitlab
Default group: my_existing_nextcloud_group
Group mapping: gitlab-group <--> my_existing_nextcloud_group
```

**GitLab Application Settings**

The corresponding GitLab application settings (https://gitlab.com/oauth/applications):

```
Application ID: [gitlab_application_client_id]
Secret: [gitlab_application_client_secret]
Scopes: openid
Confidential: No
Callback URL: https://nextcloud.example.com/apps/sociallogin/custom_oidc/gitlab_oidc
```
