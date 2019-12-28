# How to Setup Google SSO

These instructions assume "https://example.com/" is the base URL for your NextCloud. Adjust according to your setup.

1. Install the app (see README.md)
2. Setup a Google Authorized Domain
    1. Go to Google Webmaster Tools: https://www.google.com/webmasters/tools/
    2. Click "Add Property" and enter the full domain that your NextCloud will be accessible at, e.g. example.com
    3. Optionally verify your site.
        1. Verify your site ownership via the various methods shown here https://support.google.com/webmasters/answer/9008080?visit_id=637032436952938937-2175615075&rd=1
        2. Wait a while.
        3. Confirm that in Google Webmaster Tools you see the property (i.e. domain) you've entered is verified
3. Setup a Google app
    1. Go to https://console.developers.google.com/
    2. Click the list of projects at the top then "New project"
    3. Enter whatever project name you want, no need to specify an org; click "Create"
    4. Click "Create credentials" then click "OAuth client ID"
    5. Click "Configure consent screen"
    6. Enter values:
        * Application name: something relevant
        * Authorized domains: example.com (then hit ENTER; see setup of authorized domains if there's an error)
    7. Click "Save"
    8. Click "Web application" for application type and enter values:
        * Name: something relevant
        * Authorized JavaScript origins: https://example.com (then hit ENTER; see setup of authorized domains if there's an error)
        * Authorized redirect URIs: https://example.com/apps/sociallogin/oauth/google (then hit ENTER; see setup of authorized domains if there's an error)
    9. Click "Save"
    10. You should see a "client ID" and "client secret"; store these somewhere safe
4. Configure "Social Login" in NextCloud
    1. Click "Settings" in the menu
    2. Under Administration click "Social login"
    3. In the bottom section under "Google" enter:
        * App id: the "client ID" provided earlier
        * Secret: the "client secret" provided earlier
        * Default group: Select the group you want users to be assigned
    4. Click "Save" at the very bottom
5. Now, open up a new browser to test the login. On the login screen you should now see "Google" underneath the typical NextCloud login prompt.
