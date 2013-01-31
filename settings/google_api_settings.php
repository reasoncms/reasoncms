<?php
/**
 * This file setups the following:
 *
 * - Google API settings for various Google services
 * 
 * Currently in use -- Google Analytics
 *
 * Note that PHP 5.3 is required if you set USE_GOOGLE_ANALYTICS to true.
 */

/**
 * Path to the Google API php library.
 */
define('GOOGLE_API_INC', INCLUDE_PATH.'google-api-php-client/src/');

/**
 * USE_GOOGLE_ANALYTICS 
 * default = false
 * Set to true to display Google Analytics in the Admin Consoleb
 * 
 * Daily courtesy limit is 50,000 request/day (1/21/2013)
 */
domain_define('USE_GOOGLE_ANALYTICS', false);


/**
 * Setting up your Google Analytics App
 * 
 * Login to the Google API Console  (code.google.com/apis/console/)
 *   1) Create a new project
 *       1.1) Set GOOGLE_API_APP_NAME below with the name of your project
 *   2) In the Services tab, enable Google Analytics
 *   3) Click "API Access", then "Create a new OAuth 2.0 client ID..." 
 *      using the big blue button.
 *       3.1) Enter the requested information, click "Next"
 *              (logo & homepage are not required) 
 *       3.2) Select Service account, click "Create client ID"
 *       3.3) Download your private key 
 *              and close the "Public-Private Key Pair Generated" message box
 *   4) In the Service account section, copy the Client ID 
 *          (#########.apps.googleusercontent.com)
 *       4.1) Set GOOGLE_API_SERVICE_CLIENT_ID below
 *   5) In the Service account section, copy the Email address 
 *          (#########@developer.gserviceaccount.com)
 *       5.1) Set GOOGLE_API_SERVICE_EMAIL below
 *   6) Upload your private key file to your ReasonCMS server
 *       6.1) Set the GOOGLE_API_PRIVATE_KEY_FILE path below
 * 
 * Login to Google Analytics (google.com/analytics)
 *   7) Click "Admin"
 *   8) Click the Account 
 *        8.1) Copy the Account ID
 *        8.2) Set GOOGLE_ANALYTICS_ACCOUNT_ID below
 *   9) Click the property (e.g. your website)
 *        9.1) Copy the Property ID
 *        9.2) Set GOOGLE_ANALYTICS_PROPERTY_ID below
 *   10) Choose your Profile (choose your default profile)
 *       10.1) In the "Profile Settings" tab, copy the "Profile ID"
 *       10.2) set the GOOGLE_ANALYTICS_PROFILE_ID below
 *   11) In the "Users" tab
 *       11.1) Click a "+ New User"
 *           11.1.1) Select "Create a new user..."
 *           11.1.2) Set "Email Address" to match GOOGLE_ANALYTICS_SERVICE_EMAIL)
 *           11.1.3) Select role as "User"
 *       11.2)Click "Add User" to finish
 * 
 */



if ( USE_GOOGLE_ANALYTICS )
{
    /**
    * Google Analytics Service Account Info.
    * code.google.com/apis/console
    * See instructions 1-6 above
    */
    /*  Name of your Google API Project
        See instruction 1 above         */
    domain_define('GOOGLE_API_APP_NAME', '');

    /*  The Client ID of your Google API Project's Service Account
        See instruction 4 above          */
    domain_define('GOOGLE_API_SERVICE_CLIENT_ID', '' );

    /*  The email address of your Google API Project's Service Account
        See instruction 5 above          */
    domain_define('GOOGLE_API_SERVICE_EMAIL', '' );

    /*  The path to your private key file from your Google API Project
        See instruction 6 above         */
    domain_define('GOOGLE_API_PRIVATE_KEY_FILE', '');

    

    /**
     * Google Analytics Info
     * google.com/analytics -> Admin -> Profile Settings -> Profile ID
     * See instructions 7-11 above
     */
    /*  Your Google Analytics Account ID
        See instructions 8 above        */
    domain_define( 'GOOGLE_ANALYTICS_ACCOUNT_ID', '');

    /*  Your Google Analytics Property ID
        See instructions 9 above        */
    domain_define( 'GOOGLE_ANALYTICS_PROPERTY_ID', '');

    /*  Your Google Analytics Profile ID
        See instructions 10 above        */
    domain_define( 'GOOGLE_ANALYTICS_PROFILE_ID', '');

    ////////////////////////////////////////////////////////
    // Other Settings
    ////////////////////////////////////////////////////////
    /**
     * Your Google Analytics administrator/expert
     */
    domain_define('REASON_CONTACT_INFO_FOR_ANALYTICS', '<a href="mailto:ga_expert@yourdomain.edu">Your GA Expert</a>');

    /** 
     * The service provider name for your domain
     *      e.g some college
     * Used to filter on-campus traffic (dimension=ga:networkLocation!='some\ college')
     */
    domain_define('GA_SERVICE_PROVIDER_NAME', '');

    /**
     * The hostname for your Reason CMS server
     * Change to your production server for testing in a development environment
     * e.g. wwww.yourdomain.edu
     *
     * If you are not getting any results from google analytics, try setting this to an empty string.
     */
    domain_define('GA_HOST_NAME', HTTP_HOST_NAME);
}