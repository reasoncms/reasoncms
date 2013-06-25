<?php
/**
 * @package reason
 * @subpackage content_managers
 */

/**
 * Register content manager with Reason
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'SocialAccountManager';

/**
 * A content manager for text blurbs
 */
class SocialAccountManager extends ContentManager
{
	function alter_data()
	{
	}
}
?>
