<?php 

	/* siblings.php: display all the pages that have the same parent as the current page */

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'SiblingModule';


	class SiblingModule extends DefaultMinisiteModule
	{
		function init ( $args = array() )	 // {{{
		{
			parent::init( $args );
			
			// check to see if this is a home page -- don't even query for siblings
			if($this->parent->cur_page->get_value( 'parent_id' ) == $this->parent->cur_page->id())
			{
				$this->siblings = array();
			}
			else
			{
				$es = new entity_selector();
				$es->description = 'Selecting siblings of the page';
	
				// find all the siblings of this page
				$es->add_type( id_of('minisite_page') );
				$es->add_left_relationship( $this->parent->cur_page->get_value( 'parent_id' ), relationship_id_of( 'minisite_page_parent' ));
				$es->set_order('sortable.sort_order ASC' );
				$this->siblings = $es->run_one();
			}
			
		} // }}}
		function has_content() // {{{
		{
			if( empty($this->siblings) )
			{
				return false;
			}
			else
				return true;
		} // }}}
		function run() // {{{
		{
			echo '<ul class="siblingList">'."\n";

			foreach ( $this->siblings AS $sibling )
			{
				// for when the page is one level below the root: since we have grabbed all the pages whose
				// parent is this page's parent, and the root is in fact its own parent, we must check that
				// we don't display it
				if( $this->parent->pages->root_node() == $sibling->_id )
					continue;

				/* If the page has a link name, use that; otherwise, use its name */
				$page_name = $sibling->get_value( 'link_name' ) ? $sibling->get_value( 'link_name' ) : $sibling->get_value('name');
				
				if ( $this->parent->cur_page->id() != $sibling->id() )
				{
					/* Check for a url (that is, the page is an external link); otherwise, use its relative address */
					if( $sibling->get_value( 'url' ) )
						$link = $sibling->get_value( 'url' );
					else
					{
						$link = '../'.$sibling->get_value( 'url_fragment' ).'/';
						if (!empty($this->parent->textonly))
							$link .= '?textonly=1';
						//pray($this->parent->site_info);
						//$base_url = $this->parent->site_info[ 'base_url' ];
						//$link = '/'.$base_url.$this->get_nice_url( $child->id() ).'/';
					}
						
					echo '<li><a href="'.$link.'">'.$page_name.'</a>';
					/* if ( $sibling->get_value( 'description' ))
						echo "\n".'<div class="smallText">'.$sibling->get_value( 'description' ).'</div>'; */
					echo "</li>\n";
				}
				else echo '<li><strong>'.$page_name.'</strong></li>'."\n";
			}
			echo '</ul>'."\n";
		} // }}}
	}

?>
