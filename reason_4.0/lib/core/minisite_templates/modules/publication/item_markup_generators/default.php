<?php
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
*  Generates the full markup for an individual news item or blog post.  
*  Helper class to the publication minisite module.  
*
*  @package reason
*  @subpackage minisite_modules
*  @author Meg Gibbs, Matt Ryan, Nate White
*
*/
class PublicationItemMarkupGenerator extends PublicationMarkupGenerator
{
	var $item;
	var $variables_needed = array('item',
								  'comment_group',
								  'comment_group_helper',
								  'comment_moderation_state',
								  'back_link',
								  'back_to_section_link',
								  'class_vars_pass_to_submodules',
								  'request',
								  'site',
								  'current_issue',
								  'current_section',
								  'publication',
								  'item_events',
								  'item_images',
								  'item_assets',
								  'item_categories',
								  'item_comments',
								  'comment_form_markup',
								  'commenting_status',
								);
	var $submodule_order = array(   'news_comments_added',
									'news_content',
									'news_related_events',
									'news_images',
									'news_assets',
									'news_related',
									'news_categories_submodule',
									'news_display_comments',
									'news_add_comments',																		
								);


	function additional_init_actions()
	{
		/* if(empty($this->use_submodules))
			$this->use_submodules = array_keys($this->submodules);  //defined here so that we always default to using all available submodules. */
		$this->item = $this->passed_vars['item'];
		$creator = $this->item->get_value('created_by');
		if(empty($creator))
			$this->update_entity_to_include_creator($this->item);
	}

	function run()
	{
		$this->markup_string = '';
		$this->markup_string .= '<div class="fullPost">';
		$this->markup_string .= '<div class="primaryContent">'."\n";
		if($this->should_show_comment_added_section())
		{
			$this->markup_string .= '<div class="commentAdded">'.$this->get_comment_added_section().'</div>'."\n";
		}
		$this->markup_string .= '<h3 class="postTitle">'.$this->item->get_value( 'release_title' ).'</h3>'."\n";
		if($this->should_show_date_section())
		{
			$this->markup_string .= '<div class="date">'.$this->get_date_section().'</div>'."\n";
		}
		if($this->should_show_author_section())
		{
			$this->markup_string .= '<div class="author">'.$this->get_author_section().'</div>'."\n";
		}
		if($this->should_show_content_section())
		{
			$this->markup_string .= '<div class="text">'.$this->get_content_section().'</div>'."\n";
		}
		if($this->should_show_comments_section() || $this->should_show_comment_adder_section())
		{
			$this->markup_string .= $this->get_back_links_markup();
		}
		if($this->should_show_comments_section())
		{
			$this->markup_string .= '<div class="comments">'.$this->get_comments_section().'</div>'."\n";
		}
		if($this->should_show_comment_adder_section())
		{
			$this->markup_string .= '<div class="addCommentForm">'.$this->get_comment_adder_section().'</div>'."\n";
		}
		$this->markup_string .= '</div>'."\n";
		if($this->should_show_related_events_section() || $this->should_show_images_section() || $this->should_show_assets_section() || $this->should_show_categories_section())
		{
			$this->markup_string .= '<div class="relatedItems">'."\n";
			if($this->should_show_related_events_section())
			{
				$this->markup_string .= '<div class="relatedEvents">'.$this->get_related_events_section().'</div>'."\n";
			}
			if($this->should_show_images_section())
			{
				$this->markup_string .= '<div class="images">'.$this->get_images_section().'</div>'."\n";
			}
			if($this->should_show_assets_section())
			{
				$this->markup_string .= '<div class="assets">'.$this->get_assets_section().'</div>'."\n";
			}
			if($this->should_show_categories_section())
			{
				$this->markup_string .= '<div class="categories">'.$this->get_categories_section().'</div>'."\n";
			}
			$this->markup_string .= '</div>'."\n";
		}
		$this->markup_string .= '</div>';
	}
	
	/**
	 * Answers question: should the "Comment Added" section be displayed?
	 *
	 * Basically this should answer true if a comment was just posted (e.g. the request has a value for comment_posted_id)
	 *
	 * @return boolean
	 */
	function should_show_comment_added_section()
	{
		if(!empty($this->passed_vars['request']['comment_posted_id']))
			return true;
		else
			return false;
	}
	
