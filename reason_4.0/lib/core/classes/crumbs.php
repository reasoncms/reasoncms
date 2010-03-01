<?php
/**
 * @package reason
 * @subpackage classes
 * @author Matt Ryan
 */
	/**
	 * A class that encapsulates a set of breadcrumbs
	 */
	class reasonCrumbs
	{
		/**
		 * @var array
		 * @access private
		 */
		var $_crumbs = array();
		
		/**
		 * Add a breadcrumb to the end of the set of breadcrumbs
		 * @param string $name
		 * @param string $link
		 * @return void
		 */
		function add_crumb( $name , $link = '' )
		{
			$this->_crumbs[] = array( 'page_name' => $name , 'link' => $link );
		}
		
		/**
		 * Add a breadcrumb to the beginning of the set of breadcrumbs
		 * @param string $name
		 * @param string $link
		 * @return void
		 */
		function add_crumb_to_top( $name , $link = '' )
		{
			array_unshift($this->_crumbs, array('page_name' => $name , 'link' => $link));
		}
		
		/**
		 * Get the breadcrumbs in the form of an array
		 *
		 * Format:
		 * <code>
		 * array( 
		 * 		array('page_name'=>'Name of page','link'=>'/url/of/page/'),
		 * 		array('page_name'=>'Name of page','link'=>'/url/of/page/'),
		 * 		...
		 * );
		 * </code>
		 * @return array
		 */
		function get_crumbs()
		{
			return $this->_crumbs;
		}
		
		/**
		 * Get the last crumb (or null if no crumbs)
		 *
		 * Array format:
		 * <code>
		 * array('page_name'=>'Name of page','link'=>'/url/of/page/');
		 * </code>
		 * @return array | NULL
		 */
		function get_last_crumb()
		{
			if(!empty($this->_crumbs))
			{
				$last_crumb = end( $this->_crumbs);
				reset( $this->_crumbs );
				return $last_crumb;
			}
			return NULL;
		}
	}
?>
