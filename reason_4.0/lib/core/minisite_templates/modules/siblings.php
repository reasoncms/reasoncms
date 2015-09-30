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
		var $acceptable_params = array(
										'use_parent_title_as_header' => false,
										'provide_images' => false,
										'randomize_images' => false,
										'show_only_pages_in_nav' => false,
										'show_external_links' => true,
										'thumbnail_width' => 0,
										'thumbnail_height' => 0,
										'thumbnail_crop' => '',
										'previous_next' => false,
									);
		protected $parent_page;
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
								
								$page = new entity($sibling_id);
								
								if($this->params['show_only_pages_in_nav'] && $page->get_value('nav_display') != 'Yes')
								{
									continue;
								}
								if(isset($this->params['show_external_links']) && !$this->params['show_external_links'] && $page->get_value('url'))
								{
									continue;
								}
								
								$this->siblings[$sibling_id] = $page;
							}
						}
					}
					$this->parent_page = new entity($parent_id);
				}
			}
			
		} // }}}
		protected function _get_previous_next($siblings)
		{
			$ids = array_keys($siblings);
			$positions = array_flip($ids);
			$ret = array();
			if(isset($ids[ $positions[$this->page_id] - 1 ]))
				$ret['previous'] = $siblings[$ids[ $positions[$this->page_id] - 1 ]];
			if(isset($ids[ $positions[$this->page_id] + 1 ]))
				$ret['next'] = $siblings[$ids[ $positions[$this->page_id] + 1 ]];
			return $ret;
		}
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
			if($this->params['previous_next'])
				$siblings = $this->_get_previous_next($this->siblings);
			else
				$siblings = $this->siblings;
			$classes = array('siblingList');
			if($this->params['provide_images'])
				$classes[] = 'siblingListWithImages';
			if($this->params['previous_next'])
				$classes[] = 'prevNext';
			
			echo '<div class="siblingsModule">'."\n";

			if($this->params['use_parent_title_as_header'])
			{
				echo '<h3>'.$this->parent_page->get_value('name').'</h3>'."\n";
			}
			echo '<ul class="'.implode(' ',$classes).'">'."\n";
			$counter = 1;
			$even_odd = 'odd';

			foreach ( $siblings AS $key=>$sibling )
			{
				$classes = array('number'.$counter, $even_odd);
				$uname = '';
				if($sibling->get_value( 'unique_name' ))
				{
					$classes[] = 'uname-'.reason_htmlspecialchars($sibling->get_value( 'unique_name' ));
				}
				/* If the page has a link name, use that; otherwise, use its name */
				$page_name = $sibling->get_value( 'link_name' ) ? $sibling->get_value( 'link_name' ) : $sibling->get_value('name');
				
				$image_html = '';
				
				$is_current_page = false;
				if ( $this->parent->cur_page->id() == $sibling->id() )
				{
					$classes[] = 'currentPage';
					$is_current_page = true;
				}
				
				$link = '';
				
				if(!$is_current_page)
				{
					/* Check for a url (that is, the page is an external link); otherwise, use its relative address */
					if( $sibling->get_value( 'url' ) )
					{
						$link = $sibling->get_value( 'url' );
						$classes[] = 'jump';
					}
					else
					{
						$link = '../'.$sibling->get_value( 'url_fragment' ).'/';
						if (!empty($this->parent->textonly))
							$link .= '?textonly=1';
						//pray($this->parent->site_info);
						//$base_url = $this->parent->site_info[ 'base_url' ];
						//$link = '/'.$base_url.$this->get_nice_url( $child->id() ).'/';
					}
				}
				
				if($this->params['provide_images'])
				{
					$image = $this->get_page_image($sibling->id());
					
					if(!empty($image))
					{
						if($this->params['thumbnail_width'] != 0 or $this->params['thumbnail_height'] != 0)
						{
							$rsi = new reasonSizedImage();
							if(!empty($rsi))
							{
								$rsi->set_id($image->id());
								if($this->params['thumbnail_width'] != 0)
								{
									$rsi->set_width($this->params['thumbnail_width']);
								}
								if($this->params['thumbnail_height'] != 0)
								{
									$rsi->set_height($this->params['thumbnail_height']);
								}
								if($this->params['thumbnail_crop'] != '')
								{
									$rsi->set_crop_style($this->params['thumbnail_crop']);
								}
								$image_html = get_show_image_html($rsi, true, false, false, '' , '', false, $link);
							}
						}
						else
						{
							$image_html = get_show_image_html( $image->id(), true, false, false, '' , '', false, $link );
						}
					}
				}
				
				if ( !$is_current_page )
				{
					if('previous' == $key || 'next' == $key)
						$classes[] = $key;
					echo '<li class="'.implode(' ',$classes).'">';
					if(!empty($prevnext))
						echo '<strong>'.ucfirst($key).':</strong> ';
					echo $image_html;
					echo '<a href="'.$link.'">'.$page_name.'</a>';
					/* if ( $sibling->get_value( 'description' ))
						echo "\n".'<div class="smallText">'.$sibling->get_value( 'description' ).'</div>'; */
					echo "</li>\n";
				}
				else
				{
					echo '<li class="'.implode(' ',$classes).'">';
					echo $image_html;
					echo '<strong>'.$page_name.'</strong>';
					echo '</li>'."\n";
				}
				
				$counter++;
				
				if($even_odd == 'even')
					$even_odd = 'odd';
				else
					$even_odd = 'even';
			}
			echo '</ul>'."\n";
			
			echo '</div>'."\n";
		} // }}}
		/**
		 * Get the image for a given page
		 */
		function get_page_image($page_id)
		{
			$es = new entity_selector();
			$es->set_env( 'site' , $this->site_id );
			$es->add_type(id_of('image'));
			$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_image'));
			$es->set_num(1);
			$es->limit_tables();
			$es->limit_fields();
			if($this->params['randomize_images'])
				$es->set_order('rand()');
			else
				$es->set_order('relationship.rel_sort_order ASC');
			$images = $es->run_one();
			//echo $es->get_one_query();
			if(!empty($images))
			{
				return current($images);
			}
			return false;
		}
	}

?>
