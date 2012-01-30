<?php
/**
 * Domain settings are experimental.
 * 
 * If you configure this file, you will be given an additional option in the Master Admin site content manager
 * that allows you to choose a domain on a per site basis.
 *
 * For each domain, you must set the WEB_PATH, which should be equivalent to $_SERVER['DOCUMENT_ROOT'] . '/' for 
 * that domain. This is required so that rewrites rules are properly executed in the proper directories.
 *
 * Here are some other settings you may want to customize on the per domain basis.
 *
 * - FULL_ORGANIZATION_NAME
 * - SHORT_ORGANIZATION_NAME
 * - ORGANIZATION_HOME_PAGE_URI
 * - WEBMASTER_EMAIL_ADDRESS
 * - WEBMASTER_NAME
 * - UNIVERSAL_CSS_PATH
 *
 * NOTE: Any settings that you setup here, to be applied, must be setup using domain_define in the applicable settings file
 * @package reason
 */

/**
 * Configure $GLOBALS['_reason_domain_settings'] to enable domain specific settings - here is a sample:
 *
 * <code>
 * $GLOBALS['_reason_domain_settings'] = array(
 *	'my.reason.domain' => array("WEB_PATH" => '/domain/specific/path/to/document_root/')
 * </code>
 *
 * @var array
 */

$GLOBALS['_reason_domain_settings'] = array();

if (!empty($GLOBALS['_reason_domain_settings']) && (isset($_SERVER['HTTP_HOST']) || isset($_SERVER['SERVER_NAME'])))
{
	$apparent_host = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	if (isset($GLOBALS['_reason_domain_settings'][$apparent_host]))
	{
		// lets define constants for the current domain
		foreach ($GLOBALS['_reason_domain_settings'][$apparent_host] as $constant => $value)
		{
			$GLOBALS['_current_domain_settings'][$constant] = $value;
		}
	}
}
?>
