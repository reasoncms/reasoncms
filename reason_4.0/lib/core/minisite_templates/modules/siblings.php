<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the parent class and register the module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'SiblingModule';

	/**
	 * A minisite module that displays all the pages that have the same parent as the current page
	 *
	 * The current page is included in the list, but is not a link
	 */
	class SiblingModule extends DefaultMinisiteModule
	{
		var $siblings = array();
		function init ( $args = array() )	 // {{{
		{
			parent::init( $args );
			
			$root_id = $this->parent->pages->root_node();
			$this_page_id = $this->cur_page->id();
			// check to see if this is a home page -- don't even query for siblings
			if($root_id != $this_page_id)
			{
				$parent_id = $this->parent->pages->parent( $this->cur_page->id() );
				if(!empty($parent_id))
				{
					$sibling_ids = $this->parent->pages->children( $parent_id );
					if(!empty($sibling_ids))
					{
						foreach($sibling_ids as $sibling_id)
						{
							if($sibling_id != $root_id)
							{
								$this->siblings[$sibling_id] = new entity($sibling_id);
							}
						}
					}
				}
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
