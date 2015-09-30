<?php
/**
 * @package reason
 * @subpackage content_listers
 */
	/**
	 * Include parent class and register viewer with Reason.
	 */
	reason_include_once( 'content_listers/tree.php3' );

	$GLOBALS[ '_content_lister_class_names' ][ basename( __FILE__) ] = 'multiple_root_tree_viewer';

	/**
	 * A lister/viewer that shows hierarchical entities that can have multiple roots in the same site
	 */
	class multiple_root_tree_viewer extends tree_viewer
	{
		var $roots = array();
		function root_node() // {{{
		{
			if(empty($this->roots))
			{
				foreach( $this->values AS $value )
				{
					if($value->id() == $value->get_value( 'parent_id' ) )
						$this->roots[] = $value->id();
				}
			}
			return $this->roots;
		} // }}}
		function parent( $id ) // {{{
		{
			$x = $this->values[ $id ];
			if( $x )
				return $x->get_value( 'parent_id' );
			else return false;
		} // }}}
		function children( $id ) // {{{
		{
			$children = array();
			$roots = $this->root_node();
			foreach( $this->values AS $item )
			{
				if(( $item->get_value( 'parent_id' ) == $id ) AND (!in_array( $item->id() , $roots ) ) )
					$children[] = $item->id();
			}
			return $children;
		} // }}}

		function alter_values() // {{{
		{
			$parent = new entity_selector();
			$parent->add_field( 'entity2' , 'id' , 'parent_id' );
			
			$parent->add_table( 'allowable_relationship2' , 'allowable_relationship' );
			$parent->add_table( 'relationship2' , 'relationship' );
			$parent->add_table( 'entity2' , 'entity' );

			$parent->add_relation( 'entity2.id =  relationship2.entity_b' );
			$parent->add_relation( 'entity.id = relationship2.entity_a' );
			$parent->add_relation( 'relationship2.type = allowable_relationship2.id' );
			$parent->add_relation( 'allowable_relationship2.name LIKE "%parent%"' );
			$parent->set_order( 'sortable.sort_order' );

			$this->es->swallow( $parent );
			$this->remove_column( 'id' );
		} // }}}	
		function is_open( $id )  // {{{
		{
			if( preg_match( '/,' . $id . ',/' , $this->open ) )
				return true;
			else return false;
		} // }}}

		function show_all_items() // {{{
		{
				?>
				<table cellpadding="8" border="0" cellspacing="0">
				<?php
				$roots = $this->root_node();
				$this->show_sorting();
				foreach( $roots AS $root )
					$this->make_tree( $root , $root , 0);
					
				?>
				</table>
				<?php
	 	} // }}} 

		function show_item_pre( $row , &$options) // {{{
		{
			if( !empty($options[ 'color' ]) ) 
				$class = 'highlightRow';
			else 
				$class = 'listRow2';
			echo '<tr class="'.$class.'"><td>' . $row->id() . '</td>';
			
			$open_link = $this->open;
			if( !is_array( $options ) )
				$options = array();
			$options[ 'class' ] = $class;
			echo '<td>';

			echo '&nbsp;';
			if( $this->children( $row->id() ) )
			{
				$remove_filter = $this->set_no_filters();
				if( $this->is_open( $row->id() ) )
				{
					$open_link = preg_replace( '/,'.$row->id().',/', '' , $open_link );
					if( substr( $open_link , ( strlen( $open_link ) - 1 ) , 1 ) == ',' )
						$open_link .= '0';
					echo '<a href="' . $this->admin_page->make_link( array_merge( array( 'open' => $open_link ) , $remove_filter ) , true ). '">'.
					'<img src="'.REASON_HTTP_BASE_PATH.'ui_images/item_open.gif" width="13" height="13" border="0" alt="open item. click to close." />'
					.'</a>';
				}
				else
				{
					$open_link .= ',' . $row->id() . ',';
					if( substr( $open_link , ( strlen( $open_link ) - 1 ) , 1 ) == ',' )
						$open_link .= '0';
					echo '<a href="' . $this->admin_page->make_link( array_merge( array( 'open' => $open_link ) , $remove_filter ) , true ). '">' .
					'<img src="'.REASON_HTTP_BASE_PATH.'ui_images/item_closed.gif" width="13" height="13" border="0" alt="closed item. click to open." />'
					.'</a>';
				}
			}
			echo '</td>';
		} // }}}
		function force_open( $key , $open = true) // {{{
		{
			if( $open )
				if( !$this->is_open( $key ) )
					$this->open .= ',' . $key . ',';
			$root = $this->root_node();
			while(!in_array( $key , $root ) )
			{
				$key = $this->parent($key);
				if( !$this->is_open( $key ) )
					$this->open .= ',' . $key . ',';
			}
		} // }}}

		function has_filters() // {{{
		{
			if($this->filters) 
			{
				reset( $this->filters );
				while( list( $name , $value ) = each( $this->filters ) )
				{
					if( $value )
					{
						$key = 'search_' . $name;
						if(isset($this->admin_page->request[$key])) return true;
					}
				}
				return false;
			}
			else
				return false;
		} // }}}
		function set_no_filters() // {{{
		{
			$rm = array();
			reset($this->filters);	
			while(list($key, ) = each( $this->filters ))
			{
				$name = 'search_' . $key;
				$rm[ $name ] = '';
			}
			return $rm;
		} // }}}
		
	}
?>
