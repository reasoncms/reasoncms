<?php
	reason_include_once( 'minisite_templates/modules/publication/module.php' );
	$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'publicationHighlightModule';

class publicationHighlightModule extends PublicationModule
{
	//generic 3 variable overrides
	var $has_feed = false;
	var $use_pagination = false;
	
	// Filter settings
	var $use_filters = false;
	
	var $acceptable_params = array (
		'exclude_featured' => 'true',
		'highlight_1' => 'true',
		'highlight_1_num' => 6,
		'highlight_1_date' => 'true',
		'highlight_1_teaser' => 'true',
		'highlight_1_image' => 'false',
		'highlight_2' => 'true',
		'highlight_2_num' => 3,
		'highlight_2_date' => 'true',
		'highlight_2_teaser' => 'true',
		'highlight_2_image' => 'false');
		
		/** 
	* Stores the class names and file names of the markup generator classes used by the module.  
	* Format:  $markup_generator_type => array($classname, $filename)
	* @var array
	*/		
	var $markup_generator_info = array( 'list_item' => array ('classname' => 'HighlightListItemMarkupGenerator', 
										                      'filename' => 'minisite_templates/modules/athletics/markup_generators/highlight_item.php'),
										'list' => array ('classname' => 'HighlightListMarkupGenerator', 
										                 'filename' => 'minisite_templates/modules/athletics/markup_generators/highlight_list.php'),
										'featured_item' => array ('classname' => 'HighlightListItemMarkupGenerator', 
										                          'filename' => 'minisite_templates/modules/athletics/markup_generators/highlight_item.php'),
								   	   );
									   

	/** 
	* Maps the names of variables needed by the markup generator classes to the name of the method that generates them.
	* Same as {@link $item_specific_variables_to_pass}, but these methods cannot take any parameters.
	* @var array
	*/		    									
	var $variables_to_pass = array (   'site' => 'get_site_entity', 
									   'list_item_markup_strings' => 'get_list_item_markup_strings',
									   'featured_item_markup_strings' => 'get_featured_item_markup_strings',
									  //links
	 								   'back_link' => 'construct_back_link',
									   'back_to_section_link' => 'construct_back_to_section_link',
									   //comments
									   //'comment_group' => 'get_comment_group',
									   //'comment_group_helper' => 'get_comment_group_helper',
									   //'comment_moderation_state' => 'get_comment_moderation_state',
									   //issues
									   //'current_issue' => 'get_current_issue', 
									   //'issues_by_date' => 'get_issues_by_date',
									   'links_to_issues' => 'get_links_to_issues',
									   //sections
									   'current_section' => 'get_current_section',
									   'sections' => 'get_sections',
									   'group_by_section' => 'use_group_by_section_view',
									   'items_by_section' => 'get_items_by_section', 
									   'links_to_sections' => 'get_links_to_sections',
									   //'view_all_items_in_section_link' => 'get_all_items_in_section_link',
									);
	
	/**
	* Maps the names of variables needed by the markup generator classes to the name of the method that generates them.
	* Same as {@link variables_to_pass}, but these methods require a news item entity as a parameter.
	* @var array
	*/
	var $item_specific_variables_to_pass = array (	 'link_to_full_item' => 'get_link_to_full_item', 
													 'teaser_image' => 'get_teaser_image',	
													 'highlight_section' => 'get_highlight_section',
												);
	
	function init( $args )
	{
		$this->parent->add_stylesheet( REASON_HTTP_BASE_PATH.'css/publication/highlight.css' );
		parent::init( $args );
	}
	
	function alter_es() // {{{
	{
		parent::alter_es();
		$this->num = $this->params['highlight_1_num'] + $this->params['highlight_2_num'];
		$featured_items = ($this->params['exclude_featured']) ? $this->get_featured_item_ids() : array();
		$this->es->set_num($this->num);
		if (!empty($featured_items))
		{
			$this->es->add_relation("entity.id NOT IN (".implode(',', $featured_items).")");
		}
	} // }}}
	
	function get_featured_item_ids()
	{
		$featured_items = $this->get_featured_items();
		foreach (array_keys($featured_items) as $key)
		{
			$item[] = $key;
		}
		return (isset($item)) ? $item : array();
	}
	
	function get_highlight_section($item)
	{
		return (isset($this->highlight_section[$item->id()])) ? $this->highlight_section[$item->id()] : '0';
	}
		
	// iterate through items and generate array mapping items to highlight sections
	function post_es_additional_init_actions()
	{
		for ($i=0; $i<$this->num; $i++)
		{
			if ($item = current($this->items));
			{
				if ($i < $this->params['highlight_1_num'])
				{
					$this->highlight_section[$item->id()] = 1;
				}
				else
				{
					$this->highlight_section[$item->id()] = 2;
				}
			}
			next($this->items);
		}
	}
	
	// do not display
	function get_login_logout_link()
	{
	}
	
	function construct_permalink()
	{
	}
}

?>
