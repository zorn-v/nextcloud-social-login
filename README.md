# Social login

Make possible create users and login via Telegram, OAuth or OpenID

For OAuth you must create app for certain providers. Login button appear at login page if app id specified. Settings are in "Social login" section of settings page.

## Telegram

For using telegram login you need create bot and connect it to domain as described here https://core.telegram.org/widgets/login

Then specify bot login and token in "Social login" section of admin settings page

**Telegram auth will not work everywhere another than browser.***

## Built-in OAuth providers

You can create app by followed urls. You can copy link of certain login button to get proper "redirect url" for OAuth app setting.

* [Google](https://console.developers.google.com)
* [Amazon](https://developer.amazon.com/loginwithamazon/console/site/lwa/overview.html)
* [Facebook](https://developers.facebook.com/)
* [Twitter](https://apps.twitter.com/)
* [GitHub](https://github.com/settings/developers)
* [Discord](https://discordapp.com/developers/applications/me#top)

Details about "Allow login only from specified domain" google setting you can find here [#44](https://github.com/zorn-v/nextcloud-social-login/issues/44)

Custom providers is on your own. Officially not supported

## Config

You can use `'social_login_auto_redirect' => true` setting in `config.php` for auto redirect unauthorized users to social login if only one provider is configured.

## Hint

### About Callback(Reply) Url
You can copy link from specific login button on login page and paste it on provider's website as callback url!
Some users may get strange reply(Callback) url error from provider even if you pasted the right url, that's because your nextcloud server may generate http urls when you are actually using https.
Please set 'overwriteprotocol' => 'https', in your config.php file.
