<?php
/**
 * The twitter API v1.1 requires (annoyingly) all requests to be authenticated.
 *
 * This file should be configured so that Reason CMS modules may access API v1.1 methods.
 *
 * Note that at this time we support only a single-user use case, appropriate for modules that
 * want to do simple things such as displaying public tweets for a particular twitter username.
 *
 * More info on such a setup can be found here:
 * https://dev.twitter.com/docs/auth/oauth/single-user-with-examples
 *
 * To get started, you'll need to have a twitter account, and then create an application:
 *
 * 1. visit https://dev.twitter.com/apps
 * 2. click on "Create a New Application"
 * 3. fill out the relevant fields sensibly.
 * 4. click "Create my access token"
 *
 * You should now have the following information, which you should define in this file:
 *
 * - Consumer Key
 * - Consumer Secret
 * - Access Token
 * - Access Token Secret
 */

/**
 * Path to the the tmhOAuth library
 */
define('TMHOAUTH_INC', INCLUDE_PATH.'tmhOAuth/');

/**
 * Consumer Key for the application you created at dev.twitter.com/apps
 */
define('TWITTER_API_CONSUMER_KEY', "vWjklXeEfd9tl1AHtk8mw");

/**
 * Consumer Secret for the application you created at dev.twitter.com/apps
 */
define('TWITTER_API_CONSUMER_SECRET', "ru099hRMC6nqPZYSHV9QqGZqNSJDKOIUHPVq5rhygH4");

/**
 * Access Token for the application you created at dev.twitter.com/apps
 */
define('TWITTER_API_ACCESS_TOKEN', "1560760800-1ZIKakRE1Oa0OC9ARda72eHL1iYv625S2Mu98Ad");

/**
 * Access Token Secret for the application you created at dev.twitter.com/apps
 */
define('TWITTER_API_ACCESS_TOKEN_SECRET', "F65icKwlZm8PWNbwbQItroaEyY5NbiwOe89ARHBxPA");