	/**
	 * Build the markup for the "Comment Added" section
	 *
	 * This section exists to inform the user that their comment was a) added, or b) held for review
	 *
	 * @return string 
	 */
	function get_comment_added_section()
	{
		$ret = '';
		if($this->passed_vars['publication']->get_value('hold_comments_for_review') == 'yes')
		{
			$ret .= '<h4>Comments are being held for review on this publication.  Please check back later to see if your comment has been posted.</h4>';
		}
		else
		{
			$ret .= '<h4>Your comment has been added.</h4>';
			$ret .= '<a href="#comment'.$this->passed_vars['request']['comment_posted_id'].'">Jump to your comment</a>';
		}
		return $ret;
	}
	function should_show_date_section()
	{
		if( $this->item->get_value( 'datetime' ) )
			return true;
		else
			return false;
	}
	function get_date_section()
	{
		// todo: use publication date format setting
		return prettify_mysql_datetime($this->item->get_value( 'datetime' ), 'F j, Y \a\t g:i a');
	}
	function should_show_author_section()
	{
		if( $this->item->get_value( 'author' ) )
			return true;
		else
			return false;
	}
	function get_author_section()
	{
		return 'By '.$this->item->get_value( 'author' );
	}
	function should_show_content_section()
	{
		if($this->item->get_value('content') || $this->item->get_value('description'))
			return true;
		else
			return false;
	}
	function get_content_section()
	{
		if( $this->item->get_value( 'content' ) )
		{
			return $this->alter_content($this->item->get_value( 'content' ) );
		}
		else
		{
			return $this->alter_content( $this->item->get_value('description') );
		}
	}
	function alter_content($content)
	{
		if(strpos($content,'<h3') !== false || strpos($content,'<h4') !== false)
		{
			$content = tagSearchReplace($content, 'h4', 'h5');
			$content = tagSearchReplace($content, 'h3', 'h4');
		}
		return $content;
	}
	
	// Related events section
	function should_show_related_events_section()
	{
		if(!empty($this->passed_vars['item_events']))
			return true;
		else
			return false;
	}
	function get_related_events_section()
	{
		$str = '<h4>Related Events</h4>';
		$str .= '<ul>';
		foreach($this->passed_vars['item_events'] as $event)
		{
			$str .= '<li>';
			if($event->get_value('event_url'))
				$str .= '<a href="'.$event->get_value('event_url').'">'.$event->get_value('name').'</a>';
			else
				$str .= $event->get_value('name');
			$str .= '</li>';
		}
		$str .= '</ul>';
		return $str;
	}
	
	// Images section
	function should_show_images_section()
	{
		if(!empty($this->passed_vars['item_images']))
			return true;
		else
			return false;
	}
	function get_images_section()
	{
		$str = '<h4>Images</h4>';
		$str .= '<ul>';
		foreach($this->passed_vars['item_images'] as $image)
		{
			$str .= '<li>';
			ob_start();
			$textonly = false;
			if(!empty($this->passed_vars['request']['textonly']))
				$textonly = true;
			show_image( $image, false, true, true, '', $textonly );
			$str .= ob_get_contents();
			ob_end_clean();
			$str .= '</li>';
		}
		$str .= '</ul>';
		return $str;
	}
	
	// Assets section
	function should_show_assets_section()
	{
		if(!empty($this->passed_vars['item_assets']))
			return true;
		else
			return false;
	}
	function get_assets_section()
	{
		reason_include_once( 'function_libraries/asset_functions.php' );
		$str = '<h4>Related Documents</h4>';
		$str .= make_assets_list_markup( $this->passed_vars['item_assets'], $this->passed_vars['site'] );
		return $str;
	}
	
	// Categories section
	function should_show_categories_section()
	{
		if(!empty($this->passed_vars['item_categories']))
			return true;
		else
			return false;
	}
	function get_categories_section()
	{
		$ret = '<h4>Categories</h4>';
		$ret .= '<ul>';
		foreach($this->passed_vars['item_categories'] as $category)
		{
			$ret .= '<li><a href="'.$category->get_value('category_url').'">'.$category->get_value('name').'</a></li>';
		}
		$ret .= '</ul>';
		return $ret;
	}
	
	// Comments section
	function should_show_comments_section()
	{
		if(!empty($this->passed_vars['item_comments']))
			return true;
		else
			return false;
	}
	function get_comments_section()
	{
		$ret = '<a name="comments"></a>';
		$ret .= '<h4>Comments</h4>'."\n";
		
		if(!empty($this->passed_vars['item_comments']))
		{
			$ret .=  '<ul>';
			foreach($this->passed_vars['item_comments'] as $comment)
			{
				$ret .=  '<li><a name="comment'.$comment->id().'"></a>';
				// todo: use publication date format
				$ret .=  '<div class="datetime">'.prettify_mysql_datetime($comment->get_value('datetime'), 'F j Y \a\t g:i a').'</div>';
				$ret .= '<div class="author">'.$comment->get_value('author').'</div>';
				$ret .= '<div class="commentContent">'.$comment->get_value('content').'</div>';
				$ret .= '</li>';
			}
			$ret .= '</ul>';
		}
		else
		{
			$ret .= '<p>There are no comments yet for this post.</p>';
		}
		
		return $ret;
	}
	
