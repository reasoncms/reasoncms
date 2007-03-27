<?php
	reason_include_once( 'classes/entity_selector.php' );
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'parent_childManager';

	class parent_childManager extends ContentManager
	{
		var $pages = array();
		var $left_assoc_display_names = array(
											'ocs_page_parent' => 'Parent Page' ,
											'page_node_parent' => 'Parent Page' ,
											'minisite_page_parent' => 'Parent Page' );
		var $left_assoc_omit_link = array( 'ocs_page_parent' , 'minisite_page_parent', 'page_node_parent' );
		var $allow_creation_of_root_node = false;
		var $multiple_root_nodes_allowed = false;
		var $root_node_description_text = '-- Top-level item --';
		function alter_data() // {{{
		{
			$parent_id = empty( $this->admin_page->request[ 'parent_id' ] ) ? false : $this->admin_page->request[ 'parent_id' ];
			$r_id = $this->get_parent_relationship();
			/*
			// CODE REMOVED 4/24/03 by hendlerd
			// this code is meant to hide parent page when adding a child.
			// the problem that results is when a parent page is set on a 
			// new or existing item but the form does not pass the error
			// checks, the parent page would disappear on the next page.
			// this is due to the fact that the parent_id was set on the 
			// submit.  i simply removed the code so the parent page will always show
			// up, even if adding a child.  it will just show the parent_id specified.
			if( $parent_id )
			{
				if( $r_id )
				{
					$this->add_element( 'r_id' , 'hidden' );
					$this->set_value( 'r_id' , $r_id );
				
					$this->add_element( 'parent_id' , 'hidden' );
					$this->set_value( 'parent_id' , $parent_id );
					$this->set_display_name( 'parent_id' , 'Parent Page' );
					$this->add_required( 'parent_id' );
				}
			}
			else 
			{
			*/
				if( $r_id )
				{
					$parent = new entity_selector( $this->get_value( 'site_id' ) );
					$parent->add_type( $this->get_value( 'type_id' ) );
					$parent->add_field( 'entity2' , 'id' , 'parent_id' );
					
					$parent->add_table( 'allowable_relationship2' , 'allowable_relationship' );
					$parent->add_table( 'relationship2' , 'relationship' );
					$parent->add_table( 'entity2' , 'entity' );

					$parent->add_relation( 'entity2.id =  relationship2.entity_b' );
					$parent->add_relation( 'entity.id = relationship2.entity_a' );
					$parent->add_relation( 'relationship2.type = allowable_relationship2.id' );
					$parent->add_relation( 'allowable_relationship2.name LIKE "%parent%"' );

					$this->pages = $parent->run_one();

					$roots = $this->root_node();
					
					if( !empty( $this->pages[ $this->get_value( 'id' ) ] ) )
					{
						$p = $this->pages[ $this->get_value( 'id' ) ];
						$p_id = $p->get_value( 'parent_id' );
					}
					else // try to find parent of non-live entity
					{	
						$d = new DBSelector;
						$d->add_table( 'r' , 'relationship' );

						$d->add_field ( 'r' , 'entity_b' , 'p_id' );

						$d->add_relation( 'r.type = ' . $r_id );
						$d->add_relation( 'r.entity_a = ' . $this->get_value( 'id' ) );

						$res = db_query( $d->get_query() , 'Error getting page parent in parentchildManager::alter_data()' );

						if( $row = mysql_fetch_array( $res , MYSQL_ASSOC ) )
							$p_id = $row[ 'p_id' ];
						else
							$p_id = false;
					}
					$list = array();
					if($this->allow_creation_of_root_node)
					{
						if(empty($roots) || $this->multiple_root_nodes_allowed)
						{
							$list[$this->get_value( 'id' )] = $this->root_node_description_text;
						}
					}
					if( 
						($this->allow_creation_of_root_node && $this->multiple_root_nodes_allowed) ||
						(!in_array( $this->get_value( 'id' ) , $roots ))
					 )
					 {
						foreach( $roots AS $root )
						{
							$list = $list + $this->recurse_children( $root, $this->get_value( 'id' ) );
						}
					}
					
					// this chunk replaces the code removed above.
					// we need to set the parent id at some point.
					// here is good.
					if( !empty( $parent_id ) )
						$use_this_parent_id = $parent_id;
					else
						$use_this_parent_id = $p_id;
					$list = $this->alter_tree_list( $list, $use_this_parent_id );
					$this->add_element( 'parent_id' , 'select_no_sort' , array( 'options' => $list ) );
					$this->set_value( 'parent_id', $use_this_parent_id );
					$this->set_display_name( 'parent_id' , 'Parent Page' );
					$this->add_required( 'parent_id' );
					//$this->set_comments( 'parent_id' , form_comment( 'Note: If the item you are editing is a root, no options will appear in this field.' ) );

					$this->add_element( 'r_id' , 'hidden' );
					$this->set_value( 'r_id' , $r_id );
				}
				else
				{
					$this->add_element( 'r_id' , 'hidden' );
					$this->set_value( 'r_id' , false );
				}
				$roots = $this->root_node();
				if( in_array($this->_id, $roots) )
				{
					if(!$this->allow_creation_of_root_node)
					{
						$this->change_element_type( 'parent_id' , 'hidden' );
					}
					elseif( !empty($roots) && !$this->multiple_root_nodes_allowed )
					{
						$this->change_element_type( 'parent_id' , 'hidden' );
					}
				}
			//}
		} // }}}
		function alter_tree_list( $list, $parent_id )
		{
			return $list;
		}
		function root_node() // {{{
		{
			$roots = array();
			foreach( $this->pages AS $value )
				if($value->id() == $value->get_value( 'parent_id' ) )
					$roots[] = $value->id();
			return $roots;
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
			foreach( $this->pages AS $item )
			{
				if(( $item->get_value( 'parent_id' ) == $id ) AND (!in_array( $item->id() , $roots ) ) )
					$children[] = $item->id();
			}
			return $children;
		} // }}}

		
		function recurse_children( $id , $current, $depth='') // {{{
		{
			//if ( $id > 0 ) { // Added 4/17/03 nate
				$page = $this->pages[ $id ];
				if( $id == $current )
					return array();
				$children_list = array( $id => $depth . $page->get_value( 'name' ) );

				$children = $this->children( $id );
				reset( $children );
				while( list( , $value ) = each( $children ) )
					$children_list = $children_list + $this->recurse_children( $value, $current, $depth . "--" );
				return $children_list;
			//}
		} // }}}
		function get_parent_relationship() // {{{
		{
			$q = 'SELECT id FROM allowable_relationship WHERE
					name LIKE "%parent%" 
					AND relationship_a = ' . $this->get_value( 'type_id' );

			$r = db_query( $q , 'error getting parent relationship' );
			$row = mysql_fetch_array( $r );
			if( $row )
				return $row[ 'id' ];
			else return false;
		} // }}}
		function make_parent( ) // {{{
		{
			$id = $this->get_value( 'id' );
			
			$q = 'DELETE FROM relationship WHERE entity_a = ' . $id . ' AND type = ' . $this->get_value( 'r_id' );

			db_query( $q , 'error deleting existing parent relationship' );
			
			create_relationship( $id , $this->get_value( 'parent_id' ) ,
					     $this->get_value( 'r_id' ) 
					   );
		} // }}}
		function finish() // {{{
		{
			$val = $this->CMfinish();
			if( $this->get_value( 'r_id' ) )
			{
				$this->make_parent( );
			}
			return $val;
		} // }}}

	}
