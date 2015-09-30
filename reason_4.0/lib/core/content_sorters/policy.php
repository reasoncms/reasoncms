<?php
/**
 * @package reason
 * @subpackage content_sorters
 */
	/**
	 * Register sorter with Reason
	 */
	$GLOBALS[ '_content_sorter_class_names' ][ basename( __FILE__) ] = 'policy_sorter';

	/**
	 * A content sorter for policies
	 */
	class policy_sorter extends sorter
	{
		function init() // {{{
		{
			parent::init();
			if( !empty( $this->admin_page->request[ 'parent_id' ] ) )
			{
				$x = new entity( $this->admin_page->request[ 'parent_id' ] );
				$this->admin_page->title = 'Sorting Children of "' . $x->get_value( 'name' ) . '"';
			}
			elseif (!$this->is_new())
			{
				$this->admin_page->title = 'Sorting Top Level Policies';
			}
		} // }}}
		function update_es( $es ) // {{{
		{
			$parent_id = (!empty($this->admin_page->request[ 'parent_id' ])) ? $this->admin_page->request[ 'parent_id' ] : false;
			$field = $es->add_left_relationship_field( 'policy_parent' , 'entity' , 'id' , 'parent_id' );
			$es->add_left_relationship_field( 'policy_parent' , 'entity' , 'name' , 'parent_name' );
			if( $parent_id && is_numeric($parent_id) )
			{
				$es->add_relation( 'entity.id != ' . $this->admin_page->request[ 'parent_id' ] );
				$es->add_relation( '__entity__.id = ' . $this->admin_page->request[ 'parent_id' ] );
			}
			elseif (!$this->is_new())
			{
				$field_name = $field['parent_id']['table'].".".$field['parent_id']['field'];
				$es->add_relation( 'entity.id = ' . $field_name );
			}
			return $es;
		} // }}}
		function get_links() // {{{
		{
			//$links = parent::get_links();
			foreach( $this->values AS $page )
			{
				if( isset( $parents[ $page->get_value( 'parent_id' ) ] ) )
					$parents[ $page->get_value( 'parent_id' ) ][ 'num_children' ]++;
				else
					$parents[ $page->get_value( 'parent_id' ) ] = 
																array( 'name' => $page->get_value( 'parent_name' ),
																	   'num_children' => 1,
																	   'parent' => $page->get_value('parent_id'));
				
			}
			
			// we want to provide a way to sort just top level policies (lets use parent_id=0 to indicate this)
			$name = 'Sort Top Level Policies';
			$link = $this->admin_page->make_link( array( 'parent_id' => false, 'default_sort' => false ), true );
			$links[ $name ] = $link; 
			
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