	// Comment adder section
	function should_show_comment_adder_section()
	{
		if($this->passed_vars['commenting_status'] == 'publication_comments_off')
			return false;
		else
			return true;
	}
	function get_comment_adder_section()
	{
		$ret = '';
		switch($this->passed_vars['commenting_status'])
		{
			case 'publication_comments_off':
				break;
			case 'item_comments_off':
				$ret .= '<h4>Comments for this post are turned off</h4>';
				break;
			case 'login_required':
				$ret .= '<h4>Add a comment</h4>'."\n";
				$ret .= '<p>Please <a href="'.REASON_LOGIN_URL.'"> login </a> to comment.</p>';
				break;
			case 'user_not_permitted':
				$ret .= '<h4>Commenting Restricted</h4>'."\n";
				$ret .= '<p>You do not currently have the rights to post a comment. If you would like to comment, please contact the site maintainer listed on this page.</p>';
				break;
			case 'open_comments':
			case 'user_has_permission':
				$ret .= '<h4>Add a comment</h4>'."\n";
				$ret .= $this->passed_vars['comment_form_markup'];
				break;
			default:
				trigger_error( 'commenting_status not an expected value ('.$this->passed_vars['commenting_status'].')' );
		}
		return $ret;
	}

	function update_entity_to_include_creator($entity)
	{
		$creator = $this->find_creator($entity);
		$flat_values = array('created_by' => $creator);
		$tables = get_entity_tables_by_type(id_of('news'));
		$successful = update_entity( 
				$entity->id(), 
# shouldn't be using my user_id - which one?
				get_user_id('gibbsm'),
				values_to_tables($tables, $flat_values,  $ignore = array())
		); 		
	}

	function find_creator($item)
	{
		$site = $this->passed_vars['site'];
		$es = new entity_selector($site->id());
		$es->add_type( id_of('news') );
		$es->add_right_relationship( $item->id(), relationship_id_of('news_archive') );
     	$es->set_order( 'entity.last_modified ASC' );
		$archive = $es->run_one(false, 'Archived');
		if(empty($archive))
			return $item->get_value('last_edited_by');
		else
		{
			$first = current($archive);
			return $first->get_value('last_edited_by');
		}
	}
	
	/**	
	* Used to modify the default paramaters in the submodule, or by children to add new submodules to the submodule array.  
	* The submodule array should ALWAYS be accessed using this function.  
	* @return array the submodule array
	*/	
	function get_submodules()
	{
		//set the defaults that can't be set in the global variable section
		$this->submodules['news_add_comments']['comments_moderated'] = $this->passed_vars['comment_moderation_state'];
		$this->submodules['news_add_comments']['group'] = $this->passed_vars['comment_group']; 

		$this->submodules['news_add_comments']['back_link'] = $this->get_back_links_markup();
		$this->submodules['news_display_comments']['back_link'] = $this->get_back_links_markup();
 
		$cgh = $this->passed_vars['comment_group_helper'];
		if(!$cgh->group_has_members())
		{
			//the default comments_off_message says comments have been turned off for a POST; when no comments for entire pub, want no message to show up
			$this->submodules['news_add_comments']['comments_off_message'] = ''; 
		}
		return $this->submodules;
	}

	/**	
	* Displays the content of a news item by running the submodules defined in $use_submodules and 
	* displaying their output in the order defined by $submodule_order. 
	* @param entity news item
	*/	  
	function run_submodules()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		$submodules_output = array();
		$site = $this->passed_vars['site'];
		$submodule_params = $this->get_submodules();

		//we don't care about the submodule order when we're just getting the output - it's all going into an array, anyway.
		foreach($this->use_submodules as $sub_name)
		{
			$output = $this->get_submodule_output($item, $sub_name, $site);
			if($output)
			{
				$submodules_output[$sub_name]  = $output;
			}
		}

