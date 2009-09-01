<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	reason_include_once( 'content_listers/default.php3' );
	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'sections_and_issues_viewer';
	
	/**
	 * A lister/viewer for the news/post type that includes issue and section columns and searcability
	 */
	class sections_and_issues_viewer extends generic_viewer
	{
		/**
		 * Adds the columns and filters needed by this lister
		 */
		function alter_values() // {{{
		{
			$this->add_left_relationship_column( 'news_to_news_section' , 'entity' , 'name' , 'section' );
			$this->add_filter( 'section' );
			$this->add_left_relationship_column( 'news_to_issue' , 'entity' , 'name' , 'issue' );
			$this->add_filter( 'issue' );
		} // }}}
	}
?>
