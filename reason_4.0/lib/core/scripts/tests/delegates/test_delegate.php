<?php
/**
 * A delegate for the social account type
 * @package reason
 * @subpackage entity_delegates
 */

/**
 * Include dependencies
 */
reason_include_once( 'entity_delegates/abstract.php' );

/**
 * Register delegate
 */
$GLOBALS['entity_delegates']['scripts/tests/delegates/test_delegate.php'] = 'testDelegate';

/**
 * A delegate for the social account type
 */
class testDelegate extends entityDelegate
{
	function get_value($key)
	{
		switch($key)
		{
			case 'test_column':
				return 'TEST_VALUE';
			default:
				return NULL;
		}
		return NULL;
	}
	function get_test_output()
	{
		return 'TEST_OUTPUT';
	}
	/**
	 * Get the display name for this social account
	 */
	function get_display_name()
	{
		return 'TEST_DISPLAY_NAME';
	}
}