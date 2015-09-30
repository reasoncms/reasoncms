<?php
if(!defined('ZENCODER_HTTPS_ENABLED')) define('ZENCODER_HTTPS_ENABLED', true);

// Set this is true to use Zencoder's test mode. (Zencoder will only transcode the first 5 seconds, and leave
// a watermark on videos.)
if(!defined('ZENCODER_TEST_MODE')) define('ZENCODER_TEST_MODE', true);

// either 'reason' or 's3'.
if(!defined('ZENODER_FILE_STORAGE_OPTION')) define('ZENCODER_FILE_STORAGE_OPTION', '');

/**
 * The access key needed to use Zencoder's API
 */
if(!defined('ZENCODER_FULL_ACCESS_KEY')) define('ZENCODER_FULL_ACCESS_KEY', '');

?>