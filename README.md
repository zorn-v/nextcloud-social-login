# Social login

Make possible create users and login via OAuth or OpenID

For OAuth you must create app for certain providers. Login button appear at login page if app id specified. Settings are in "Social login" section of settings page.

## Built-in OAuth providers

You can create app by followed urls. You can copy link of certain login button to get proper "redirect url" for OAuth app setting.

* [Facebook](https://developers.facebook.com/)
* [Google](https://console.developers.google.com)
* [Twitter](https://apps.twitter.com/)
* [GitHub](https://github.com/settings/developers)
* [Discord](https://discordapp.com/developers/applications/me#top)

## Config

You can use `'social_login_auto_redirect' => true` setting in `config.php` for auto redirect unauthorized users to social login if only one provider is configured.
