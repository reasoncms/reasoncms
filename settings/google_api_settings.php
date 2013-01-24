<?php
/**
 * This file setups the following:
 *
 * - Google API settings for various Google services
 * 
 * Currently in use -- Google Analytics
 *
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
domain_define('USE_GOOGLE_ANALYTICS', true);


/**
 * Setting up your Google Analytics App
 * 
 * Login to the Google API Console  (code.google.com/apis/console/)
 *   1) Create a new project
 *       1.1) Set GOOGLE_ANALYTICS_APP_NAME below with the name of your project
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
 *       4.1) Set GOOGLE_ANALYTICS_SERVICE_CLIENT_ID below
 *   5) In the Service account section, copy the Email address 
 *          (#########@developer.gserviceaccount.com)
 *       5.1) Set GOOGLE_ANALYTICS_SERVICE_EMAIL below
 *   6) Upload your private key file to your ReasonCMS server
 *       6.1) Set the GOOGLE_ANALYTICS_PRIVATE_KEY_FILE path below
 * 
 * Login to Google Analytics (google.com/Analytics)
 *   7) Go to Profile for your account
 *   8) Click "Admin" -> 
 *          Choose the Account -> 
 *          Choose the property (e.g. your website) -> 
 *          Choose your Profile (choose your default profile)
 *   9) In the "Profile Settings" tab, copy the "Profile ID"
 *       9.2) set the GOOGLE_ANALYTICS_PROFILE_ID below
 *   10) In the "Users" tab
 *       10.1) Click a "+ New User"
 *           10.1.1) Select "Create a new user..."
 *           10.1.2) Set "Email Address" to match GOOGLE_ANALYTICS_SERVICE_EMAIL)
 *           10.1.3) Select role as "User"
 *       10.3)Click "Add User" to finish
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
    /*  Name of your Google API Project
        See instruction 1 above         */
    domain_define('GOOGLE_ANALYTICS_APP_NAME', 'ReasonGoogleAnalytics');

    /*  The Client ID of your Google API Project's Service Account
        See instruction 4 above          */
    domain_define('GOOGLE_ANALYTICS_SERVICE_CLIENT_ID', '437862994053-4lecueqf6u1rhk9r99pavrnck66dp5n1.apps.googleusercontent.com' );

    /*  The email address of your Google API Project's Service Account
        See instruction 5 above          */
    domain_define('GOOGLE_ANALYTICS_SERVICE_EMAIL', '437862994053-4lecueqf6u1rhk9r99pavrnck66dp5n1@developer.gserviceaccount.com' );

    /*  The path to your private key file from your Google API Project
        See instruction 6 above         */
    domain_define('GOOGLE_ANALYTICS_PRIVATE_KEY_FILE', '/var/reason/htdoc/7bae3a25fc9ddbf6d572cd0ddc4b098bdb7c544e-privatekey.p12');

    

    /**
     * Google Analytics Profile 
     * google.com/analytics -> Admin -> Profile Settings -> Profile ID
     * See instructions 7-10 above
     */
    /*  Your Google Analytics Profile ID
        See instructions 9 above        */
    domain_define( 'GOOGLE_ANALYTICS_PROFILE_ID', '6696281');

    ////////////////////////////////////////////////////////
    // Other Settings
    ////////////////////////////////////////////////////////
    /**
     * Your Google Analytics administrator/expert
     */
    domain_define('REASON_CONTACT_INFO_FOR_ANALYTICS', '<a href="mailto:greeta01@luther.edu">Tabita Green</a>');

    /** 
     * The service provider name for your domain
     *      e.g carelton college
     * Used to filter on-campus traffic (dimension=ga:networkLocation!='carleton\ college')
     */
    domain_define('GA_SERVICE_PROVIDER_NAME', 'luther college');

    /**
     * The hostname for your ReasonCMS server
     * Change to your production server for testing in a development environment
     * e.g. wwww.yourdomain.edu
     */
    domain_define('GA_HOST_NAME', 'www.luther.edu');
}