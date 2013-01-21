<?php
/**
 * This file setups the following:
 *
 * - Google API keys for various Google services
 * 
 * Currently in use -- Google Analytics
 *
 */

/**
 * Path to our Google API php library.
 */
define('GOOGLE_API_INC', INCLUDE_PATH.'google-api-php-client/src/');

/**
 * USE_GOOGLE_ANALYTICS default = false
 * Set to true to display Google Analytics in the Admin Console
 * 
 * Daily courtesy limit is 50,000 request/day (1/21/2013)
 */
domain_define('USE_GOOGLE_ANALYTICS', false);


/**
 * Setting up your Google Analytics App
 * 
 * Login to the Google API Console  (code.google.com/apis/console/)
 *   1) Create a new project
 *       1.1) Set GOOGLE_ANALYTICS_APP_NAME below
 *   2) In the Services tab, enable Google Analytics
 *   3) Click API Access and create a new OAuth 2.0 client ID using the big blue button.
 *       3.1) Enter the requested information (logo is not required) and click next
 *       3.2) Select Service account and click Create client ID
 *       3.3) Download your private key
 *       3.4) Close the Public-Private Key Pair Generated floating message box
 *   4) In the Service account section, copy the Email address (#########.apps.googleusercontent.com)
 *       4.1) Set GOOGLE_ANALYTICS_SERVICE_CLIENT_ID below
 *   5) In the Service account section, copy the Email address (#########@developer.gserviceaccount.com)
 *       5.1) Set GOOGLE_ANALYTICS_SERVICE_EMAIL below
 *   6) Upload your private key file to your ReasonCMS server
 *       6.1) Set the GOOGLE_ANALYTICS_PRIVATE_KEY_FILE path below
 * 
 * Login to Google Analytics (google.com/Analytics)
 *   7) Go to Profile for your account
 *   8) Click Admin -> Choose the Account -> Choose the property (e.g. your website) -> Profile (choose your default profile)
 *   9) In the Profile Settings tab, copy the Profile ID
 *       9.2) set the GOOGLE_ANALYTICS_PROFILE_ID below
 *   10) In the Users tab
 *       10.1) Add a new user 
 *           10.1.1) Email address should be the same one you used for GOOGLE_ANALYTICS_SERVICE_EMAIL)
 *           10.1.1) Set role to User
 *       10.2) Add profile access to the entire site
 *       10.3) Save the User
 * 
 * 
 * 
 */

if ( USE_GOOGLE_ANALYTICS )
{
    /**
    * Google Analytics Service Account Info.
    * code.google.com/apis/console
    * See instructions 1-6 above
    */
    /* See instruction 1 above */
    domain_define('GOOGLE_ANALYTICS_APP_NAME', '');

    /* See instruction 4 above */
    domain_define('GOOGLE_ANALYTICS_SERVICE_CLIENT_ID', '' );

    /* See instruction 5 above */
    domain_define('GOOGLE_ANALYTICS_SERVICE_EMAIL', '' );

    /* See instruction 6 above */
    domain_define('GOOGLE_ANALYTICS_PRIVATE_KEY_FILE', '');

    

    /**
     * Google Analytics Profile 
     * google.com/analytics -> Admin -> Profile Settings -> Profile ID
     * See instructions 7-10 above
     */
    domain_define( 'GOOGLE_ANALYTICS_PROFILE_ID', '');

    ////////////////////////////////////////////////////////
    // Other Settings
    ////////////////////////////////////////////////////////
    /**
     * Your Google Analytics administrator/expert
     */
    domain_define('REASON_CONTACT_INFO_FOR_ANALYTICS', '<a href="mailto:ga_expert@yourdomain.edu">Your GA Expert</a>');

    /** 
     * the service provider name for your domain
     *      e.g carelton college
     * Used to filter on-campus traffic (dimension=ga:networkLocation!='carleton\ college')
     */
    domain_define('GA_SERVICE_PROVIDER_NAME', '');

    /**
     * The hostname for your ReasonCMS server
     * Change to your production server for testing in a development environment
     */
    domain_define('GA_HOST_NAME', HTTP_HOST_NAME);
}