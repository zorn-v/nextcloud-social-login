You need an Apple developer account (https://developer.apple.com/)

* Create App under: Apple Developer > Account > Identifiers
    * "Register a new identifier"
        * Select APP IDs
        * Select type "App"
        * Register an App ID
            * Add description
            * Add Bundle ID exlicit, add id
            * Select "Sign in with Apple"
            * Click on "Continue"
            * Click on "Register"
            * Get the TEAM-ID from the details page of the APP-ID. You will need this as second element in the configuration page
        * Register a new identifier
            * Service IDs, click on '+'
            * Select "Service IDs", Continue
            * Give description and id and click on Continue
            * Click on Register to set
            * This gives you the first entry in the Configuratin page: Service-ID
* Apple Developer > Account > Keys
    * Click on "+"
    * Define a name and a description
    * Then select "Sign in with Apple" and select the defined APP-ID
    * Then configure the domains and redirect URIs
    * Select Continue and Register
    * Remember the Key-ID and download the key. Both are needed in the configuration page