		//we do care about order when we're displaying the output, however.
		if(!empty($submodules_output))
		{
			//so we check to see if anything in submodules doesn't have a place in the order of submodules
			$unordered_submodules = array();
			foreach($submodules_output as $sub_name => $output)
			{
				$class_name[] = 'contains' . ucfirst($this->submodules[$sub_name]['wrapper_class']);
				if(!in_array($sub_name, $this->submodule_order))
				{
					$unordered_submodules[] = $sub_name;
					trigger_error('The submodule "'.$sub_name.'" does not appear in the $submodule_order array',WARNING);
				}
			}
		
			if(count($submodules_output > 1))
			{
				$additional_class_text = ' multiple ' . implode($class_name, ' ');
			}
			else
			{
				$additional_class_text = '';
			}
			$markup_string .= '<div class="submodules'.$additional_class_text.'">'."\n";

			//and then display the output in the defined order of submodules			
			$submodule_order = array_merge($this->submodule_order, $unordered_submodules);
			foreach($submodule_order as $sub_name)
			{
				if(array_key_exists($sub_name, $submodules_output))
				{
					$markup_string .=  '<div class="'.$submodule_params[$sub_name]['wrapper_class'].'">'."\n".$submodules_output[$sub_name].'</div>'."\n";
				}
			}
			$markup_string .=  '</div>'."\n";
			return $markup_string;
		}
	}

	/**	
	* Runs a given submodule and returns the output.
	* @param entity news item
	* @param string submodule name
	* @param entity site entity for this site
	* @return string submodule output
	*/	
	function get_submodule_output($item, $sub_name, $site)
	{
		$this->include_filename($sub_name);
		$sub = new $sub_name();
		$submodules = $this->get_submodules();
		
		$sub->pass_params($submodules[$sub_name]);
		$sub->pass_site($site);
		if(!empty($this->passed_vars['class_vars_pass_to_submodules']))
		{
			$add_vars = array();
			foreach($this->passed_vars['class_vars_pass_to_submodules'] as $var)
			{
				if(!empty($this->$var))
				{
					$add_vars[$var] = $this->$var;
				}
				elseif(!empty($this->passed_vars[$var]))
				{
					$add_vars[$var] = $this->passed_vars[$var];
				}
			}
			if(!empty($add_vars))
			{
				$sub->pass_additional_vars($add_vars);
			}
		}
		$sub->init($this->passed_vars['request'], $item);
		if($sub->has_content())
		{
			return $sub->get_content();
		}
		else
			return false;
	}

	/**	
	* Includes the file for a given submodule, or triggers an error if the file cannot be found.
	* @param string name of submodule to be included
	*/	
	//this probably needs some more checks ... e.g., does the class exist?  compare to default minisite template
	function include_filename($submodule_name)
	{
#		$base = $_SERVER[ 'DOCUMENT_ROOT' ]/*.'/global_stock/php/'*/;
		$include = /*REASON_INC.'lib/core/*/'minisite_templates/modules/publication/submodules/'.$submodule_name;
		if( file_exists( REASON_INC.'lib/core/'.$include.'.php' ) )
		{
			reason_include_once( $include.'.php' );
		}
		else
		{
			trigger_error('The submodule class file for "'.$submodule_name.'" cannot be found',WARNING);
		}
	}

	
	function get_back_links_markup()
	{
		$markup_string = '';
		$markup_string .= '<div class = "back">';
		$markup_string .= '<div>'.$this->get_back_link_markup().'</div>';
		$markup_string .= '<div>'.$this->get_back_to_section_link_markup().'</div>';
		$markup_string .= '</div>';
		return $markup_string;
	}
	
	//returns the markup for the link back to the main list of the publication/issue
	function get_back_link_markup()
	{
		return '<a href="'.$this->passed_vars['back_link'].'">Return to '.$this->get_main_list_name().'</a>';
	}
	
	//returns the name of the main list (either the publication or the publication with the issue we were looking at)
	function get_main_list_name()
	{
		$main_list_name = $this->passed_vars['publication']->get_value('name');
		$current_issue = $this->passed_vars['current_issue'];
		if(!empty($current_issue))
			$main_list_name .= ': '.$current_issue->get_value('name');
		return $main_list_name;
	}
	
	//returns the markup for the link back to the list of just one section if that's where we came from
	//if we didn't, returns false
	function get_back_to_section_link_markup()
	{
		$current_section = $this->passed_vars['current_section'];
		if(!empty($current_section))
		{
			$section_name = $current_section->get_value('name');
			$section_url = $this->passed_vars['back_to_section_link'];
			$link = '<a href="'.$section_url.'">Return to '.$section_name.' ('.$this->get_main_list_name().')</a>';
			return $link;
		}
		else
			return false;
	}

}
?>
