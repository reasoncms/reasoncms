<?php

/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register module with Reason
	 */

	reason_include_once( 'minisite_templates/modules/default.php' );
    reason_include_once( 'classes/sized_image.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ChildrenModule';
	
	
	/**
	 * A minisite module whose output is all the child pages of the current page, in sort order
	 *
	 * Various parameters are available for variant behavior.
	 */
	class ChildrenModule extends DefaultMinisiteModule 
	{
		var $es;
		var $acceptable_params = array(
										'description_part_of_link' => false,
										'provide_az_links' => false,
										'provide_images' => false,
										'randomize_images' => false,
										'show_only_pages_in_nav' => false,
										'show_external_links' => true,
										'exclude' => array(),
										'limit_to' => array(),
										'thumbnail_width' => 0,
										'thumbnail_height' => 0,
										'thumbnail_crop' => ''
									);
		var $offspring = array();
		var $az = array();
		function init( $args = array() ) // {{{
		{
			parent::init( $args );

			$this->es = new entity_selector();
			$this->es->description = 'Selecting children of the page';

			// find all the children of this page
			$this->es->add_type( id_of('minisite_page') );
			$this->es->add_left_relationship( $this->cur_page->id(), relationship_id_of( 'minisite_page_parent' ) );
			if($this->params['show_only_pages_in_nav'])
			{
				$this->es->add_relation('nav_display = "Yes"');
			}
			if(isset($this->params['show_external_links']) && !$this->params['show_external_links'])
			{
				$this->es->add_relation('(url = "" OR url IS NULL)');
			}
			if(!empty($this->params['exclude']))
			{
				$this->es->add_relation('unique_name NOT IN ('.$this->_param_to_sql_set($this->params['exclude']).')');
			}
			if(!empty($this->params['limit_to']))
			{
				$this->es->add_relation('unique_name IN ('.$this->_param_to_sql_set($this->params['limit_to']).')');
			}
			
			$this->es->set_order('sortable.sort_order ASC');
			$this->offspring = $this->es->run_one(); 
			
			if(array_key_exists($this->cur_page->id(), $this->offspring))
			{
				unset($this->offspring[$this->cur_page->id()]);
			}
			
			if(!empty($this->params['provide_az_links']))
			{
				foreach($this->offspring as $child)
				{
					$page_name = $child->get_value( 'link_name' ) ? $child->get_value( 'link_name' ) : $child->get_value('name');
					$letter = carl_strtoupper(substr($page_name,0,1), 'UTF-8');
					if(!in_array($letter, $this->az))
					{
						$this->az[$child->id()] = $letter;
					}
				}
			}

		} // }}}
		function _param_to_sql_set($param)
		{
			if(is_array($param))
			{
				array_walk($param, 'db_prep_walk');
				return implode(',',$param);
			}
			else
			{
				return '"'.addslashes($param).'"';
			}
		}
		function has_content() // {{{
		{
			if( empty($this->offspring) )
			{
				return false;
			}
			else
				return true;
		} // }}}
		function run() // {{{
		{
			/* If the page has no entries, say so */
			if( empty($this->offspring ) )
			{
				echo 'This page has no children<br />';	
			}
			/* otherwise, list them */
			else
			{
				if($this->params['provide_az_links'])
				{
					echo '<div class="childrenAZ">';
					foreach($this->az as $key=>$letter)
					{
						echo '<a href="#child_'.$letter.'">'.$letter.'</a> ';
					}
					echo '</div>';
				}
				$class = 'childrenList';
				if($this->params['provide_images'])
					$class .= ' childrenListWithImages';
				echo '<ul class="'.$class.'">'."\n";
				$counter = 1;
				$even_odd = 'odd';
				
				foreach( $this->offspring AS $child )
				{
					if ( $this->cur_page->id() != $child->id() )
					{
						$this->show_child_page($child,$counter,$even_odd);
						$counter++;
						
						if($even_odd == 'even')
							$even_odd = 'odd';
						else
							$even_odd = 'even';
					}
				}
				echo "</ul>\n";
			}
		} // }}}
		function show_child_page($child,$counter,$even_odd)
		{
			/* If the page has a link name, use that; otherwise, use its name */
			$page_name = $this->get_page_name($child);
			$title_attr = '';
			if( $page_name != $child->get_value('name') )
			{
				$title_attr = ' title="'.reason_htmlspecialchars(strip_tags($child->get_value('name')),ENT_QUOTES).'"';
			}
			$page_name = strip_tags($page_name,'<span><strong><em>');
			$link = $this->get_page_link($child);
			$uname = '';
			if($child->get_value( 'unique_name' ))
			{
				$uname = ' uname-'.reason_htmlspecialchars($child->get_value( 'unique_name' ));
			}
				
			echo '<li class="number'.$counter.' '.$even_odd.$uname.'">';
			
			if($this->params['provide_az_links'] && array_key_exists($child->id(),$this->az))
			{
				echo '<a name="child_'.$this->az[$child->id()].'"></a>';
			}
			if($this->params['provide_images'])
			{
				$image = $this->get_page_image($child->id());
				
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
							show_image($rsi, true, false, false, '' , '', false, $link);
						}
					}
					else
					{
					show_image( $image->id(), true, false, false, '' , '', false, $link );
					}
				}
			}
			if($this->params['description_part_of_link'])
			{
				// needs somewhat different html since inline elements cannot contain block elements
				echo '<a href="'.$link.'"'.$title_attr.'><strong>'.$page_name.'</strong><br />';
				if ( $child->get_value( 'description' ))
				{
					echo "\n".'<span class="childDesc">'.$child->get_value( 'description' ).'</span>';
				}
				echo '</a>';
			}
			else
			{
				echo '<h4><a href="'.$link.'"'.$title_attr.'>'.$page_name.'</a></h4>';
				if ( $child->get_value( 'description' ))
				{
					echo "\n".'<div class="childDesc">'.$child->get_value( 'description' ).'</div>';
				}
			}
			echo '</li>'."\n";
		}
		function get_page_name($page)
		{
			return $page->get_value( 'link_name' ) ? $page->get_value( 'link_name' ) : $page->get_value('name');
		}
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
		function get_page_link($page)
		{
			/* Check for a url (that is, the page is an external link); otherwise, use its relative address */
			if( $page->get_value( 'url' ) )
				$link = $page->get_value( 'url' );
			else
			{
				$link = $page->get_value( 'url_fragment' ).'/';
				if (!empty($this->textonly))
					$link .= '?textonly=1';
			}
			return $link;
		}
		function last_modified() // {{{
		{
			if( $this->has_content() )
			{
				$temp = $this->es->get_max( 'last_modified' );
				return $temp->get_value( 'last_modified' );
			}
			else
				return false;
		} // }}}
		function get_documentation()
		{
			return '<p>Displays links to the current page\'s children. Each link includes the name of the page, along with that page\'s description</p>';
		}
	}

?>
