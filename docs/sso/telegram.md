# How to Setup Telegram SSO

These instructions assume "example.com" is the domain for your NextCloud. Adjust according to your setup.

Add [BotFather](https://telegram.me/BotFather)

```
/newbot
nameof_bot
```

> Save the API key somewhere you will need to put it in nextcloud along with your bot username in the social login section.

Set domain of you nextcloud instance

```
/setdomain
example.com
```

Go to your nextcloud settings social login admin page.
Enter your Telegram bot name in `App id` field

```
nameof_bot
```

and your API key that you copied when you created your bot to `Secret` field

And thats it

**Telegram auth will not work everywhere another than browser.**
