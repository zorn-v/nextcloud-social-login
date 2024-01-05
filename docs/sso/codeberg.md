What is helped - https://forgejo.codeberg.page/docs/latest/user/oauth2-provider/

`1.` [Create app in Codeberg](https://codeberg.org/user/settings/applications) and copy Client ID and Client secret somewhere. \
`2.` Go to your Nextcloud Admin settings,`Social Login` section, and create Custom Oauth2 provider. 

| Section | What to enter |
| --- | --- |
| Local/inner name | Codeberg |
| Name | Codeberg |
| API Base URL | https://codeberg.org |
| Authorize url | https://codeberg.org/login/oauth/authorize |
| Token url | https://codeberg.org/login/oauth/access_token |
| Profile url | https://codeberg.org/login/oauth/userinfo |
| Logout url | leave empty |
| Client ID and Client secret | paste from step 1 |
| Scope | read:user |
| Other after | leave empty if not sure |
		
`3.` Tick `Allow users to attach/connect their social logins` box and click `Save` in the bottom of page. \
`4.` Go in Incognito/Private window of your browser, go to your Nextcloud login page and copy path of Codeberg provider - Right click, copy link, save somewhere. \
Must look like this - `https://cloud.example.org(/index.php)/apps/sociallogin/custom_oauth2/Codeberg` \
`5.` Go back to your created earlier [Codeberg application](https://codeberg.org/user/settings/applications), click `Edit` button and paste link from step 4 into `Redirection URI` section, click `Save`.

And you done! Try connect your Codeberg account to Nextcloud account from user settings `Social Login` section, it must work. In theory, it's also appliable to `Gitea`, because [Forgejo](https://forgejo.org) is [Gitea](https://gitea.com) fork.
