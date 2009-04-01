<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NewsProofingMultipageModule';
	reason_include_once( 'minisite_templates/modules/news.php' );
       
    /**
     * A minisite module that shows non-published issues (for proofing)
     *
     * Note: this module is deprecated. Use the publications framework instead.
     *
     * @deprecated
     */
	class NewsProofingMultipageModule extends NewsMinisiteModule
	{
		var $limit_to_shown_issues = false;
	}
?>
