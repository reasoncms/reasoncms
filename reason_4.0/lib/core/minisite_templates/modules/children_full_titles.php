<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include base class & register module with Reason
 */
reason_include_once( 'minisite_templates/modules/children.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildrenFullTitlesModule';

/**
 * A minisite module that shows child pages of the current page, using their full titles rather than their link names.
 *
 * Revised December 2012 - Instead of duplicating code this now just forces a new param "force_full_page_titles" to true.
 *
 * @author Nathan White
 */
class ChildrenFullTitlesModule extends ChildrenModule 
{
	function init( $args = array() )
	{
		$this->params['force_full_page_titles'] = true;
		parent::init( $args );
	}
}
?>