# How to Setup Facebook SSO

These instructions assume "https://example.com/" is the base URL for your NextCloud. Adjust according to your setup.

1. Install the app (see README.md)
2. Create a Facebook App
    1. Go to https://developers.facebook.com/
    2. Click "Create App"
    3. Enter something relevant for the name
    4. Go to "Basic" under "Settings" and enter relevant information:
        * Display Name: NextCloud Login
        * App Domains: https://example.com/
        * Privacy Policy URL: https://example.com/privacy (this page doesn't need to exist yet)
        * Website: https://example.com
    5. Click "Save changes"
    6. Click the "+" next to Products and add the "Facebook Login"
    7. Go to "Settings" under "Facebook Login" in the sidebar
        * Client OAuth Login: enable this
        * Web OAuth Login: enable this
        * Enforce HTTPS: enable this
        * Valid OAuth Redirect URIs: https://example.com/apps/sociallogin/oauth/facebook
    7. Click "Save"
    8. Go back to "Basic" under "Settings" and you should see an App ID and App Secret; store these somewhere safe
3. Configure "Social Login" in NextCloud
    1. Login as an admin to your NextCloud
    2. Click "Settings" in the menu
    3. Under Administration click "Social login"
    4. In the bottom section under "Facebook" enter:
        * App id: the "App ID" provided earlier
        * Secret: the "App Secret" provided earlier
        * Default group: Select the group you want users to be assigned
    5. Click "Save" at the very bottom
5. Now, open up a new browser to test the login. On the login screen you should now see "Facebook" underneath the typical NextCloud login prompt.

