<?php
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
*  Generates the full markup for an individual news item or blog post.  
*  Helper class to the publication minisite module.  
*
*  @package reason
*  @subpackage minisite_modules
*  @author Meg Gibbs
*
*/
class PublicationItemMarkupGenerator extends PublicationMarkupGenerator
{
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
								  'publication'
								);

	/**
	* List of all the submodules for this module with the default parameters for each submodule.
	* This should be accessed through the get_submodules() method, not directly.
	* @var array
	*/
	var $submodules = array(    'news_comments_added'=>array('title'=>'Your comment has been added.',
														'title_tag'=>'h4',
														'wrapper_class'=>'commentAdded',
													),
								'news_content'=>array(	'title'=>'',
														'title_tag'=>'',
														'wrapper_class'=>'text',
														'date_format' => 'F j, Y \a\t g:i a',
													),
								'news_related_events'=>array ( 'title'=>'Related Events',
															   'title_tag'=>'h4',
															   'wrapper_class'=>'relatedEvents'
													),
								'news_images'=>array(	'title'=>'Images',
														'title_tag'=>'h4',
														'wrapper_class'=>'images',
													),
								'news_assets'=>array(	'title'=>'Related Documents',
														'title_tag'=>'h4',
														'wrapper_class'=>'assets',
													),
								'news_related'=>array(	'title'=>'Related Stories',
														'title_tag'=>'h4',
														'wrapper_class'=>'relatedNews',
													),
								'news_categories_submodule'=>array(	'title'=>'Categories',
														'title_tag'=>'h4',
														'wrapper_class'=>'categories',
													),
								'news_display_comments'=>array(	'title'=>'Comments',
													'title_tag'=>'h4',
													'wrapper_class'=>'comments',
													'date_format' => 'F j, Y \a\t g:i a',
													'back_link' => '',
												),
								'news_add_comments'=>array(	'title'=>'Add a Comment',
														'title_tag'=>'h4',
														'public'=>false,
														'wrapper_class'=>'addCommentForm',
														'comments_moderated' => false, 
														'group' => '',
														'back_link' => '',
													),
								/*'news_return_to_list' => array ( 'title'=>'Return to',
													 	 'wrapper_class'=>'returnToList',
														), */
								); 
	/**
	* List of submodules that should be called.
	* @var array
	*/
	var $use_submodules = array();  // defined in init(), so that our default is to use everything defined in the $submodules array

	/**
	* The order in which submodules should be called.  If a submodule being used is not included in this array, it will be displayed after
	* the submodules defined in this array - but you should include all submodules that you're using in this array anyway.
	* @var array
	*/							
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


	function PublicationListMarkupGenerator ()
	{
	}

	function additional_init_actions()
	{
		if(empty($this->use_submodules))
			$this->use_submodules = array_keys($this->submodules);  //defined here so that we always default to using all available submodules.
		$item = $this->passed_vars['item'];
		$creator = $item->get_value('created_by');
		if(empty($creator))
			$this->update_entity_to_include_creator($item);
	}

	function run()
	{	
		$this->markup_string = $this->run_submodules();
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
