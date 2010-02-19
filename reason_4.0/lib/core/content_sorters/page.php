<?php
/**
 * @package reason
 * @subpackage content_sorters
 */
	/**
	 * Register sorter with Reason
	 */
	$GLOBALS[ '_content_sorter_class_names' ][ basename( __FILE__) ] = 'page_sorter';

	/**
	 * A content sorter for minisite pages
	 */
	class page_sorter extends sorter
	{
		function init() // {{{
		{
			parent::init();
			if( !empty( $this->admin_page->request[ 'parent_id' ] ) )
			{
				$x = new entity( $this->admin_page->request[ 'parent_id' ] );
				$this->admin_page->title = 'Sorting Children of "' . $x->get_value( 'name' ) . '"';
			}
		} // }}}
		function update_es( $es ) // {{{
		{
			$es->add_left_relationship_field( 'minisite_page_parent' , 'entity' , 'id' , 'parent_id' );
			$es->add_left_relationship_field( 'minisite_page_parent' , 'entity' , 'name' , 'parent_name' );
			if( !empty( $this->admin_page->request[ 'parent_id' ] ) )
			{
				$es->add_relation( 'entity.id != ' . $this->admin_page->request[ 'parent_id' ] );
				$es->add_relation( '__entity__.id = ' . $this->admin_page->request[ 'parent_id' ] );
			}
			return $es;
		} // }}}
		function get_links() // {{{
		{
			$links = parent::get_links();
			foreach( $this->values AS $page )
			{
				if( isset( $parents[ $page->get_value( 'parent_id' ) ] ) )
					$parents[ $page->get_value( 'parent_id' ) ][ 'num_children' ]++;
				else
					$parents[ $page->get_value( 'parent_id' ) ] = 
																array( 'name' => $page->get_value( 'parent_name' ),
																	   'num_children' => 1 );
			}
			foreach( $parents AS $id => $info )
				if( $info[ 'num_children' ] > 1 )
				{
					$name = 'Sort children of "' . $info[ 'name' ] . '"';
					$link = $this->admin_page->make_link( array( 'parent_id' => $id , 'default_sort' => false ) , true );
					$links[ $name ] = $link;
				}

			$this->links = $links;
			return( $this->links );
		} // }}}
	}
?>
