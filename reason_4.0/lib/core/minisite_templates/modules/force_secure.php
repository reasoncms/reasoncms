<?php
/**
 * @package reason
 * @subpackage minisite_modules
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
 
/**
 * Register the module
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ForceSecureModule';

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'function_libraries/user_functions.php' );

/**
 * Force the page to be loaded over https
 */
class ForceSecureModule extends DefaultMinisiteModule
{
	function init( $args = array() )
	{
		force_secure_if_available();
	}
}