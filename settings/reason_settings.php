<?php
/**
 * Reason Settings
 *
 * This file contains all of the settings needed for Reason to function
 * (except for those in paths.php.) Many of these settings can/should be altered to match
 * the environment for this Reason instance.
 * @package reason
 */
 	include_once( 'paths.php');

	////////////////////////////////////////////////////////////
	//
	// Begin items you may need to change to get Reason up and running
	//
	// This area contains setting you will likely need to edit to run Reason
	//
	////////////////////////////////////////////////////////////

	/**
	 * REASON_HTTP_BASE_PATH
	 * This setting identifies the location of Reason's web-available directory from the web root
	 * This path should be an alias to the reason www folder, which should be
	 * located outside the web root.
	 *
	 * The location of the reason www folder is /reason_package/reason_4.0/www/
	 */
	define( 'REASON_HTTP_BASE_PATH','/reason/');

	/**
	 * REASON_GLOBAL_FEEDS_PATH
	 * This setting identifies the directory that global feeds will be placed in
	 * this should be relative to the web root.
	 */
	define( 'REASON_GLOBAL_FEEDS_PATH', 'global_feeds');

	/**
	 * APACHE_MIME_TYPES
	 * The location of Apache's mime.types file - used by asset access manager to determine
	 * what mime type to use when delivering reason managed assets
	 */
	define ( 'APACHE_MIME_TYPES', '/etc/mime.types' );

	/**
	 * REASON_DB
	 * This setting identifies connection name for the Reason database
	 * Actual credentials and database info are kept in a separate xml file
	 */
	define( 'REASON_DB', 'reason_connection' );

	/**
	 * REASON_SITE_DIRECTORY_OWNER
	 * the user group which site directories should belong to so that Reason can write .htaccess rules
	 * This should be the same as either the user or the group that Apache/php runs as
	 */
	define( 'REASON_SITE_DIRECTORY_OWNER', 'www' ); // replace this with the user/group that Apache runs as

	/*
	 NOTE: while it is not necessary to get Reason up and running, please note that when you are
	 ready to put Reason into production you will need to set THIS_IS_A_DEVELOPMENT_REASON_INSTANCE,
	 below, to *false* on your production instance. Reason prevents search engines
	 from indexing development instances (a good thing on a development instance, but a decidently
	 bad thing if it is inadvertently applied to a production instance!)
	 */

	////////////////////////////////////////////////////////////
	//
	// End items you may need to change to get Reason up and running
	//
	// You should be able to run reason without continuing further in this file
	//
	////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////
	//
	// Start things you may want to change at some point
	//
	// This section contains settings that may be useful to alter,
	// but a basic install of Reason should work OK without touching them.
	//
	////////////////////////////////////////////////////////////

	/**
	 * REASON_MAINTENANCE_MODE
	 * Set this to true during database maintenance or upgrades.
	 *
	 * When REASON_MAINTENANCE_MODE is true, users without the db_maintenance privilege
	 * will be unable to access the administrative interface, and modules should restrict
	 * database writes / updates.
	 */
	define( 'REASON_MAINTENANCE_MODE', false );

	/**
	 * REASON_DEFAULT_TIMEZONE
	 * PHP 5 will send warnings in E_STRICT mode if a default timezone is not set
	 *
	 * List of supported time zones: http://us2.php.net/manual/en/timezones.php
	 *
	 * In PHP 4, this setting will be ignored.
	 */
	define( 'REASON_DEFAULT_TIMEZONE', 'America/Chicago' );

	/**
	 * DISABLE_REASON_LOGIN
	 * Set this to true if you want to temporarily keep people from logging in to Reason
	 * false = normal -- people can log in
	 * true = shut down -- people cannot log in
	 * Boolean (e.g. true, false -- no quotes)
	 */
	define('DISABLE_REASON_LOGIN', false);

	/**
	 * DISABLE_REASON_ADMINISTRATIVE_INTERFACE
	 * Set this to true if you want to temporarily disable the reason administrative interface
	 * false = normal -- people can use the administrative interface
	 * true = shut down -- people cannot use the administrative interface
	 * Boolean (e.g. true, false -- no quotes)
	 */
	define('DISABLE_REASON_ADMINISTRATIVE_INTERFACE', false);

	/**
	 * ADMIN_NOTIFICATIONS_EMAIL_ADDRESSES
	 * Some code will send email to an administrator if it encounters an error.
	 * This email address (or email addresses) can be set here if different from
	 * WEBMASTER_EMAIL_ADDRESS
	 */
	define('ADMIN_NOTIFICATIONS_EMAIL_ADDRESSES',WEBMASTER_EMAIL_ADDRESS);

	/**
	 * THIS_IS_A_DEVELOPMENT_REASON_INSTANCE
	 *
	 * Identifies whether this copy of Reason is a testing/development instance of Reason
	 * or a production/live instance of Reason
	 *
	 * Boolean (e.g. true, false -- no quotes)
	 *
	 * true = testing/development
	 * false = production/live
	 *
	 * IMPORTANT: If this setting is true (the default value), Reason will hide all pages in this
	 * instance from search engines. This is based on the assumption that development and testing
	 * copies of Reason are not intended to be picked up by search engines and the like.
	 * In order for pages in your Reason instance to be visible to search engines, you *must*
	 * change this setting to false.
	 */
	define( 'THIS_IS_A_DEVELOPMENT_REASON_INSTANCE', true );

	/**
	 * REASON_HOST_HAS_VALID_SSL_CERTIFICATE
	 * Set this to true if you have a valid certificate signed by a known certificate authority.
	 * Otherwise set this to false (if you have a self-signed cert, for example).
	 *
	 * If this is set to true and you do not have a valid certificate, Reason will not
	 * be able to update .htaccess files.
	 *
	 * If this is set to false, Reason will work in either case, but it is better practice
	 * to set it to true when possible to eliminate the possiblity of man-in-the-middle attacks.
	 */
	define('REASON_HOST_HAS_VALID_SSL_CERTIFICATE', false);

	/**
	 * REASON_SESSION_TIMEOUT
	 *
	 * Determines how long a Reason session lasts, in minutes.
	 */
	define('REASON_SESSION_TIMEOUT', 60);

	/**
	 * REASON_SESSION_TIMEOUT_WARNING
	 *
	 * Determines how long before the end of a Reason session the user is notified, in minutes.
	 */
	define('REASON_SESSION_TIMEOUT_WARNING', 5);

	/**
	 * REASON_DEFAULT_HTML_EDITOR
	 *
	 * The name of the editor Reason sites use by default.
	 * This can be overridden in the Master Admin.
	 * Note that this is the same as the name of the file in lib/[core|local]/html_editors,
	 * but without the ".php"
	 */
	define('REASON_DEFAULT_HTML_EDITOR', 'tiny_mce');

	/**
	 * REASON_URL_FOR_GENERAL_FEED_HELP
	 * Identifies a URI where users can get more information about using feeds
	 * Comment out this line if you don't want to provide a link for feed assistance
	 * (Developer note: surround any use of this constant in a if(defined()) block)
	 */
	//define( 'REASON_URL_FOR_GENERAL_FEED_HELP', 'http://www.domain_name.domain/your/path/here/' );

	/**
	 * REASON_URL_FOR_PODCAST_HELP
	 * Identifies a URI where users can get more information about subscribing to podcasts
	 * Comment out this line if you don't want to provide a link for feed assistance
	 * (Developer note: surround any use of this constant in a if(defined()) block)
	 */
	//define( 'REASON_URL_FOR_PODCAST_HELP', 'http://www.domain_name.domain/your/path/here/' );

	/**
	 * REASON_URL_FOR_ICAL_FEED_HELP
	 * Identifies a URI where users can get more information about using iCal feeds
	 * Comment out this line if you don't want to provide a link for feed assistance
	 * (Developer note: surround any use of this constant in a if(defined()) block)
	 */
	//define( 'REASON_URL_FOR_ICAL_FEED_HELP', 'http://www.domain_name.domain/your/path/here/' );

	/**
	 * REASON_LOGIN_PATH
	 * This setting identifies the location of the login site relative to the server root directory/
	 * Reason uses this to make links to log in and to header in the cases of secured content
	 */
	define( 'REASON_LOGIN_PATH', 'login/' );

	define ('ACCESS_LOG_USER_AGENT_REGEX_FILTER', '(?!htdig|msnbot|psbot|NaverBot|Gigabot|sohu|YahooSeeker|.*Googlebot|.*ZyBorg|.*Slurp|.*Jeeves\/Teoma)' );

	/**
	 * REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS
	 * A snippet of XHTML that informs people how they can have the user permissions changed for their site
	 *  -- used on the view users backend module
	 */
	define('REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS', '<a href="mailto:'.WEBMASTER_EMAIL_ADDRESS.'">'.WEBMASTER_NAME.'</a>');
	/**
	 * ERROR_404_PATH
	 * This setting identifies the location of the 404 page relative to the server root directory/
	 * It should not have an initial slash.
	 * Example: errors/404.php
	 */
	define('ERROR_404_PATH', 'errors/404.php');

	/**
	 * ERROR_404_PAGE
	 * This setting identifies the URI of the 404 page
	 * It will typically look like this: 'http://'.HTTP_HOST_NAME.'/'.ERROR_404_PATH
	 * Reason uses this to header when a query-string-based resource is not available
	 * You should also set the Apache 404 page to this path
	 * This page is important, as it handles redirection for moved Reason pages
	 * If you want a custom 404 page, you must add these lines to the very top of the 404 page file:
	 * include_once( 'reason_header.php' );
   	 * reason_include( 'scripts/urls/404action.php' );
	 */
	define( 'ERROR_404_PAGE', 'http://'.HTTP_HOST_NAME.'/'.ERROR_404_PATH );
	// Use this for the release package

	/**
	 * ERROR_403_PATH
	 * This setting identifies the location of the 403 page relative to the server root directory/
	 * It should not have an initial slash.
	 * Example: errors/403.php
	 */
	define('ERROR_403_PATH', 'errors/403.php');

	/**
	 * ERROR_404_PAGE
	 * This setting identifies the URI of the 403 page
	 * It will typically look like this:
	 * 'http://'.HTTP_HOST_NAME.'/'.ERROR_403_PATH
	 */
	define( 'ERROR_403_PAGE', 'http://'.HTTP_HOST_NAME.'/'.ERROR_403_PATH );

	/**
	 * ALLOW_REASON_SITES_TO_SWITCH_THEMES
	 * Set this to true if you want sites to be able to switch themes (note that
	 * you can still turn off theme switching on a site-by-site basis)
	 * Set this to false if you only want to set site themes in the Master Admin area
	 * Boolean (e.g. true, false -- no quotes)
	 */
	define( 'ALLOW_REASON_SITES_TO_SWITCH_THEMES', true );

	/**
	 * REASON_PREVIOUS_HOSTS
	 * comma-separated list of hosts.  No spaces.
	 * This is used to identify links to reason resources that point to a previous host
	 * If this is the first server this Reason instance has been on, you can leave this string empty
	 */
	define( 'REASON_PREVIOUS_HOSTS', '' );

	/**
	 * REASON_SEARCH_ENGINE_URL
	 *
	 * The url of the search engine to use for searching Reason sites.
	 * To disable the search module, comment out this line or set its value to an empty string.
	 * (e.g. for Google, use the string 'http://www.google.com/search'.)
	 */
	define('REASON_SEARCH_ENGINE_URL','http://www.google.com/search');

	/**
	 * REASON_SEARCH_FORM_METHOD
	 *
	 * The method that the search module's form uses.
	 * Shoud be 'get' or 'post', depending on what the search engine you are using wants.
	 */
	define('REASON_SEARCH_FORM_METHOD','get');

	/**
	 * REASON_SEARCH_FORM_INPUT_FIELD_NAME
	 *
	 * The name of the field that contains the search string in the search module.
	 * This should not contain any double quotes.
	 * This should be set to be the request key that the search engine expects
	 * (e.g. for Google, use the string 'q'.)
	 */
	define('REASON_SEARCH_FORM_INPUT_FIELD_NAME','q');

	/**
	 * REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME
	 *
	 * The name of the field that contains the URI of the site so that the
	 * search engine can restrict its results to the site being searched.
	 * This should not contain any double quotes.
	 * This should be set to be the request key that the search engine expects
	 * (e.g. for Google, use the string 'as_sitesearch'.)
	 */
	define('REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME','as_sitesearch');

	/**
	 * REASON_SEARCH_FORM_HIDDEN_FIELDS
	 *
	 * A chunk of xhtml that gets inserted into the search module.
	 * This allows you to pass the search engine any other values you want.
	 * Example: '<input type="hidden" name="method" value="and" />'
	 */
	define('REASON_SEARCH_FORM_HIDDEN_FIELDS','');

	/**
	 * REASON_ADMIN_LOGO_MARKUP
	 * A snippet of XHTML that you can use to customize the Reason admin banner
	 */
	define('REASON_ADMIN_LOGO_MARKUP','<a href="?">Reason at '.FULL_ORGANIZATION_NAME.'</a>');

	/**
	 * REASON_TAGLINE
	 * A short sentence that serves as a tagline in the Reason admin banner
	 */
	define('REASON_TAGLINE','Web Administration For '.FULL_ORGANIZATION_NAME);

	/**
	 * MEDIA_POPUP_TEMPLATE_FILENAME
	 * The name of the file in lib/xxx/popup_templates/ to use to generate the media popup markup
	 */
	define('MEDIA_POPUP_TEMPLATE_FILENAME','generic_media_popup_template.php');

	/**
	 * IMAGE_POPUP_TEMPLATE_FILENAME
	 * The name of the file in lib/xxx/popup_templates/ to use to generate the image popup markup
	 */
	define('IMAGE_POPUP_TEMPLATE_FILENAME','generic_image_popup_template.php');

	/**
	 * REASON_STANDARD_MAX_IMAGE_HEIGHT
	 *
	 * When images are uploaded to Reason, they are resized automatically.
	 * This setting determines their maximum vertical size in pixels
	 */
	define('REASON_STANDARD_MAX_IMAGE_HEIGHT', 500);

	/**
	 * REASON_STANDARD_MAX_IMAGE_WIDTH
	 *
	 * When images are uploaded to Reason, they are resized automatically.
	 * This setting determines their maximum horizontal size in pixels
	 */
	define('REASON_STANDARD_MAX_IMAGE_WIDTH', 500);

	/**
	 * REASON_STANDARD_MAX_THUMBNAIL_HEIGHT
	 *
	 * When images are uploaded to Reason, thumbnails are automatically created.
	 * This setting determines their maximum vertical size in pixels
	 */
	define('REASON_STANDARD_MAX_THUMBNAIL_HEIGHT', 125);

	/**
	 * REASON_STANDARD_MAX_THUMBNAIL_WIDTH
	 *
	 * When images are uploaded to Reason, thumbnails are automatically created.
	 * This setting determines their maximum horizontal size in pixels
	 */
	define('REASON_STANDARD_MAX_THUMBNAIL_WIDTH', 125);

	/**
	 * Set custom auto thumbnail sizes on a site-by-site basis using this array
	 *
	 * $GLOBALS['_reason_site_custom_thumbnail_sizes'] = array(
	 *		'site_unique_name'=>array('height'=>250,'width'=>200),
	 *		'other_site_unique_name'=>array('height'=>200,'width'=>175),
	 *	);
	 *
	 */
	$GLOBALS['_reason_site_custom_thumbnail_sizes'] = array();

	/**
	 * REASON_USES_DISTRIBUTED_AUDIENCE_MODEL
	 *
	 * Reason has two basic modes for how audiences are handled: "Unified" and "Distributed."
	 *
	 * In the "Unified" model, there is a single set of audiences, which all Reason sites share.
	 * The "Unified" model is appropriate for instances of Reason where all subsites belong to the same
	 * organization, whose various subsites and subgroups all share a standard set of audiences.
	 *
	 * In the "Distributed" model, each site manages its own set of audiences
	 * (and they can be borrowes/shared as usual.) The "Distributed" model is appropriate for
	 * instances of Reason shared by multiple organizations or units who have different sets of primary audiences.
	 *
	 * For the "unified" model, set this constant to false.
	 * For the "distributed" model, set this constant to true.
	 *
	 */
	define('REASON_USES_DISTRIBUTED_AUDIENCE_MODEL', false);

	/**
	 * REASON_USERS_DEFAULT_TO_AUTHORITATIVE
	 *
	 * This constant defines the default value for the user_authoritative_source
	 * field on the user type. If it is true, when a new user is created the
	 * user_authoritative_source field will be set to "reason".
	 * If it is false, when a user is created the user_authoritative_source field will be set to "external".
	 *
	 * In plain(ish) English, set this constant to TRUE if your instance if Reason
	 * is not integrated with other, more authoritative directory services.
	 * In a base install, this will be set to TRUE.
	 *
	 * If your instance of Reason is integrated with an authoritative directory service,
	 * and user entries in Reason mainly serve as stubs, set this constant to FALSE.
	 *
	 * Of course, the user_authoritative_source field can be edited on a user-by-user
	 * basis in a mixed environment; this constant simply sets the default value.
	 */
	define('REASON_USERS_DEFAULT_TO_AUTHORITATIVE', true);

	/**
	 * REASON_ALLOWS_INLINE_EDITING
	 *
	 * This constant determines whether a Reason instance exposes inline editing features to users with
	 * proper privileges (determined on a module to module basis). It defaults to true and should be
	 * left as true to take advantage of inline editing features provided by Reason modules.
	 */
	define('REASON_ALLOWS_INLINE_EDITING', true);

	////////////////////////////////////////////////////////////
	//
	// End things you may want to change at some point
	//
	////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////
	//
	// Start things your probably *DON'T* want to change
	//
	////////////////////////////////////////////////////////////

	/**
	 * REASON_DATA_DIR
	 * This setting identifies the filesystem location of the reason data directory
	 * This needs to be readable / writable by Apache/php but should not be web accessible.
	 */
	define( 'REASON_DATA_DIR', REASON_INC.'data/' );

	/**
	 * REASON_CSV_DIR
	 * This setting identifies the filesystem location of the reason-managed csv data.
	 */
	define( 'REASON_CSV_DIR', REASON_DATA_DIR.'csv_data/' );

	/**
	 * PHOTOSTOCK
	 * This setting identifies the filesystem location of the reason-managed images directory.
	 */
	define( 'PHOTOSTOCK', REASON_DATA_DIR.'images/' );

	/**
	 * WEB_PHOTOSTOCK
	 * This setting identifies the web path for reason-managed images
	 * This should be relative to the server (e.g. don't include the domain name here)
	 */
	define( 'WEB_PHOTOSTOCK', REASON_HTTP_BASE_PATH.'images/' );

	/**
	 * REASON_IMAGE_GRAVEYARD
	 * This setting identifies the filesystem location for expunged images.
	 * If it is empty, expounged images will be deleted.
	 */
	define( 'REASON_IMAGE_GRAVEYARD', '');

	/**
	 * REASON_TEMP_DIR
	 * This setting defines the location for Reason temporary data
	 */
	define( 'REASON_TEMP_DIR', REASON_DATA_DIR.'tmp/' );

	/**
	 * REASON_LOG_DIR
	 * This setting defines the location for Reason to log information about its activity
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'REASON_LOG_DIR', REASON_DATA_DIR.'logs/' );

	/**
	 * PAGE_CACHE_LOG
	 * This setting defines the location for Reason to log information about page caching performance.
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'PAGE_CACHE_LOG', REASON_LOG_DIR.'page_cache_log' );

	/**
	 * REASON_PATH
	 * This setting identifies the filesystem location of the Reason codebase
	 * It should be the same as the constant REASON_INC defined in paths.php
	 */
	define( 'REASON_PATH',REASON_INC);

	/**
	 * REASON_HOST
	 * This setting identifies the http host name (e.g. www.foo.com)
	 * It should be the same as the constant HTTP_HOST_NAME defined in paths.php
	 * All scripts should use this or HTTP_HOST_NAME rather than HTTP_HOST or
	 * other server-defined variables, so that scripts can be run from the command line.
	 */
	define( 'REASON_HOST',HTTP_HOST_NAME);

	/**
	 * WEB_TEMP
	 * This setting defines a web-available temporary directory.
	 * It is used to store uploads temporarily while error checking is resolved.
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'WEB_TEMP', REASON_HTTP_BASE_PATH.'tmp/' );

	/**
	 * REASON_CACHE_DIR
	 * This setting identifies the directory used to store cache files.
	 * This directory will have to have permissions that allow Apache/php to write to it
	 */
	define( 'REASON_CACHE_DIR', REASON_DATA_DIR.'cache/' );

	/**
	 * CM_VAR_PREFIX
	 * The prefix prepended to content manager variables when an associated entity is created.
	 */
	define( 'CM_VAR_PREFIX', '__old_' );

	/**
	 * ASSET_PATH
	 * The filesystem path to the directory that contains reason-managed files
	 */
	define( 'ASSET_PATH', REASON_DATA_DIR.'assets/' );

	/**
	 * REASON_ASSET_MAX_UPLOAD_SIZE_MEGS
	 * The largest size that uploaded Reason assets can be, in megabytes.
	 * Note that Reason will use the smallest of these three values:
	 * post_max_size in php.ini, upload_max_filesize in php.ini, and this setting.
	 */
	define( 'REASON_ASSET_MAX_UPLOAD_SIZE_MEGS',  50 );

	/**
	 * MINISITE_ASSETS_DIRECTORY_NAME
	 * This setting defines the assets directory vis-a-vis a site's base directory
	 * So a site at /foo/bar/ will have its assets available at /foo/bar/this_string/
	 * This directory name should be defined with no slashes
	 * Note that changing this directory name could break links to assets if this is an existing instance of Reason
	 */
	 define( 'MINISITE_ASSETS_DIRECTORY_NAME',  'assets' );

	/**
	 * MINISITE_FEED_DIRECTORY_NAME
	 * This setting defines the feeds directory vis-a-vis a site's base directory
	 * So a site at /foo/bar/ will have its feeds live at /foo/bar/this_string/
	 * This path should be defined with slashes before and after, a la "/feeds/"
	 * Note that changing this directory name could break links to feeds if this is an existing instance of Reason
	 */
	define( 'MINISITE_FEED_DIRECTORY_NAME', 'feeds' );

	/**
	 * FEED_GENERATOR_STUB_PATH
	 * This setting identifies the location of the feed generation script
	 * The url manager uses this setting to create the feed rewrite rules
	 */
	define( 'FEED_GENERATOR_STUB_PATH',REASON_HTTP_BASE_PATH.'displayers/generate_feed.php' );


	/**
	* PUBLICATION_FEED_DEFAULT_TO_CONTENT
	* This setting identifies whether the blog feed will default to using the content field of a post for the RSS description
	* If set to true, content will be used, if set to false, description will be used
	* The blog content manager uses this variable to determine the initial setting of a new blog
	*/
	define( 'PUBLICATION_FEED_DEFAULT_TO_CONTENT', false);

	/**
	* PUBLICATION_HIDE_FEED_DESCRIPTION_CHECKBOX
	* The feed description checkbox allows editors to decide whether a blog will use content or description
	* from a blog post for the RSS description field, if this is false this checkbox will be displayed
	* If true no checkbox will be shown and the feed will simply use the previous value or default
	* The blog content manager uses this variable
	*/
	define( 'PUBLICATION_HIDE_FEED_DESCRIPTION_CHECKBOX', false);

	/**
	* PUBLICATION_REMINDER_CRON_SET_UP
	* This setting allows users to add reminder days and emails to a publication.
	* If no post has been added in reminder_days, an email will be sent to reminder_emails
	* If this is true, these options will be available. If false, they will not and no reminders will occur.
	* The blog content manager uses this variable.
	* Add this to the crontab:
	*
	* 00 00 * * * user path/to/php/bin/php -d include_path=/path/to/reason_package path/to/reason_package/reason_4.0/lib/core/scripts/news/tickler_many.php
 	*
	* In order to begin the sending of reminders.
	* (user is the username; the first 00 is the minute of the day to send at, the second 00 is the hour, so 30 09 would be 9:30 in the morning)
	*/
	define( 'PUBLICATION_REMINDER_CRON_SET_UP', false);

	/**
	* PUBLICATION_SOCIAL_SHARING_DEFAULT
	* This setting identifies whether the social sharing on the publication content manager will default to on or off
	* If set to true, it will default to on, if set to false, it will default to off
	* The blog content manager uses this variable to determine the initial setting for a new blog
	*/
	define( 'PUBLICATION_SOCIAL_SHARING_DEFAULT', false);

	/**
	 * REASON_WEB_ADMIN_PATH
	 * This setting identifies the location of the Reason admin area
	 * It should be in the form of foo.host_name.bar/http/path/to/admin/area/
	 */
	define('REASON_WEB_ADMIN_PATH', HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'admin/' );

    /**
	 * THOR_FORM_DB_CONN
	 * The name of the database connection used to store form data
	 * Thor settings will define the constant THOR_FORM_DB_CONN
	 */
	include ( SETTINGS_INC . '/thor_settings.php' );

	/**
	 * REASON_SESSION_CLASS
	 * The name of this instance of Reason's session handling class
	 */
	define( 'REASON_SESSION_CLASS', 'Session_PHP' );

    // Note: Reason is not configured to manage media uploads in this release.
	// It may be theoretically possible to get it working by setting the
	// streaming server info to your server, but this has not been tested.
	// Reason *will* manage media separately uploaded, even when REASON_MANAGES_MEDIA
	// is set to false.

	define('REASON_MANAGES_MEDIA',false); // change this to true when things are better

	define('NOTIFY_WHEN_MEDIA_IS_IMPORTED',false);

	define('MEDIA_MAX_UPLOAD_FILESIZE_MEGS', 50);

	define('MEDIA_FILESIZE_NOTIFICATION_THRESHOLD',0);

	define('MEDIA_NOTIFICATION_EMAIL_ADDRESSES',WEBMASTER_EMAIL_ADDRESS);

	/**
	 * REASON_BASE_STREAM_URL
	 * The http host/domain name for the media server that Reason uses
	 * This should have a trailing slash and no protocol, e.g. "media.foo.com/"
	 */
	define('REASON_BASE_STREAM_URL' , 'streaming_server.your_domain.com/');

	/**
	 * REASON_STREAM_DIR
	 * The path on remote host for media management
	 * This should have no slashes on either end, e.g "foo/bar"
	 * This is the http-available directory that is dedicated to Reason-managed media
	 * It should be writable by the user identified in REASON_STREAMING_USER
	 */
	define('REASON_STREAM_DIR' , 'reason_media');

	/**
	 * REASON_STREAMING_HOST
	 * the host name of the media server for file transfer/shell access
	 */
	define('REASON_STREAMING_HOST' , 'streaming_machine_name.your_domain.com');

	/**
	 * REASON_STREAM_BASE_PATH
	 * The path to the webserver root on the media server
	 */
	define('REASON_STREAM_BASE_PATH' , '/usr/local/helix/Content/');

	/**
	 * REASON_STREAMING_USER
	 * The username used to connect to the media server
	 */
	define('REASON_STREAMING_USER' , REASON_SITE_DIRECTORY_OWNER);

	/**
	 * REASON_REMOTE_HOME_PATH
	 * The location of the personal directory space on the media server
	 */
	define('REASON_REMOTE_HOME_PATH', '/mnt/people/home/');

	// info about location and name of the utility class for moving av files around

	/**
	 * REASON_AV_TRANSFER_UTILITY_LOCATION
	 * The location of the file that contains the class for transferring files to the media server
	 */
	define('REASON_AV_TRANSFER_UTILITY_LOCATION', 'ssh/streaming_server.php');

	/**
	 * REASON_AV_TRANSFER_UTILITY_CLASS_NAME
	 * The name of the class for transferring files to the media server
	 */
	define('REASON_AV_TRANSFER_UTILITY_CLASS_NAME', 'streaming_server');

	/**
	 * REASON_FLASH_VIDEO_PLAYER_URI
	 * http location of the .swf file used to play flash video (.flv) files
	 */
	define('REASON_FLASH_VIDEO_PLAYER_URI', FLVPLAYER_HTTP_PATH.'flvplayer.swf');

	/**
	 * QUICKTIME_LINK_WEB_PATH
	 * http location of the script which generates quicktime link files for streaming quicktime media
	 */
	define('QUICKTIME_LINK_WEB_PATH',REASON_HTTP_BASE_PATH.'displayers/qt_link.php');

	/**
	 * REASON_ADMIN_CSS_DIRECTORY
	 * Indicates the directory location (from the web root) of the Reason admin css files
	 */
	define('REASON_ADMIN_CSS_DIRECTORY',REASON_HTTP_BASE_PATH.'css/reason_admin/');

	/**
	 * REASON_ADMIN_IMAGES_DIRECTORY
	 * Indicates the directory location (from the web root) of the Reason admin image files
	 */
	define('REASON_ADMIN_IMAGES_DIRECTORY',REASON_HTTP_BASE_PATH.'ui_images/reason_admin/');

	/**
	 * REASON_IMAGE_VIEWER
	 * The path from the web root of the image popup script
	 */
	define( 'REASON_IMAGE_VIEWER',REASON_HTTP_BASE_PATH.'displayers/image.php' );

	define('REASON_PRIMARY_NEWS_PAGE_URI','http://'.HTTP_HOST_NAME.'/news/'); // this should go away
	define('REASON_PRIMARY_EVENTS_PAGE_URI','http://'.HTTP_HOST_NAME.'/calendar/'); // this should go away
	define('REASON_STATS_URI_BASE','');

	// Reason can be configured to pull images from a remote server when
	// batch uploading, but this still contains code specific to Carleton.
	// Batch uploading of images shold be ready by the next release.

	define('REASON_SSH_DEFAULT_HOST', '');
	define('REASON_SSH_DEFAULT_USERID', REASON_SITE_DIRECTORY_OWNER);
	define('REASON_SSH_TEMP_STORAGE', "/tmp/import");
	define('REASON_IMAGE_IMPORT_HOST','');
	define('REASON_IMAGE_IMPORT_BASE_PATH','/mnt/people/home');
	define('REASON_IMAGE_IMPORT_USERID',REASON_SITE_DIRECTORY_OWNER);

	/**
	 * PREVENT_MINIMIZATION_OF_REASON_DB
	 *
	 * This should almost always be set to true.
	 * Only set this to false if this is an instance whose primary purpose
	 * for existence is to have most of its entities deleted so as to create
	 * a clean new base for a new Reason instance.
	 */
	define('PREVENT_MINIMIZATION_OF_REASON_DB', true);

	/**
	 * WEB_JAVASCRIPT_PATH
	 * The web address of javascript files used by reason modules.
	 */
	define( 'WEB_JAVASCRIPT_PATH', REASON_HTTP_BASE_PATH.'js/' );

	/**
	 * USE_JS_LOGOUT_TIMER
	 * Use the session timeout javascript
	 */
	define( 'USE_JS_LOGOUT_TIMER', true);

	/**
	 * DEFAULT_TO_POPUP_ALERT
	 * This specifies the way in which the user is notified that their session has expired.
	 * A value of true means a javascript:alert() is used.
	 * A value of false means a div-based notice is used.
	 * The alert (true) is more accessible, but also more obtrusive
	 * (e.g. the browser window will ask for attention even when user is doing something else.)
	 * Note that this setting is just for the default behavior; this can be set on a per-user basis.
	 */
	define( 'DEFAULT_TO_POPUP_ALERT', false);

	// This is for the session_cookie class, which is not fully implemented.
	//define('REASON_COOKIE_DOMAIN','.domain_name.domain');

	define( 'REASON_ICALENDAR_UID_DOMAIN','reason');

	/**
	 * Record the types that have feeds for the editor link
	 *
	 * If you add a new feed for the Loki link dialog box, register it here so it can show up
	 */
	$GLOBALS['_reason_types_with_editor_link_feeds'] = array('minisite_page','news','event_type','asset');

	/**
	 * REASON_DEFAULT_ALLOWED_TAGS
	 *
	 * @deprecated  slated for removal in Reason 4.5
	 *
	 * This security model is insufficient for XSS protection - see REASON_ENABLE_ENTITY_SANITIZATION.
	 *
	 * A whitelist of the (X)HTML(5) tags Reason will allow to be saved to the database.
	 *
	 * Note that the head items field on pages does not follow this whitelist.
	 *
	 * Left out of this list for security reasons, or because they belong outside the document body:
	 * <base><body><head><html><link><meta><script><title>
	 *
	 * Included in this list for legacy reasons:
	 * <acronym><big><rb><rpc><rtc><strike><tt>
	 *
	 * Tags deprecated in HTML4 and not revived in HTML5, and therefore left out of this list:
	 * <applet><center><dir><font><isindex><xmp>
	 *
	 * This string should be in the same format as the second argument to php's built-in strip_tags() function, e.g.: '<a><abbr><acronym><address>'
	 */
	define('REASON_DEFAULT_ALLOWED_TAGS','<a><abbr><acronym><address><area><article><aside><audio><b><bdi><bdo><big><blockquote><br><button><canvas><caption><cite><code><col><colgroup><command><datalist><dd><del><details><dfn><div><dl><dt><em><embed><fieldset><figcaption><figure><footer><form><h1><h2><h3><h4><h5><h6><header><hgroup><hr><i><iframe><img><input><ins><kbd><keygen><label><legend><li><map><mark><menu><meter><nav><noscript><object><ol><optgroup><option><output><p><param><pre><progress><q><ruby><rb><rp><rpc><rt><rtc><s><samp><section><select><small><source><span><strike><strong><sub><summary><sup><table><tbody><td><textarea><tfoot><th><thead><time><tr><track><tt><u><ul><var><video><wbr>');

	/**
	 * REASON_ENABLE_ENTITY_SANITIZATION
	 *
	 * When this is set to true, when saved or updated, each field of an entity will be run through default or custom sanitization
	 * procedures as defined in config/sanitization/default.php. In its default configuration, this runs most entity fields through
	 * HTML Purifier, combatting a wide variety of XSS exploits.
	 *
	 * This is new in Reason 4.4. If you have modified REASON_DEFAULT_ALLOWED_TAGS in the past, you may need to customize the filtering
	 * mechanism in config/sanitization/default.php so that XHTML filtering works according to your setup.
	 *
	 * This will be enabled by default in Reason 4.5. You should test and enable it as soon as possible in Reason 4.4.
	 */
	define('REASON_ENABLE_ENTITY_SANITIZATION', false);

	/**
	 * REASON_DEFAULT_FOOTER_XHTML
	 *
	 * The default text to be used to indicate maintainer/contact information
	 *
	 * There are three strings that will be automagically replaced with the site/page info:
	 *
	 * [[sitename]] is replaced with the name of the site
	 *
	 * [[maintainer]] is replaced with the name/email of the site maintainer
	 *
	 * [[lastmodified]] is replaced with the date the page was most recently modified
	 */
	define('REASON_DEFAULT_FOOTER_XHTML','<div id="maintainer">[[sitename]] pages maintained by [[maintainer]]</div><div id="lastUpdated">This page was last updated on [[lastmodified]]</div>');

	/**
	 * REASON_DEFAULT_FAVICON_PATH
	 *
	 * The URL of the image that should be used as a favicon for sites in this instance.
	 *
	 * Leave this string empty to keep Reason from including a favicon link on pages.
	 *
	 * In future releases there will likely be a way to specify favicons for individual sites.
	 */
	define('REASON_DEFAULT_FAVICON_PATH','');

	/**
	 * REASON_PERFORMANCE_PROFILE_LOG
	 *
	 * This defines the path to a file where Reason can log performance-related information
	 *
	 * If you enter a path to a file on the filesystem, Reason will
	 * log page generation times into that file, enabling analysis of which pages need to be
	 * optimized, or performance changes over time.
	 */
	define('REASON_PERFORMANCE_PROFILE_LOG','');

	/**
	 * REASON_FORMS_THOR_DEFAULT_VIEW
	 *
	 * Indicates the filename that should be used as the default thor view for thor forms within Reason.
	 *
	 * You can provide the path in one of three ways:
	 *
	 * 1. Fully qualified path
	 * 2. Pathname from the core/local split
	 * 3. filename within minisite_templates/modules/form/views/thor/ directory
	 */
	define('REASON_FORMS_THOR_DEFAULT_VIEW', 'default.php');

	/**
	 * REASON_FORMS_THOR_DEFAULT_MODEL
	 *
	 * Indicates the filename that should be used as the default model for thor forms within Reason.
	 *
	 * You can provide the path in one of three ways:
	 *
	 * 1. Fully qualified path
	 * 2. Pathname from the core/local split
	 * 3. Relative path from within minisite_templates/modules/form/models/ directory
	 */
	define('REASON_FORMS_THOR_DEFAULT_MODEL', 'thor.php');

	/**
	 * REASON_FORMS_THOR_DEFAULT_CONTROLLER
	 *
	 * Indicates the filename that should be used as the default controller for thor forms within Reason.
	 *
	 * You can provide the path in one of three ways:
	 *
	 * 1. Fully qualified path
	 * 2. Pathname from the core/local split
	 * 3. Relative path from within minisite_templates/modules/form/controllers/ directory
	 */
	define('REASON_FORMS_THOR_DEFAULT_CONTROLLER', 'thor.php');

	/**
	 * REASON_LOKI_CSS_FILE
	 *
	 * If this constant is a non-empty string, Reason will instruct Loki 2+ to add a stylesheet to the content editing pane.
	 * The value of the constant should be the URL of the stylesheet to add. It's best to provide a URL relative
	 * to the base of the server or starting with //domain.com/ so as to avoid security warnings.
	 */
	define('REASON_LOKI_CSS_FILE','');

	/**
	 * REASON_TINYMCE_CONTENT_CSS_PATH
	 *
	 * Relative path to the CSS file that TinyMCE will use to style the WYSIWYG content area. If you have an install
	 * of Reason CMS with a simple css footprint and a main css file, you may want to change this to that css file
	 * instead of using the simple one (content.css) that comes with Reason CMS.
	 */
	define('REASON_TINYMCE_CONTENT_CSS_PATH', REASON_HTTP_BASE_PATH . 'tinymce/css/content.css');

	/**
	 * REASON_DELETED_ITEM_EXPUNGEMENT_WAIT_DAYS
	 *
	 * The number of days Reason's garbage collector should wait before expunging a deleted item from the database.
	 *
	 * Note: Reason's garbage collector script must be run as a nightly cron job in order for this feature to work.
	 */
	define('REASON_DELETED_ITEM_EXPUNGEMENT_WAIT_DAYS', 14);

	/**
	 * REASON_DISABLE_AUTO_UPDATE_CHECK
	 *
	 * If you want Reason to stop checking for updates, set this to true. (Not recommended... but if
	 * you don't want Reason phoning home to check for updates, this setting is for you.)
	 */
	define('REASON_DISABLE_AUTO_UPDATE_CHECK', false);

	/**
	 * REASON_LOG_LOGINS
	 *
	 * The Reason login module can log all login and logout actions.  If you set this value to true,
	 * a log file will be populated at REASON_LOG_DIR/reason_login.log
	 */
	define('REASON_LOG_LOGINS', false);

	/**
	 * REASON_SIZED_IMAGE_DIR
	 *
	 * Full file system path to the directory where Reason's sized image class (sized_image.php) should store sized images.
	 */
	define('REASON_SIZED_IMAGE_DIR', REASON_DATA_DIR.'sized_images/');

	/**
	 * REASON_SIZED_IMAGE_DIR_WEB_PATH
	 *
	 * Web path to the directory where Reason's sized image are accessible.
	 */
	define('REASON_SIZED_IMAGE_DIR_WEB_PATH', REASON_HTTP_BASE_PATH.'sized_images/');

	/**
	 * REASON_SIZED_IMAGE_CUSTOM_DIR
	 *
	 * Full file system path to the directory where Reason's should store custom sized images.
	 */
	define('REASON_SIZED_IMAGE_CUSTOM_DIR', REASON_DATA_DIR.'sized_images_custom/');

	/**
	 * REASON_SIZED_IMAGE_CUSTOM_DIR_WEB_PATH
	 *
	 * Web path to the directory where Reason's custom sized image are accessible.
	 */
	define('REASON_SIZED_IMAGE_CUSTOM_DIR_WEB_PATH', REASON_HTTP_BASE_PATH.'sized_images_custom/');

	/**
	 * REASON_ENTITY_LOCKS_ENABLED
	 *
	 * This setting turns entity locking on and off.
	 *
	 * If it is set to true, entities & their relationships can be locked by users with appropriate privs.
	 *
	 * If it is set to false, Reason will ignore all locking information and will not present interfaces
	 * for setting up locks.
	 */
	define('REASON_ENTITY_LOCKS_ENABLED', false);

	/**
	 * REASON_EVENT_GEOLOCATION_ENABLED
	 *
	 * Reason event geolocation adds mapping features to the event content manager and to Reason event modules.
	 * These features use the free version of google maps, and are enabled by default. Google provides info on
	 * the terms and conditions of their mapping service here:
	 *
	 * http://code.google.com/apis/maps/terms.html
	 *
	 * If you are using Reason in an environment that does not quality for free use of Google maps you should
	 * disable event geolocation.
	 */
	define('REASON_EVENT_GEOLOCATION_ENABLED', true);

	/**
	 * REASON_MYSQL_SPATIAL_DATA_AVAILABLE
	 *
	 * If you are running MySQL 5, Reason can store location information as binary data in MySQL, and keep
	 * this data up to date using triggers. This is off by default - you should only turn it on if you have
	 * upgraded your database to support this functionality as described in the binary spatial data upgrade
	 * script.
	 *
	 * If you enable this on a database that does not have this support Reason will crash.
	 */
	define('REASON_MYSQL_SPATIAL_DATA_AVAILABLE', false);

	/**
	 * REASON_IPINFODB_API_KEY
	 *
	 * Optionally provide your api key for the api.ipinfodb.com ip address geolocation service.
	 *
	 * With an API key, Reason can provide superior ip geolocation results.
	 */
	define('REASON_IPINFODB_API_KEY', '');

  /**
   * REASON_SHOW_META_KEYWORDS
   *
   * Show meta keywords in <head>. meta keywords are deprecated and no longer beneficial for SEO.
   */
  define('REASON_SHOW_META_KEYWORDS', false);

  /**
   * REASON_HOME_TITLE_PATTERN
   *
   */
  define('REASON_HOME_TITLE_PATTERN', '[minisite_name] | [organization_name]');

  /**
   * REASON_SECONDARY_TITLE_PATTERN
   *
   */
  define('REASON_SECONDARY_TITLE_PATTERN', '[minisite_name]: [page_title] | [organization_name]');

  /**
   * REASON_ITEM_TITLE_PATTERN
   *
   */
  define('REASON_ITEM_TITLE_PATTERN', '[item_name] | [minisite_name] | [organization_name]');
