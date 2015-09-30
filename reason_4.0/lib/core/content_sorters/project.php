<?php
/**
 * @package reason
 * @subpackage content_sorters
 */
	/**
	 * Register sorter with Reason
	 */
	$GLOBALS[ '_content_sorter_class_names' ][ basename( __FILE__) ] = 'project_sorter';
	
	/**
	 * A content sorter for projects
	 */
	class project_sorter extends sorter
	{
		function update_es( $es ) // {{{
		{
			if( !empty( $this->admin_page->request[ 'sort_current_projects' ] ) )
			{
				$es->add_relation( 'bug.bug_state != 
"Cancelled"' );
				$es->add_relation( 'bug.bug_state != 
"Done"' );
			}
			return $es;
		} // }}}
		function get_links() // {{{
		{
			$links = parent::get_links();
			$links[ 'Sort Current Projects' ] = $this->admin_page->make_link( array( 'sort_current_projects' => 'true' , 'default_sort' => false ) , true );
			$this->links = $links;
			return( $this->links );
		} // }}}
	}
?>
