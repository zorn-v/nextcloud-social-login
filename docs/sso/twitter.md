# How to Setup Twitter SSO

These instructions assume "https://example.com/" is the base URL for your NextCloud. Adjust according to your setup.

1. Install the Social login app in your NextCloud (see README.md)
2. Apply for becoming a Twitter App Developer
    1. Go to https://developer.twitter.com/
    2. Click "Apply" for a developer account
    3. Fill out relevant information
        * You'll need to provide a valid phone number and confirm it
        * Under "The specifics" when it asks you what your intent is, explain it is for NextCloud and then uncheck each box appropriately. If you're just using this for NextCloud you can uncheck all boxes since this will just be for SSO, it won't be analyzing Twitter data or generating content.
    4. Submit your application.
    5. Confirm your email.
    6. Wait for your application to be approved
3. Create an application
    1. Go to https://developer.twitter.com/
    2. Click "Create an app"
    3. Fill out relevant information:
        * Website URL: https://example.com/
        * Enable Sign In with Twitter: Check this box
        * Callback URLs: https://example.com/apps/sociallogin/oauth/twitter
        * Organization website URL: https://example.com/
    4. Click "Save"
    5. Go to the "Keys and tokens tab"; here you should see a section "Consumer API keys", store these somewhere safe
4. Configure "Social Login" in NextCloud
    1. Click "Settings" in the menu
    2. Under Administration click "Social login"
    3. In the bottom section under "Twitter" enter:
        * App id: the "API key" provided earlier
        * Secret: the "API secret key" provided earlier
        * Default group: Select the group you want users to be assigned
    4. Click "Save" at the very bottom
5. Now, open up a new browser to test the login. On the login screen you should now see "Twitter" underneath the typical NextCloud login prompt.

