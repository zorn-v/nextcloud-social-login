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


You can use provider groups in two ways:

1. Map provider groups to existing nextcloud groups
2. Create provider groups in nextcloud and associate it to user (if appropriate option specified)

If you want sync groups on every login do not forget to check "Update user profile every login" setting

You can find example how to configure WSO2IS for return roles claim with OIDC at https://medium.com/@dewni.matheesha/claim-mapping-and-retrieving-end-user-information-in-wso2is-cffd5f3937ff

## Telegram

Add [BotFather](https://telegram.me/BotFather)
```
/newbot
nameof_bot
```

>Save the API key somewhere you will need to put it in nextcloud along with your bot username in the social login section.

[Go here](https://core.telegram.org/widgets/login)

Add your bot username and change the authoriazation type to "Redirect to URL"
Enter your nextcloud domain followed by:

`/apps/sociallogin/oauth/telegram`

```
https://cloud.nextcloud.com/apps/sociallogin/oauth/telegram
```
Login with Telegram

Go back to BotFather

```
/mybots
```
Select your bot
```
/setdomain
cloud.nextcloud.com
```
Go to your nextcloud settings social login admin page.
Enter your Telegram bot name
```
nameof_bot
```
and your API key that you copied when you created your bot

And thats it

For using telegram login you need create bot and connect it to domain as described here https://core.telegram.org/widgets/login

Then specify bot login and token in "Social login" section of admin settings page

**Telegram auth will not work everywhere another than browser.***

## Built-in OAuth providers

You can copy link of certain login button to get proper "redirect url" for OAuth app setting.

* [Google](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/google.md)
* [Amazon](https://developer.amazon.com/loginwithamazon/console/site/lwa/overview.html)
* [Facebook](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/facebook.md)
* [Twitter](https://github.com/zorn-v/nextcloud-social-login/blob/master/docs/sso/twitter.md)
* [GitHub](https://github.com/settings/developers)
* [Discord](https://discordapp.com/developers/applications/me#top)

Details about "Allow login only from specified domain" google setting you can find here [#44](https://github.com/zorn-v/nextcloud-social-login/issues/44)
You can use comma separated list for multiple domains

## Config

You can use `'social_login_auto_redirect' => true` setting in `config.php` for auto redirect unauthorized users to social login if only one provider is configured.
If you want to temporary disable this function (e.g. for login as local admin), you can add `noredir=1` query parameter in url for login page. Something like `https://cloud.domain.com/login?noredir=1`

## Hint

### About Callback(Reply) Url
You can copy link from specific login button on login page and paste it on provider's website as callback url!
Some users may get strange reply(Callback) url error from provider even if you pasted the right url, that's because your nextcloud server may generate http urls when you are actually using https.
Please set 'overwriteprotocol' => 'https', in your config.php file.
