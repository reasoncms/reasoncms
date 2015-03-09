<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 *  Register the module with Reason
 */
$GLOBALS[ '_module_class_names' ][ 'publication' ] = 'PublicationModule';
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'PublicationModule';

/**
 * Include parent class
 */
include_once( 'reason_header.php' );
reason_include_once( 'minisite_templates/modules/generic3.php' );
reason_include_once( 'classes/page_types.php' );
reason_include_once( 'classes/inline_editing.php' );
/**
* A minisite module to handle publications, including blogs, issued newsletters, and newsletters.
* 
* This module attempts to separate logic and markup as much as possible in order to maximize flexibility in markup;
* the logic is handled by this class, while the markup is created by easily extensible markup generator classes.  
*
* @package reason
* @subpackage minisite_modules
*
* @author Meg Gibbs
* @author Matt Ryan
* @author Nathan White
*
* @todo Move any remaining markup in the publication module that could possibly be removed to the appropriate markup generator *or* make an ubermarkupgenerator that handles the surrounding html
* @todo Alter language from being blog-oriented to being publication-oriented.
* @todo fix featured items/issues interaction
* @todo improve mathod of removing featured items from other item lists so that specified number of items in section remains correct
*/	
class PublicationModule extends Generic3Module
{
////////
// VARIABLES
////////	

	//generic 3 variable overrides
	var $query_string_frag = 'story';
	var $pagination_prev_next_texts = array('previous'=>'Newer','next'=>'Older');
	var $use_dates_in_list = true; // this is kinda pointless, since this module is a) never meant to be overloaded, and b) we have morkup generators that can decide this on their own. So the default markup generator now ignores it.
	var $show_list_with_details = false;
	var $has_feed = true;
	var $jump_to_item_if_only_one_result = false;
	var $make_current_page_link_in_nav_when_on_item = true;
	var $back_link_text = 'Return to ';
	var $feed_url;
	
	var $style_string = 'blog';
	var $use_pagination = true;
	var $no_items_text = 'This publication does not have any news items yet.';
	var $date_format = 'F j, Y \a\t g:i a';		//will be replaced if 'date_format' field for publication is set
	var $num_per_page = 12;	
	var $max_num_items = '';
	var $minimum_date_strtotime_format;
	var $minimum_date;
	
	// Filter settings
	var $use_filters = true;
	var $filter_types = array(	'category'=>array(	'type'=>'category_type',
													'relationship'=>'news_to_category',
												 ),
							);
	var $search_fields = array('entity.name','chunk.content','meta.keywords','meta.description','chunk.author','press_release.release_title');
	var $search_field_size = 10;
	
	
	//variables original to this module
	var $publication;	//entity of the current publication
	var $item;			//entity of the news item being viewed 
	var $user_netID; 	//current user's net_ID
	var $session;		//reason session
	var $additional_query_string_frags = array ('comment_posted', 'issue', 'section');
	var $issue_id;							// id of the issue being viewed - this is a class var since the most recent issue won't necessarily be in $_REQUEST
	var $all_issues = array();
	var $issues = array();					// $issue_id => $issue_entity
	var $sections = array();				// $section_id => $section_entity
	var $sections_by_issue = array();				// $issue_id => array($section_id => $section_entity)
	var $no_section_key = 'no_section';		//key to be used in the items_by_section array when there are no sections.
	var $group_by_section = true;			//whether or not items should be grouped by section when displayed
	var $show_module_title = false; // page title module generally handles this
	
	// related mode variables - page type configurable
	var $related_mode = false;      // in related_mode, related publication items are aggregated
	var $related_order = ''; 		// allows for keywords for custom order and special considerations for related items
	var $related_title; // page type can provide specific title or keyword which will be used instead of the default
	var $limit_by_page_categories = false; // by default page to category relationship is ignored - can be enabled in page type
	
	var $related_publications;
	var $related_publications_links = array();
	var $related_categories;

	var $show_login_link = true;
	var $show_featured_items;
	var $queried_for_events_page_url = false;
	var $events_page_url = '';
	var $comment_form_file_location = 'minisite_templates/modules/publication/forms/submit_comment.php';
	var $post_form_file_location = 'minisite_templates/modules/publication/forms/submit_post.php';
	var $commenting_status = array();
	var $css = 'css/publication/default_styles.css'; // style sheet(s) to be added
	var $_ok_to_view = true;
	var $_unauthorized_message = NULL;
	var $_items_by_section = array(); // a place to cache items organized by section
	var $_blurbs_by_issue; // a place to cache blurbs organized by issue
	var $_comment_group_helper; // The helper for the publication's comment group	
	var $_comment_has_errors;
	protected $_item_images = array();
	protected $_item_media = array();
		
	/** 
	* Stores the default class names and file names of the markup generator classes used by the module.  
	* Format:  $markup_generator_type => array($classname, $filename)
	* @var array
	*/		
	var $markup_generator_info = array( 'item' => array ('classname' => 'PublicationItemMarkupGenerator', 
														 'filename' => 'minisite_templates/modules/publication/item_markup_generators/default.php',
														 //'settings' => array()
														 ),
										'list_item' => array ('classname' => 'PublicationListItemMarkupGenerator', 
										                      'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/default.php',
										                      //'settings' => array()
										                      ),
										'list' => array ('classname' => 'PublicationListMarkupGenerator', 
										                 'filename' => 'minisite_templates/modules/publication/publication_list_markup_generators/default.php',
										                 //'settings' => array()
										                 ),
										'featured_item' => array ('classname' => 'PublicationListItemMarkupGenerator', 
										                          'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/default.php',
										                          ),
										'issue_list' => array ('classname' => 'PublicationIssueListMarkupGenerator',
															   'filename' => 'minisite_templates/modules/publication/issue_list_markup_generators/default.php',
															   ),
										'persistent' => array('classname' => 'PublicationsPersistentMarkupGenerator',
															  'filename' => 'minisite_templates/modules/publication/persistent_markup/default.php',
															  ),
								   	   );
								   	   
	var $related_markup_generator_info = array( 'list_item' => array ('classname' => 'RelatedListItemMarkupGenerator', 
										                  'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item.php',
										         ),
												'list' => array ('classname' => 'RelatedListMarkupGenerator', 
										                 'filename' => 'minisite_templates/modules/publication/publication_list_markup_generators/related_list.php',
										                 ),
										        'featured_item' => array ('classname' => 'RelatedListItemMarkupGenerator', 
										                          'filename' => 'minisite_templates/modules/publication/list_item_markup_generators/related_item.php',
										                          ),
										        'persistent' => array ('classname' => 'EmptyMarkupGenerator', 
										                          'filename' => 'minisite_templates/modules/publication/empty_markup_generator.php',
										                          ),
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
									   'back_to_filters_link' => 'construct_back_to_filters_link',
									   //comments
									   'comment_group' => 'get_comment_group',
									   'comment_group_helper' => 'get_comment_group_helper',
									   'comment_moderation_state' => 'get_comment_moderation_state',
									   //issues
									   'current_issue' => 'get_current_issue', 
									   'issues_by_date' => 'get_issues',
									   'links_to_issues' => 'get_links_to_issues',
									   'issue_blurbs' => 'get_current_issue_blurbs',
									   //sections
									   'current_section' => 'get_current_section',
									   'sections' => 'get_sections_issue_aware',
									   'group_by_section' => 'use_group_by_section_view',
									   'items_by_section' => 'get_items_by_section', 
									   'links_to_sections' => 'get_links_to_sections',
									   'view_all_items_in_section_link' => 'get_all_items_in_section_link',
									   'links_to_current_publications' => 'get_links_to_current_publications',
									   'publication'=>'get_publication_entity',
									   'search_string' => 'get_sanitized_search_string',
									   'text_only' => 'get_text_only_state',
									   'current_filters' => 'get_current_filter_entities',
									   'inline_editing_info' => 'get_inline_editing_info',
									   'filter_interface_markup'=>'get_filter_interface_markup',
									   'search_interface_markup'=>'get_search_interface_markup',
									   'pagination_markup' => 'get_pagination_markup',
									   'add_item_link' => 'get_add_item_link',
									   'login_logout_link' => 'get_login_logout_link',
									   'use_filters' => 'get_use_filters_value',
									   'filtering_markup' => 'get_filtering_markup',
									);
	
	/**
	* Maps the names of variables needed by the markup generator classes to the name of the method that generates them.
	* Same as {@link variables_to_pass}, but these methods require a news item entity as a parameter.
	* @var array
	*/
	var $item_specific_variables_to_pass = array (	 'item_comment_count' => 'count_comments', 
													 'link_to_full_item' => 'get_link_to_full_item',
													 'link_to_related_item' => 'get_link_to_related_item',
													 'permalink' => 'construct_permalink',
													 'teaser_image' => 'get_teaser_image',
													 'section_links' => 'get_links_to_sections_for_this_item',
													 'item_number' => 'get_item_number',
													 'item_publication' => 'get_item_publication',
													 'item_events' => 'get_item_events',
													 'item_images' => 'get_item_images',
													 'item_media' => 'get_item_media',
													 'item_assets' => 'get_item_assets',
													 'item_categories' => 'get_item_categories',
													 'item_social_sharing' => 'get_item_social_sharing',
													 'item_comments' => 'get_item_comments',
													 'comment_form_markup'=>'get_comment_form_markup',
													 'comment_has_errors' => 'get_comment_has_errors',
													 'commenting_status' => 'commentability_status',
													 'previous_post' => 'get_next_post',
													 'next_post' => 'get_previous_post',
												);
											   

	//var $acceptable_params
	
var $noncanonical_request_keys = array(
								'filters',
								'search',
								'page',
								'textonly',
								'add_item',
								'filter1',
								'filter2',
								'filter3',
								'comment_posted_id');

////////
// INIT-RELATED METHODS
////////	

	/**
	*	Extended from generic3 so that the generic3 init function is called on ONLY if there is actually 
	*   a publication associated with the page.  We don't want any orphaned news items showing up.  
	*/
	function init( $args = array() ) 
	{
		$this->set_defaults_from_parameters($this->params);
		$this->set_show_featured_items();
		$this->set_minimum_date();
	
		if ($this->related_mode) $this->init_related( $args );
		elseif (!empty($this->publication)) parent::init( $args );
		else
		{
			//make sure that there's a publication associated with this page before we do anything else.  
			$pub_es = new entity_selector( $this->site_id );
			$pub_es->description = 'Selecting publications for this page';
			$pub_es->add_type( id_of('publication_type') );
			$pub_es->add_right_relationship( $this->page_id, relationship_id_of('page_to_publication') );
			$pub_es->set_num( 1 );
			$publications = $pub_es->run_one();
			if(!empty($publications))
			{
				//defining variables and such should usually go in the additional_init_actions(), so we only define the publication here
				$this->publication = current($publications);
				parent::init( $args );
			}
			elseif(!empty($this->params['no_publication_warning']))
			{
				trigger_error('No publications are associated with this publication page');
			}
		}
		if(!empty($this->css))
		{
			$this->parent->add_stylesheet(REASON_HTTP_BASE_PATH.$this->css,'',true);
		}
		
		if (!$this->related_mode)
		{
			// Register this module for inline editing
			$inline_edit =& get_reason_inline_editing($this->page_id);
			$inline_edit->register_module($this, $this->user_can_inline_edit());
			
			// Only load inline_editing javascript if inline editing is available for the module and active for the module
			if ($inline_edit->available_for_module($this) && $inline_edit->active_for_module($this))
			{
				$head_items =& $this->get_head_items();
				$head_items->add_javascript(JQUERY_URL, true);
				$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/publications/inline_editing.js');
			}
		}
	}
	
	function set_show_featured_items()
	{
		if (isset($this->params['show_featured_items'])) $this->show_featured_items = $this->params['show_featured_items']; // set from parameter if present
		if (!isset($this->show_featured_items))
		{	
			$this->show_featured_items = ($this->related_mode) ? false : true;
		}
	}
	
	function set_minimum_date()
	{
		if (isset($this->params['minimum_date_strtotime_format']))
		{
			// minimum_date_strtotime_format should be a string usable to strtotime and also in the past not the future - trigger an error if not.
			$cur_timestamp = time();
			$min_date = strtotime($this->params['minimum_date_strtotime_format']);
			if ($min_date && ($min_date != -1) && ($min_date < $cur_timestamp))
			{
				$this->minimum_date = get_mysql_datetime($min_date);
			}
			elseif (!$min_date || ($min_date == -1))
			{
				trigger_error('A minimum date value was not set as the value of minimum_date_strtime_format 
							  ('.$this->params['minimum_date_strtotime_format'].') is not a valid argument for strtotime.');
			}
			elseif ($min_date > $cur_timestamp)
			{
				trigger_error('A minimum date value was not set. The value of minimum_date_strtime_format 
							  ('.$this->params['minimum_date_strtotime_format'].') must reference a date in the past.');
			}
		}
	}
	/**
	 * Init when publication is in related_mode
	 * @author Nathan White
	 */
	function init_related( $args = array())
	{
		// init defaults
		$this->use_filters = false;
		$this->show_login_link = false;
		$this->use_pagination = (isset($this->params['use_pagination']) && $this->params['use_pagination']) ? true : false;
		if (!$this->use_pagination && empty($this->max_num_items)) $this->max_num_items = $this->num_per_page;
		$this->style_string = 'relatedPub';
		unset ($this->request[ $this->query_string_frag.'_id' ] );
		
		$publication_ids = (!empty($this->params['related_publication_unique_names'])) 
						   ? $this->build_ids_from_unique_names($this->params['related_publication_unique_names'])
						   : array();
		
		$pub_es = new entity_selector();
		
		$pub_es->description = 'Selecting publications for this page';
		$pub_es->add_type( id_of('publication_type') );
		$pub_es->enable_multivalue_results();
		$pub_es->limit_tables();
		$pub_es->limit_fields();
		if (!empty($publication_ids)) $pub_es->add_relation('entity.id IN (' . implode(",", array_keys($publication_ids)) . ')');
		else $pub_es->add_right_relationship( $this->page_id, relationship_id_of('page_to_related_publication') );
		$pub_es->add_right_relationship_field('page_to_publication', 'entity', 'id', 'page_id');
		$publications = $pub_es->run_one();
		if (empty($publications))
		{
			//$s = get_microtime();
			$pub_es = new entity_selector( $this->site_id );
			$pub_es->description = 'Selecting publications for this page';
			$pub_es->add_type( id_of('publication_type') );
			$pub_es->enable_multivalue_results();
			$pub_es->limit_tables();
			$pub_es->limit_fields();
			$pub_es->add_right_relationship_field('page_to_publication', 'entity', 'id', 'page_id');	
			$publications = $pub_es->run_one();
		}
		if (!empty($publications)) // lets make sure the pages are live
		{
			$publications = $this->_filter_non_live_related_ids($publications, 'minisite_page', 'page_id');
			$publications = $this->_filter_pubs_with_improper_page_types($publications);
		}
		if (!empty($publications))
		{
			$this->related_publications = $publications;
			
			if ($this->limit_by_page_categories)
			{
				$category_ids = (!empty($this->params['related_category_unique_names'])) 
								? $this->build_ids_from_unique_names($this->params['related_category_unique_names'])
								: array();
				// grab categories in which to limit related news items
				$cat_es = new entity_selector();
				$cat_es->description = 'Selecting categories for this page';
				$cat_es->add_type( id_of('category_type'));
				$cat_es->limit_tables();
				$cat_es->limit_fields();
				if (!empty($category_ids)) $cat_es->add_relation('entity.id IN (' . implode(",", array_keys($category_ids)) . ')');
				else $cat_es->add_right_relationship($this->page_id, relationship_id_of('page_to_category') );
				$categories = $cat_es->run_one();
				if (!empty($categories))
				{
					$this->related_categories = $categories;
				}
			}
			parent::init( $args );
		}
		elseif(!empty($this->params['no_publication_warning']))
		{
			trigger_error('Publication module unable to find an active publication to display.');
		}
	}
		
	/**
	 * Crawl through a set of entites and look at a related_id_field - this is needed because there is not a reliable way to
	 * add an entity id through add_right_relationship_field or add_left_relationship_field in a manner that ensures the entity
	 * id added corresponds to a live entity. This is an issue for pages in particular that have become pending but hold a publication.
	 *
	 * - if any related_ids are not live, filter them out.
	 * - If none are live, remove the entity from the set
	 */
	function _filter_non_live_related_ids($entities, $related_id_type_unique_name, $related_id_field)
	{
		$all_entity_ids = array();
		foreach($entities as $entity)
		{
			$entity_ids = $entity->get_value($related_id_field);
			$entity_array = (is_array($entity_ids)) ? $entity_ids : array($entity_ids);
			$all_entity_ids = array_merge($all_entity_ids, $entity_array);
		}
		$all_entity_ids = array_unique($all_entity_ids);
		$es = new entity_selector();
		$es->add_type(id_of($related_id_type_unique_name));
		$es->limit_tables();
		$es->limit_fields();
		$es->add_relation('entity.id IN (' . implode(",", $all_entity_ids) . ')');
		$es->add_relation('entity.state != "Live"');
		$result = $es->run_one("", "All");		
		if ($result) // we have non live entities that we need to filter out
		{
			$filtered_entities = array();
			$ids_to_filter = array_keys($result);
			foreach ($entities as $id => $entity)
			{
				$entity_ids = $entity->get_value($related_id_field);
				$entity_array = (is_array($entity_ids)) ? $entity_ids : array($entity_ids);
				$remaining_values = array_values(array_diff($entity_array, $ids_to_filter));
				if (!empty($remaining_values))
				{
					$remaining_values = (count($remaining_values) == 1) ? $remaining_values[0] : $remaining_values;
					$entity->set_value($related_id_field, $remaining_values);
					$filtered_entities[$id] = $entity;
				}
			}
			return $filtered_entities;
		}
		else return $entities;
	}
	
	/**
	 * Checks to make sure the page_id for a publication runs the publication module in a region and it is not set to related mode.
	 * @param array publication entities with page_id populated
	 */
	function _filter_pubs_with_improper_page_types($publications)
	{
		if (!empty($publications))
		{
			$filtered_publications = array();
			foreach ($publications as $pub_id => $publication)
			{
				$page_ids = $publication->get_value('page_id');
				$page_ids_array = (is_array($page_ids)) ? $page_ids : array($page_ids);
				foreach ($page_ids_array as $page_id)
				{
					// does the page id have a page type with a valid publications module?
					if (!empty($page_id))
					{
						$page = new entity($page_id);
						$page_type_name = $page->get_value('custom_page');
						$rpts =& get_reason_page_types();
						$pt = $rpts->get_page_type($page_type_name);
						$regions = $pt->get_region_names();
						foreach ($regions as $region)
						{
							$region_info = $pt->get_region($region);
							$valid = ($region_info['module_name'] == 'publication' && !(isset($region_info['module_params']['related_mode'])	&& ( ($region_info['module_params']['related_mode'] == "true") || ($region_info['module_params']['related_mode'] == true))));
							if ($valid == true) $valid_page_ids[$pub_id][] = $page_id;
						}
					}
				}
				if (!empty($valid_page_ids[$pub_id]))
				{
					$filtered_publications[$pub_id] = $publication;
					$filtered_page_ids = (count($valid_page_ids[$pub_id]) == 1) ? $valid_page_ids[$pub_id][0] : $valid_page_ids[$pub_id];
					$filtered_publications[$pub_id]->set_value('page_id', $filtered_page_ids);
				}
			}
			return $filtered_publications;
		}
		return $publications;
	}
	
	/**
	 * Build array of entities from an array of unique_names (or a string with one unique name)
	 */
	function build_ids_from_unique_names($unique_names)
	{
		$unique_names = (is_array($unique_names)) ? $unique_names : array($unique_names);	
		foreach($unique_names as $unique_name)
		{
			$id = id_of($unique_name);
			if (!empty($id)) $ids[$id] = $unique_name;
		}
		return (isset($ids)) ? $ids : array();
	}
	
	/**
	 * Crumb for publication should use the release title and not the name of the item
	 * @author Nathan White
	 */
	function add_crumb()
	{
		foreach( $this->items AS $item )
        {
	       	if( $item->id() == $this->request[ $this->query_string_frag.'_id' ] )
           	{
           		$this->_add_crumb( $item->get_value( 'release_title' ) );
          	}
        }
	}

	/**
	 * Modifies the params array to dramatically expand what can be passed in via page type parameters for a publication
	 * @author Nathan White
	 */
	function handle_params( $params )
	{
		// This is a slight hack to get publications to default to *not* limiting to the current site. This should allow publications to be displayed anywhere.
		$this->base_params['limit_to_current_site'] = false;
		
		// all params that could be provided in page_types
		$potential_params = array('use_filters', 'use_pagination', 'num_per_page', 'max_num_items', 'minimum_date_strtotime_format', 'show_login_link', 
		      					  'show_module_title', 'related_mode', 'related_order', 'date_format', 'related_title',
		      					  'limit_by_page_categories', 'related_publication_unique_names', 'related_category_unique_names','css',
		      					  'show_featured_items','jump_to_item_if_only_one_result','authorization','comment_form_file_location','post_form_file_location',);
		$markup_params = 	array('markup_generator_info' => $this->markup_generator_info, 
							      'item_specific_variables_to_pass' => $this->item_specific_variables_to_pass,
							      'variables_to_pass' => $this->variables_to_pass);
		
		$params_to_add = array_diff_assoc_recursive($params, $markup_params + $potential_params);
		if (!empty($params_to_add))
		{
			foreach ($params_to_add as $k=>$v)
			{
				$this->acceptable_params[$k] = $v;
			}
		}
		$this->acceptable_params['module_displays_search_interface'] = true;
		$this->acceptable_params['module_displays_filter_interface'] = true;
		$this->acceptable_params['show_pagination_in_module'] = true;
		$this->acceptable_params['no_publication_warning'] = true;
		parent::handle_params( $params );
	}

	/**
	 * @author Nathan White
	 */
	function set_defaults_from_parameters($param_array, $key = '')
	{
		if (!empty($this->params['related_mode']) && $this->params['related_mode'] == true)
		{
			$this->markup_generator_info = $this->related_markup_generator_info;
		}
		foreach ($param_array as $k=>$v)
		{
			if (isset($this->$k))
			{
				if (is_array($this->$k))
				{
					$this->$k = array_merge_recursive2($this->$k, $v);
				}
				else $this->$k = $v;
			}
		}
	}
	
	//extended generic3 hook
	function pre_es_additional_init_actions() 
	{
		if ($this->related_mode) $this->related_pre_es_additional_init_actions();
		else
		{
			$this->module_title = ($this->show_module_title) ? $this->publication->get_value('name') : '';
			
			// allow parameter override
			if (!empty($this->params['num_per_page']))
			{
				$this->num_per_page = $this->params['num_per_page'];
			}
			elseif($this->publication->get_value('posts_per_page'))
			{
				$this->num_per_page = $this->publication->get_value('posts_per_page');
			}
			
			$date_format = $this->publication->get_value('date_format');
			if(!empty($date_format))
				$this->date_format = $this->publication->get_value('date_format');
					
			$publication_type = $this->publication->get_value('publication_type');
			$publication_descriptor = 'publication';
			$news_item_descriptor = 'news items';
			if($publication_type == 'Blog')
			{
				$publication_descriptor = 'blog';
				$news_item_descriptor = 'posts';
			}
			elseif($publication_type == 'Newsletter')
			{
				$publication_descriptor = 'newsletter';
				$news_item_descriptor = 'articles';
			}
			if($this->publication->get_value('has_issues') == 'yes')
			{
				$this->init_issue();
			}
			if($this->publication->get_value('has_sections') == 'yes')
			{
				$this->init_section();
			}
			if(!empty($this->issue_id)) // means publication is set to use issues and the publication has related issues
			{
				$publication_descriptor = 'issue';
			}
			
			if(!empty($this->request['search']))
			{
				$news_item_descriptor .= ' that match the search phrase "'.reason_htmlspecialchars($this->request['search']).'"';
			}
		
			$this->no_items_text = 'This '.$publication_descriptor.' does not have any '.$news_item_descriptor.'.';
				
			$this->back_link_text = $this->back_link_text.$this->publication->get_value('name');
			
			if($this->make_current_page_link_in_nav_when_on_item 
				&&	(!empty($this->request[$this->query_string_frag.'_id']) || !empty($this->request['section_id']) || !empty($this->request['issue_id']) ) )
			{
				$this->parent->pages->make_current_page_a_link();
			}
			
			$this->_handle_authorization();
		}
	}
	
	function _handle_authorization()
	{
		if(!empty($this->params['authorization']))
		{
			reason_include_once($this->params['authorization']);
			if(empty($GLOBALS[ '_reason_publication_auth_classes' ][$this->params['authorization']]) || !class_exists($GLOBALS[ '_reason_publication_auth_classes' ][$this->params['authorization']]))
			{
				trigger_error($this->params['authorization'].' did not define its class name properly in $GLOBALS[ \'_reason_publication_auth_classes\' ]');
				return; // should it shut everything down or open everything up???
			}
			else
			{
				$netid = $this->get_user_netid();
				$item_id = !empty($this->request[ $this->query_string_frag.'_id' ]) ? $this->request[ $this->query_string_frag.'_id' ] : NULL;
				
				$auth = new $GLOBALS[ '_reason_publication_auth_classes' ][$this->params['authorization']]();
				$auth->set_username($netid);
				$auth->set_item_id($item_id);
				if($this->has_issues())
				{
					$auth->set_issue_id($this->issue_id);
					if($ri = $this->get_most_recent_issue());
						$auth->set_most_recent_issue_id($ri->id());
				}
				if(!$auth->authorized_to_view())
				{
					if(!$netid)
					{
						// header to the login page
						force_login($auth->get_login_message_unique_name());
					}
					else
					{
						// store a not authorized flag for the display phase to pick up
						$this->_ok_to_view = false;
						$this->_unauthorized_message = $auth->get_unauthorized_message();
					}
				}
			}
		}
	}

	/**
	 * Init and add needed head items for social media integration.
	 *
	 * This is triggered in post_es_additional_init_actions.
	 *
	 * @todo add parameters for further integration that this method pays attention to.
	 */
	function _init_social_media_integration()
	{
		// for now, lets just add sensible open graph tags for the item if we have a current item
		if (!empty($this->current_item_id))
		{
			$this->_add_open_graph_tags_for_item();
		}
	}
	
	/**
	 * Add basic metadata using the open graph protocol (http://ogp.me/).
	 *
	 * This should improve how shared items appear on facebook and possibly other social networks.
	 *
	 * @todo add integration with propietary tags for specific social networks.
	 */
	function _add_open_graph_tags_for_item()
	{
		$item = new entity($this->current_item_id);
		if (reason_is_entity($item, 'news'))
		{
			$title = htmlspecialchars(trim(strip_tags($item->get_value('release_title'))),ENT_QUOTES,'UTF-8');
			$description = htmlspecialchars(trim(str_replace('&nbsp;', '', strip_tags($item->get_value('description')))),ENT_QUOTES,'UTF-8');
			if (empty($description)) // lets look to the content field if description is missing.
			{
				$content = htmlspecialchars(trim(str_replace('&nbsp;', '', strip_tags($item->get_value('content')))),ENT_QUOTES,'UTF-8');
				if (!empty($content))
				{
					$words = explode(' ', $content, 31);
					unset($words[count($words)-1]);
					$description = implode(' ', $words).'…';
				}
			}
			$url = carl_construct_link(array(''), array('story_id', 'issue_id', 'section_id'));
			if ($teaser = $this->get_teaser_image($item))
			{
				$teaser = reset($teaser);
				$image_urls[] = reason_get_image_url($teaser);
			}
			elseif ($images = $this->get_item_images($item))
			{
				foreach ($images as $image)
				{
					$image_urls[] = reason_get_image_url($image);
				}
			}
			$site = $this->get_site_entity();
			if ($site) $site_name = htmlspecialchars(trim(strip_tags($site->get_value('name'))),ENT_QUOTES,'UTF-8');
			$head_items =& $this->get_head_items();
			$head_items->add_head_item('meta',array( 'property' => 'og:type', 'content' => 'article'));
			$head_items->add_head_item('meta',array( 'property' => 'og:title', 'content' => $title));
			$head_items->add_head_item('meta',array( 'property' => 'og:url', 'content' => $url));
			if (!empty($description)) $head_items->add_head_item('meta',array( 'property' => 'og:description', 'content' => $description));
			if (!empty($image_urls))
			{
				foreach ($image_urls as $image_url)
				{
					$head_items->add_head_item('meta',array( 'property' => 'og:image', 'content' => 'http://'.$_SERVER['HTTP_HOST'].$image_url));
					if (HTTPS_AVAILABLE) $head_items->add_head_item('meta',array( 'property' => 'og:image:secure_url', 'content' => 'https://'.$_SERVER['HTTP_HOST'].$image_url));
				}	
			}
			if (!empty($site_name)) $head_items->add_head_item('meta',array( 'property' => 'og:site_name', 'content' => $site_name));
		}
	}
	
	protected function _init_markup_generators()
	{
		/* if(!$this->_ok_to_show)
			return; */
		$head_items = $this->get_head_items();
		if(empty($head_items))
			return;
		if(empty($this->current_item_id))
		{
			$persistent_markup_generator = $this->set_up_generator_of_type('persistent');
			$persistent_markup_generator->add_head_items($head_items);
			
			if ($this->issue_list_should_be_displayed())
			{
				$issue_markup_generator = $this->set_up_generator_of_type('issue_list');
				$issue_markup_generator->add_head_items($head_items);
			}
			else
			{
				$list_markup_generator = $this->set_up_generator_of_type('list');
				$list_markup_generator->add_head_items($head_items);
				$featured = $this->get_featured_items();
				if(!empty($featured))
				{
					foreach($featured as $f)
					{
						$featured_list_item_markup_generator = $this->set_up_generator_of_type('featured_item', $f);
						$featured_list_item_markup_generator->add_head_items($head_items);
					}
				}
				if(!empty($this->items))
				{
					foreach($this->items as $item)
					{
						$list_item_markup_generator = $this->set_up_generator_of_type('list_item', $item);
						$list_item_markup_generator->add_head_items($head_items);
					}
				}
			}
		}
		else
		{
			$item = new entity($this->current_item_id);
			
			$persistent_markup_generator = $this->set_up_generator_of_type('persistent', $item);
			$persistent_markup_generator->add_head_items($head_items);
			
			$item_markup_generator = $this->set_up_generator_of_type('item', $item);
			$item_markup_generator->add_head_items($head_items);
		}
	}
	
	/**
	 * Makes sure the section id is okay - intelligently redirects if not.
	 *
	 * Case 1 - we have a section id and item id
	 *
	 * - if the section id is not valid for the item but a single valid section id does exist, redirect to that section
	 * - if the section id is not valid for the item and multiple section ids (or none) exist, redirect without the section
	 *
	 * Case 2 - we have a section id but not item id
	 *
	 * - if the section id is not valid, but a single valid section id is available, redirect to that section
	 * - if the section id is not valid, and multiple section ids (or none) exist, redirect without the section
	 *
	 * @return void
	 */	
	function init_section()
	{
		$requested_section = (!empty($this->request['section_id'])) ? $this->request['section_id'] : false;
		if ($requested_section)
		{
			if ($this->current_item_id)
			{
				// lets make sure the item is in the requested section
				$item = new entity($this->current_item_id);
				$sections_for_item = $this->find_sections_for_this_item($item);
				$sections_for_item_keys = (!empty($sections_for_item)) ? array_keys($sections_for_item) : array();
				$available_sections = $this->get_sections_issue_aware();
				$available_section_keys = (!empty($available_sections)) ? array_keys($available_sections) : array();
				if (!in_array($requested_section, $sections_for_item_keys) || !in_array($requested_section, $available_section_keys))
				{
					// is the union of section_for_item_keys and available_section_keys a single section? If so - redirect to it
					$intersection = array_intersect($sections_for_item_keys, $available_section_keys);
					if (count($intersection) == 1)
					{
						$redirect = carl_make_redirect(array('section_id' => array_shift($intersection)));
						header('Location: '.$redirect);
						exit;
					}
					else
					{
						$redirect = carl_make_redirect(array('section_id' => ''));
						header('Location: '.$redirect);
						exit;
					}
				}
			}
			else
			{
				// we do not have an item but want to still verify that the section is valid - if not, lets redirect
				$available_sections = $this->get_sections_issue_aware();
				$available_section_keys = (!empty($available_sections)) ? array_keys($available_sections) : array();
				if (!in_array($requested_section, $available_section_keys))
				{
					if (count($available_section_keys) == 1)
					{
						$redirect = carl_make_redirect(array('section_id' => array_shift($available_section_keys)));
						header('Location: '.$redirect);
						exit;
					}
					else
					{
						$redirect = carl_make_redirect(array('section_id' => ''));
						header('Location: '.$redirect);
						exit;
					}
				}
			}
		}
	}
	
	/**
	 * init_issue_for_item checks the item and any issue id it was passed - if an issue does not exist or is
	 * invalid, the user is redirected to a url with the most recent valid issue for the item
	 */
	function init_issue()
	{
		$user_issue_keys = $all_issue_keys = array();
		$requested_issue = (!empty($this->request['issue_id'])) ? $this->request['issue_id'] : false;
		$requested_section = (!empty($this->request['section_id'])) ? $this->request['section_id'] : false;
		
		// if we have an item
		if ($this->current_item_id)
		{
			$issues =& $this->get_visible_issues_for_item();
			$user_issue_keys = (!empty($issues)) ? array_keys($issues) : false;
			$all_issues =& $this->get_all_issues();
			$all_issue_keys = array_keys($all_issues);
		}
		else
		{
			if ($requested_issue) 
			{
				$all_issues =& $this->get_all_issues();
				$user_issues =& $this->get_issues();
				$all_issue_keys = array_keys($all_issues);
				$user_issue_keys = array_keys($user_issues);
			}
			elseif ($this->_should_restrict_to_current_issue() ) // if no section requested set an issue_id
			{
				$most_recent_issue = $this->get_most_recent_issue();
				if ($most_recent_issue)
				{
					$this->issue_id = $most_recent_issue->id();
					$this->_add_css_urls_to_head($this->_get_issue_css($this->issue_id));
					return true;
				}
			}
		}	
		if ((!empty($user_issue_keys) || !empty($all_issue_keys))) // item is in an issue
		{
			if (!empty($user_issue_keys) && in_array($requested_issue, $user_issue_keys))
			{
				$this->issue_id = $requested_issue; // requested issue verified
				$this->_add_css_urls_to_head($this->_get_issue_css($this->issue_id));
				$issue_link = $this->get_links_to_issues();
				$issue = new entity($this->issue_id);
				$this->_add_crumb( $issue->get_value( 'name' ), $this->get_link_to_issue($issue) );
				if($requested_section)
				{
					$section = $this->get_current_section();
					if ($section)
					{
						$this->_add_crumb( $section->get_value( 'name' ), $this->get_link_to_section($section) );
					}
				}
				$this->item_specific_variables_to_pass['next_post'] = 'get_next_post';
				$this->item_specific_variables_to_pass['previous_post'] = 'get_previous_post';
				return true;
			}
			elseif (!empty($all_issue_keys) && in_array($requested_issue, $all_issue_keys))
			{
				if (!reason_check_authentication()) // person is not logged in, but could have access to a hidden issue - force login
				{
					reason_require_authentication();
				}
			}
			elseif (!empty($user_issue_keys))
			{
				$redirect = carl_make_redirect(array('issue_id' => array_shift($user_issue_keys)));
				header('Location: '.$redirect);
				exit;
			}
		}
	}
	
	function _should_restrict_to_current_issue()
	{
		if(empty($this->current_item_id) && empty($this->request['section_id'])&& empty($this->request['issue_id']) && empty($this->request['search']) && empty($this->request['filter1']) && empty($this->request['filter2']) && empty($this->request['filter3']))
			return true;
		else
			return false;
	}
	
	function _get_issue_css($issue_id)
	{
		$css = array();
		$r_id = relationship_id_of('issue_to_css_url');
		if($r_id)
		{
			$es = new entity_selector();
			$es->add_type(id_of('external_url'));
			$es->add_right_relationship($issue_id,$r_id);
			$es->set_order('rel_sort_order ASC');
			$css_entities = $es->run_one();
			if(!empty($css_entities))
			{
				foreach($css_entities as $e)
				{
					$css[] = $e->get_value('url');
				}
			}
		}
		else
		{
			trigger_error('Please run the Reason beta 5 to beta 6 publications upgrade script to add the issue_to_css_url relationship');
		}
		return $css;
	}
	
	function _add_css_urls_to_head($css)
	{
		foreach($css as $url)
		{
			$this->parent->add_stylesheet( $url );
		}
	}
	
	/**
	 * pre_es_additional_init_actions when module is in related mode
	 * @author Nathan White
	 */
	function related_pre_es_additional_init_actions()
	{
		if (isset($this->params['related_title']))
		{
			$this->module_title = $this->params['related_title'];
		}
		elseif (!empty($this->related_title))
		{
			$this->module_title = $this->related_title;
		}
		elseif(count($this->related_publications) == 1)
		{
			$pub = current($this->related_publications);
			$this->module_title = $pub->get_value('name');
		}
		else $this->module_title = 'Related posts';
		
		// use date format for publication if there is only one and the date_format was not set by the page type
		
		if (!isset($this->params['date_format']) && (count($this->related_publications) == 1) )
		{
			$pub = current($this->related_publications);
			$date_format = $pub->get_value('date_format');
			if(!empty($date_format)) $this->date_format = $date_format;
		}
	}
	
	/**
	 * Tweaks to do_pagination
	 *
	 * - turn off pagination if we're grouping items by section.
	 * - turn off pagination if max_num_items is set, and is less than num_per_page
	 * - if pagination is on, make a pre_pagination copy of the entity selector for featured item selection.
	 */
	function do_pagination()
	{
		if($this->use_group_by_section_view() || (!empty($this->max_num_items) && ($this->max_num_items) < $this->num_per_page))
		{
			$this->use_pagination = false;
		}
		else
		{
			$this->pre_pagination_es = carl_clone($this->es);
			parent::do_pagination();
		}
	}
	
	/**	
	* Adds any query string fragments from the publication module to the cleanup_rules.
	* @return array $cleanup_rules
	*/
	function get_cleanup_rules()
	{
		$this->cleanup_rules = parent::get_cleanup_rules();
		foreach($this->additional_query_string_frags  as $fragment)
		{
			$this->cleanup_rules[$fragment . '_id'] = array('function' => 'turn_into_int');
		}
		return $this->cleanup_rules;
	}

	//overloaded generic3 function -- sets what entity type "items" is
	function set_type()
	{
		$this->type = id_of('news');
	}
	
	//overloaded generic3 hook ... we've added the news_to_blog relation to the es,  issue_id & section_id relations when appropriate
	function alter_es() // {{{
	{
		if ($this->related_mode) $this->related_alter_es();
		else
		{
			$this->es->set_order( 'dated.datetime DESC' );
			$this->es->add_left_relationship( $this->publication->id(), relationship_id_of('news_to_publication') );
			if($this->publication->get_value('has_issues') == 'yes')
			{
				if($issues = $this->get_issues())
				{
					if(!empty($this->issue_id))
					{
						$this->es->add_left_relationship( $this->issue_id, relationship_id_of('news_to_issue') );
					}
					else
					{
						// limit to shown issues in publication here so that people can't discover unpublished stories through search, etc.
						$this->es->add_left_relationship(array_keys($issues), relationship_id_of('news_to_issue') );
						
					}
				}
				else
				{
					$this->es->add_relation('1 = 2'); // if it is an issued publication without any issues associated, don't show any posts
				}
			}
			if(!empty($this->request['section_id']))
			{
				$this->es->add_left_relationship( $this->request['section_id'], relationship_id_of('news_to_news_section') );
			}
			if (!empty($this->minimum_date))
			{
				$this->es->add_relation('dated.datetime > "' . $this->minimum_date . '"');
			}
		}
		$this->es->add_relation( 'status.status = "published"' );	
		$this->further_alter_es();
		if(!empty($this->max_num_items))
		{
			$this->es->set_num($this->max_num_items);
		}
	} // }}}
	
	function related_alter_es()
	{
		$this->es->set_env('site', $this->site_id);
		$this->es->optimize('distinct');
		$this->es->add_left_relationship( array_keys($this->related_publications), relationship_id_of('news_to_publication') );
		// add category limitations
		if (!empty($this->related_categories)) // if no categories do not limit;
		{
			$this->es->add_left_relationship( array_keys($this->related_categories), relationship_id_of('news_to_category'));
		}
		if (!empty($this->minimum_date))
		{
			$this->es->add_relation('dated.datetime > "' . $this->minimum_date . '"');
		}
		$this->related_issue_limit($this->es);
		$table_limit_array = (!empty($this->minimum_date)) ? array('status', 'dated') : array('status');
		$this->related_order_and_limit($this->es, $table_limit_array);
	}
	
	/**
	 * If we are dealing with publications that have issues, make sure that the items listed are part of a visible issue
	 */
	function related_issue_limit(&$es)
	{
		if (!empty($this->related_publications))
		{
			$issued_pubs = array();
			$nonissued_pubs = array();
			foreach($this->related_publications as $pub)
			{
				if($pub->get_value('has_issues') == 'yes')
					$issued_pubs[$pub->id()] = $pub;
				else
					$nonissued_pubs[$pub->id()] = $pub;
			}
			
			if(empty($issued_pubs))
				return;
			
			$issued_posts = array();
			$nonissued_posts = array();
			$table_limit_array = (!empty($this->minimum_date)) ? array('status', 'dated') : array('status');
			if(!empty($issued_pubs))
			{
				$es2 = new entity_selector();
				$es2->limit_tables('show_hide');
				$es2->limit_fields('show_hide');
				$es2->add_type( id_of('issue_type') );
				$es2->add_left_relationship( array_keys($issued_pubs), relationship_id_of('issue_to_publication') );
				$es2->add_relation("show_hide.show_hide = 'show'");
				$issues = $es2->run_one();
				if(!empty($issues))
				{
					$issued_posts_es = carl_clone($es);
					$issued_posts_es->add_left_relationship(array_keys($issues), relationship_id_of('news_to_issue'));
					$this->related_order_and_limit($issued_posts_es, $table_limit_array);
					$issued_posts = $issued_posts_es->run_one();
				}
			}
			if(!empty($nonissued_pubs))
			{
				$nonissued_posts_es = carl_clone($es);
				$nonissued_posts_es->add_left_relationship(array_keys($nonissued_pubs), relationship_id_of('news_to_publication'));
				$this->related_order_and_limit($nonissued_posts_es, $table_limit_array);
				$nonissued_posts = $nonissued_posts_es->run_one();
			}
			
			$post_ids = array_unique(array_merge(array_keys($issued_posts),array_keys($nonissued_posts)));
			$es->add_relation('entity.id IN ("'.implode('","',$post_ids).'")');
		}
	}

	/**
	 * applies the ordering scheme specified in the related_order keyword - currently only random and the default dated.datetime DESC
	 * ordering are supported.
	 * @param object $es by reference, the entity selector for which we will specify order and limits
	 * @param array $table_limit_array optional array of tables which should be included in the limit_tables array
	 * @param array $field_limit_array optional array of fields which should be included in the limit_fields array
	 * @return void
	 * @author Nathan White
	 */
	function related_order_and_limit(&$es, $table_limit_array = '', $field_limit_array = '')
	{
		if ($this->related_order == 'random')
		{
			$order_string = 'rand()';
		}
		else
		{
			if (!is_array($table_limit_array) || !in_array('dated', $table_limit_array)) $table_limit_array[] = 'dated';
			$order_string = 'dated.datetime DESC';
		}
		$es->limit_tables($table_limit_array);
		$es->limit_fields($field_limit_array);
		$es->set_order($order_string);
	}
	
	function further_alter_es()
	{
	}
	
	function post_es_additional_init_actions()
	{
		if ($this->related_mode) $this->related_post_es_additional_init_actions();
		$this->_init_social_media_integration();
		$this->_init_markup_generators();
	}
	
	/**
	 * take the set of items selected, and replaces it with a set that includes the multivalue publication and category ids that
	 * match the limitations of the page
	 * @author Nathan White
	 */
	function related_post_es_additional_init_actions()
	{
		if ($this->items)
		{
			$es = new entity_selector();
			$es->add_type($this->type);
			$es->enable_multivalue_results();
			$es->add_relation('entity.id IN ('.implode(",", array_keys($this->items)).')');
			$es->add_left_relationship_field('news_to_publication', 'entity', 'id', 'publication_id', array_keys($this->related_publications));
			if ($this->related_categories)
			{
				$es->add_left_relationship_field('news_to_category', 'entity', 'id', 'cat_id', array_keys($this->related_categories));
			}
			$this->related_order_and_limit($es);
			$this->items = $es->run_one();
		}
	}

	//overloaded generic3 function	
	function has_content() // {{{
	{
		if ($this->related_mode) return $this->has_content_related();
		elseif(empty($this->publication))
			return false;
		else
			return true;
	} // }}}
	
	/**
	 * has content function for module when running in related mode
	 * @author Nathan White
	 */
	function has_content_related()
	{
		if (empty($this->items)) return false;
		else 
		{
			return true;
		}
	}
	

////////
// DISPLAY INDIVIDUAL ITEM METHODS
////////	

	//overloaded generic3 function
	/**
	*	Displays the full view of a news item.
	*   @param $item the news item entity
	*/
	function show_item_content( $item )
	{	
		if( !$this->_ok_to_view )
		{
			echo $this->_unauthorized_message;
			return;
		}
		
		//if this is an issued publication, we want to say what issue we're viewing
		$current_issue = $this->get_current_issue();
		if(!empty($current_issue) )
		{
			$list_markup_generator = $this->set_up_generator_of_type('list');
			echo $list_markup_generator->get_current_issue_markup($current_issue);
		}
			
		// Show a disco inline editing form if it is available and active, otherwise show the page normally
		$inline_edit =& get_reason_inline_editing($this->page_id);
		if ($inline_edit->available_for_module($this) && $inline_edit->active_for_module($this))
		{
			$this->show_inline_editing_form();
		}
		else
		{
			$item_markup_generator = $this->set_up_generator_of_type('item', $item);
			echo $item_markup_generator->get_markup();
		}
	}
	
	//this is the function used to generate the variable needed by the list_markup_generator
	function get_site_entity()
	{
		return new entity($this->site_id);
	}
	function get_publication_entity()
	{
		return $this->publication;
	}
	
////////
// DISPLAY LIST METHODS
////////

	
		
	function show_persistent()
	{
		if(!empty($this->current_item_id) && $this->request['story_id'] == $this->current_item_id)
		{
			$item = new entity($this->current_item_id);
			$item->get_values();
			$persistent_markup_generator = $this->set_up_generator_of_type('persistent', $item);
		}
		else
		{
			$persistent_markup_generator = $this->set_up_generator_of_type('persistent');
		}
		echo $persistent_markup_generator->get_markup();
	}
	
	function get_use_filters_value()
	{
		return $this->use_filters;
	}

	//overloaded from generic3 so that the links to other issues will still appear even when there are no items for that issue.
	function list_should_be_displayed()
	{
		if(!empty($this->items) || $this->has_issues() )
			return true;
		else
			return false;
	}
	
	function issue_list_should_be_displayed()
	{
		return (!$this->related_mode && $this->has_issues() && isset($this->request['issue_id']) && ($this->request['issue_id'] === 0));
	}
	
	// overloaded generic3 function
	/** 
	* Gets the markup for the list from the list markup generator.
	* If there are no items in the list, displays links to other issues if appropriate
	*/ 
	function do_list()
	{	
		if( !$this->_ok_to_view )
		{
			echo $this->_unauthorized_message;
			return;
		}	
		if ($this->issue_list_should_be_displayed())
		{
			$issue_markup_generator = $this->set_up_generator_of_type('issue_list');
			echo $issue_markup_generator->get_markup();
		}
		else
		{
			$list_markup_generator = $this->set_up_generator_of_type('list');
			echo $list_markup_generator->get_markup();
			if(empty($this->items))	//this should only appear if we have issues ... otherwise would be echoed list_items()
				echo '<div class="noItemsText">'.$this->no_items_text.'</div>'."\n";
		}
	}

	/**
	*  Instantiates a new markup generator and passes it the correct variables.
	*  @param string $type Type of markup generator to instantiate (this needs to be a key in {@link markup_generator_info})
	*  @param object $item New item entity (optional - pass if this is a markup generator to display an individual item).
	*  @return the new markup generator
	*/
	function set_up_generator_of_type($type, $item = false)
	{
		if(!isset($this->markup_generators[$type]))
			$this->markup_generators[$type] = array();
		$item_id = !empty($item) ? $item->id() : 0;
		if(!isset($this->markup_generators[$type][$item_id]))
		{
			if(isset($this->markup_generator_info[$type]['filename']))
			{
				if(!reason_include_once( $this->markup_generator_info[$type]['filename'] ))
				{
					trigger_error('Markup generator file not found at '.$this->markup_generator_info[$type]['filename'].'. Empty markup generator substituted.');
					reason_include_once('minisite_templates/modules/publication/empty_markup_generator.php');
				}
			}
			else
			{
				trigger_error('No markup generator filename found for "'.$type.'". Empty markup generator substituted.');
				reason_include_once('minisite_templates/modules/publication/empty_markup_generator.php');
			}
			if(isset($this->markup_generator_info[$type]['classname']))
			{
				if(class_exists($this->markup_generator_info[$type]['classname']))
					$markup_generator = new $this->markup_generator_info[$type]['classname']();
				else
				{
					trigger_error('Class '.$this->markup_generator_info[$type]['classname'].' not found. Empty markup generator substituted.');
					$markup_generator = new EmptyMarkupGenerator();
				}
			}
			else
			{
				trigger_error('No markup generator classname found for "'.$type.'". Empty markup generator substituted.');
				$markup_generator = new EmptyMarkupGenerator();
			}
			$markup_generator_settings = (!empty($this->markup_generator_info[$type]['settings'])) 
								     ? $this->markup_generator_info[$type]['settings'] 
								     : '';
			if (!empty($markup_generator_settings)) $markup_generator->set_passed_variables($markup_generator_settings);
			$markup_generator->set_passed_variables($this->get_values_to_pass($markup_generator, $item));
			//pray($this->get_values_to_pass($markup_generator, $item));
			$this->markup_generators[$type][$item_id] = $markup_generator;
		}
		return $this->markup_generators[$type][$item_id];
	}
	
	/**
	*  Helper function to set_up_generator_of_type; passes appropriate variables from the module to the 
	*  markup generator
	*  @param object $markup_generator The markup generator object
	*  @return An array of values to pass, formatted $variable_name => value
	*/
	function get_values_to_pass($markup_generator, $item)
	{
		$values_to_pass = array();
		foreach($markup_generator->get_variables_needed() as $var_name)
		{
			if(isset($this->variables_to_pass[$var_name]) && !empty($this->variables_to_pass[$var_name]) )
			{
				$method = $this->variables_to_pass[$var_name];
				if(method_exists($this, $method))
					$values_to_pass[$var_name] = $this->$method();
				else
					trigger_error('Method "'.$method.'" is not defined', WARNING);
			}
			elseif(isset($this->item_specific_variables_to_pass[$var_name])&& !empty($this->item_specific_variables_to_pass[$var_name]) )
			{
				$method = $this->item_specific_variables_to_pass[$var_name];
				if(method_exists($this, $method))
				{
					$values_to_pass[$var_name] = $this->$method($item);
				}
				else
					trigger_error('Method "'.$method.'" is not defined', WARNING);
			}
			elseif($var_name == 'item' && !empty($item))
			{
				$values_to_pass[$var_name] = $item;
			}
			elseif($var_name == 'item')
			{
				$values_to_pass[$var_name] = false;
			}
			//elseif( isset($this->markup_generator_info
			elseif( isset($this->$var_name))
			{
				$values_to_pass[$var_name] = $this->$var_name;
			}
		}
		return $values_to_pass;
	}
	
//////////
///  METHODS TO ADD NEW ITEMS
//////////

		/**	
		* Returns the text for the "add post" link.
		* Overloads the Generic3 hook.
		* @return string text for the "add post" link.
		*/	
		function get_add_item_link()
		{
			if ($this->related_mode) return false;
			$netid = $this->get_user_netid();
			
			$ph = $this->get_post_group_helper();
			if($ph->group_has_members())
			{
				if($ph->requires_login()) // login required to post
				{
					if(empty($this->user_netID)) // not logged in
					{
						return '';
					}
					else // logged in
					{
						if($ph->has_authorization($netid)) // has authorization to post
						{
							return $this->make_add_item_link();
						}
						else // does not have authorization to post
						{
							return '';
						}
					}
				}
				else // No login required to post
				{ 
					return $this->make_add_item_link();
				}
			}
			else 
				return '';
		}
		
		/**
		*  Helper function to get_add_item_link() - returns the markup for the add item link.
		*  @return string the add item link
		*/
		function make_add_item_link()
		{
			if ($this->related_mode) return false;
			$link = array('add_item=true');
			if(!empty($this->textonly))
			{
				$link[] = 'textonly=1';
			}
			//if we've been looking at a particular issue, we want to be able to automatically set the issue value in the form
			if(!empty($this->issue_id))
			{
				$link[] = 'issue_id='.$this->issue_id;
			}
			//ditto if we've been looking at a particular section
			if(!empty($this->request['section_id']))
			{
				$link[] = 'section_id='.$this->request['section_id'];
			}

			//not using construct_link because we don't want to include a page value
			return '<div class="addItemLink"><a href ="?'.implode('&amp;',$link).'">Post to '.$this->publication->get_value('name').'</a></div>'."\n";
		}
		
		/**
		 * Checks to make sure that the given news item is OK to display.
		 * 
		 * This should return true if the entity looks OK to be shown and false if it does not.
		 *
		 * It also does some checks and may redirect to make URLs sane (IE link given with wrong section).
		 *
		 * @param entity $entity news_item_entity
		 * @return boolean True if OK
		 */
		function further_checks_on_entity( $entity )
		{
			if(empty($this->items[$entity->id()]))
			{
				if($entity->get_value('status') == 'pending' && !user_has_access_to_site($this->site_id)) return false;
				$publication_check = ($entity->has_left_relation_with_entity($this->publication, 'news_to_publication'));
				// check that issue id is present and validated if the publication has issue
				if ($this->publication->get_value('has_issues') == 'yes')
				{
					$issue_check = (!empty($this->request['issue_id']) && ($this->request['issue_id'] == $this->issue_id));
				}
				else $issue_check = true;
				if ($publication_check && $issue_check) return true;
				else return false;
			}
			else
			{
				return true;
			}
		}
		
		/**	
		* Displays the Blog Post Submission Disco form if a user is authorized to post to the blog. 
		* Overloads the Generic3 hook.
		*/	
		function add_item()
		{	
			$posting_group_helper = $this->get_post_group_helper();
						
			if($posting_group_helper->group_has_members())
			{
				if($posting_group_helper->requires_login())
				{
					$netid = $this->get_user_netid();
					
					if(!empty($netid))
					{
						if($posting_group_helper->has_authorization($netid))
						{
							$this->build_post_form($netid);
						}
						else
						{
							echo 'You are not authorized to post on this publication.'."\n";
						}
					}
					else
						echo 'Please <a href="'.REASON_LOGIN_URL.'"> login </a> to post.'."\n";
				}
				else
				{
					$this->build_post_form('');
				}
			}
		}

		/**	
		* Helper function to add_item() - initializes & runs a BlogPostSubmissionForm object 
		* @param string user's netID
		*/	
		function build_post_form($net_id)
		{
			reason_include_once($this->post_form_file_location);
			
			$identifier = basename( $this->post_form_file_location, '.php');
			if(empty($GLOBALS[ '_publication_post_forms' ][ $identifier ]))
			{
				trigger_error('Post forms must identify their class name in the $GLOBALS array; the form located at '.$this->post_form_file_location.' does not and therefore cannot be run.');
				return '';
			}
			
			$form_class = $GLOBALS[ '_publication_post_forms' ][ $identifier ];

			$hold_posts_for_review = ($this->publication->get_value('hold_posts_for_review') == 'yes') ? true : false;

			$form = new $form_class($this->site_id, $this->publication, $net_id, $hold_posts_for_review);
			if(!empty($this->issue_id))
				$form->set_issue_id($this->issue_id);
			if(!empty($this->request['section_id']))
				$form->set_section_id($this->request['section_id']);
			$form->run();
		}

///////////////
//  ISSUE FUNCTIONS
///////////////	
		/**
		* Returns true if the publication can have issues related to it and has issues related to it.
		* This function only checks to see if a publication has issues if it SHOULD be able to have issues.
		* If an publication shouldn't have issues -- for example, a blog -- but has issues related to it anyway, this
		* function will return false.
		* @return boolean true if the publication has issues.
		*/
		function has_issues()
		{
			if($this->publication && $this->publication->get_value('has_issues') == "yes")
			{
				$issues =& $this->get_issues();
				if(!empty($issues)) return true;
			}
			return false;
		}
		
		/**
		* Returns an array of the issues associated with this publication.
		* Format: $issue_id => $issue_entity
		* @return array array of the issues for this publication
		*/
		function &get_issues()
		{
			if(empty($this->issues))
			{
				if($this->publication->get_value('has_issues') == "yes")
				{
					$issues =& $this->get_all_issues();
					if (!empty($issues))
					{
						$this->issues =& $this->filter_hidden_issues($issues);
					}
					else $this->issues = false;
				}
			}
			return $this->issues;
		}
		
		function &get_all_issues()
		{
			if (empty($this->all_issues))
			{
				if($this->publication->get_value('has_issues') == "yes")
				{
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting issues for this publication';
					$es->add_type( id_of('issue_type') );
					$es->limit_tables(array('dated','show_hide'));
					$es->limit_fields(array('dated.datetime', 'show_hide.show_hide'));
					$es->set_order('dated.datetime DESC');
					$es->add_left_relationship( $this->publication->id(), relationship_id_of('issue_to_publication') );
					$issues = $es->run_one();
					if (!empty($issues))
					{
						$this->all_issues = $issues;
					}
					else $this->all_issues = false;
				}
			}
			return $this->all_issues;
		}
		
		function &get_visible_issues()
		{
			$issues =& $this->get_issues();
			$visible_issues = ($issues) ? $this->filter_hidden_issues($issues, false) : false;
			return $visible_issues;
		}
		
		function &filter_hidden_issues($issues, $site_users_have_access = true)
		{
			if ($site_users_have_access && user_has_access_to_site($this->site_id)) return $issues;
			else
			{
				foreach ($issues as $k=>$v)
				{
					if ($v->get_value('show_hide') == 'show') $visible_issues[$k] = $v;
				}
				return $visible_issues;	
			}
		}
			
		/**
		* Returns an array of the issues associated with the current item id.
		* Format: $issue_id => $issue_entity
		* @return array array of the issues for this publication
		*/
		function &get_issues_for_item()
		{
			static $issues;
			if (!isset($issues[$this->current_item_id]))
			{
				if ($all_issues = $this->get_all_issues())
				{
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting issues for this news item';
					$es->limit_tables('dated');
					$es->limit_fields('dated.datetime');
					$es->add_type( id_of('issue_type') );
					$es->add_right_relationship( $this->current_item_id, relationship_id_of('news_to_issue') );
					$es->add_relation('entity.id IN ('.implode(", ", array_keys($all_issues)).')');
					$es->set_order('dated.datetime DESC');
					$issue_set = $es->run_one();
					$issues[$this->current_item_id] = $issue_set;
				}
				else $issues[$this->current_item_id] = FALSE;
			}
			return $issues[$this->current_item_id];
		}
		
		/**
		* Returns an array of the visible issues associated with the current item id.
		* Format: $issue_id => $issue_entity
		* @return array array of the issues for this publication
		*/
		function &get_visible_issues_for_item()
		{
			static $visible_issues;
			if (!isset($visible_issues[$this->current_item_id]))
			{
				if ($issues_for_item = $this->get_issues_for_item())
				{
					$visible_issues[$this->current_item_id] = $this->filter_hidden_issues($issues_for_item);
				}
				else $visible_issues[$this->current_item_id] = FALSE;
			}
			return $visible_issues[$this->current_item_id];
		}
		
//		/**
//		*  Creates an issues array keyed by date instead of by id
//		*  @return array array of issues, formatted $datetime => $issue_entity
//		*/
//		function get_issues_by_date()
//		{
//			$issues_by_date = array();
//			if($this->has_issues())
//			{
//				return $this->issues;
//			}
//			return array();
//		}
		
		/**
		* Returns an array of links to each issue for this publication.
		* Format $issue_id => $issue_entity
		* @return array Array of links to each issue of this publication.
		*/
		function get_links_to_issues()
		{
			$links = array();
			if($this->has_issues())
			{
				$issues =& $this->get_issues();
				foreach($issues as $issue_id => $issue_entity)
				{
					$links[$issue_entity->id()] = $this->get_link_to_issue($issue_id);
				}
			}
			return $links;
		}
		
		function get_link_to_issue($issue)
		{
			if(is_object($issue))
			{
				return $this->construct_link(NULL, array( 'issue_id'=>$issue->id(), 'page'=> '1'  ) );
			}
			else
			{
				return $this->construct_link(NULL, array( 'issue_id'=>$issue, 'page'=> '1'  ) );
			}
		}
		
		/**
		* Returns a copy of the issue entity that's currently being viewed.
		* If you want the issue with the most recent datetime, use {@link get_most_recent_issue()}.
		* @return object the issue entity that's currently being viewed.
		*/
		function get_current_issue()
		{
			if($this->has_issues() && !empty($this->issue_id))
			{
				$issues =& $this->get_issues();
				return $issues[$this->issue_id];
			}
			else
				return false;
		}
	
		/**
		* Returns a copy of the issue entity with the most recent datetime.
		* @return object the most recent issue entity
		*/
		function get_most_recent_issue()
		{
			$issues =& $this->get_visible_issues();
			if ($issues)
			{
				reset($issues); // make sure pointer is at first element		
				return current($issues);
			}
			else return false;
		}
		
		/**
		* Returns the blurbs attached to the current issue
		* @todo make the issue_to_text_blurb relationship sortable
		* @return object the most recent issue entity
		*/
		function get_current_issue_blurbs()
		{
			$issue = $this->get_current_issue();
			if( is_object($issue) )
			{
				if( !isset( $this->_blurbs_by_issue[ $issue->id() ] ) )
				{
					$es = new entity_selector();
					$es->add_type(id_of('text_blurb'));
					$es->set_env('site', $this->site_id);
					$es->add_right_relationship($issue->id(), relationship_id_of('issue_to_text_blurb'));
					$this->_blurbs_by_issue[ $issue->id() ] = $es->run_one();
				}
				return $this->_blurbs_by_issue[ $issue->id() ];
			}
			return array();
		}

///////
///  NEWS SECTION FUNCTIONS
///////
		/**
		* Returns true if the publication has news sections related to it.
		* @return boolean true if the publication has sections.
		*/
		function has_sections()
		{
			if(!$this->related_mode && $this->publication->get_value('has_sections') == "yes")
			{
				$sections = $this->get_sections();
				if(!empty($sections))
				{
					return true;
				}
			}
			return false;
		}
		
		/**
		*  Returns an array of the news sections associated with this publication. If there is a current issue selected, only return those sections that have posts in the current issue.
		*  Format: $section_id => $section_entity
		*  @return array Array of the news sections for the publication
		*/
		function get_sections_issue_aware()
		{
			if($this->publication->get_value('has_issues') == 'yes' && $issue = $this->get_current_issue())
			{
				if(!isset($this->sections_by_issue[$issue->id()]))
				{
					$this->sections_by_issue[$issue->id()] = array();
					$es = new entity_selector();
					$es->add_type( id_of('news'));
					$es->add_left_relationship( $issue->id(), relationship_id_of('news_to_issue') );
					$es->add_left_relationship_field('news_to_news_section','entity','id','section_id');
					$es->add_left_relationship_field('news_to_news_section','sortable','sort_order','section_order');
					$es->set_order('section_order ASC');
					$posts = $es->run_one();
					foreach($posts as $post)
					{
						if(!isset($this->sections_by_issue[$issue->id()][$post->get_value('section_id')]))
							$this->sections_by_issue[$issue->id()][$post->get_value('section_id')] = new entity($post->get_value('section_id'));
					}
				}
				return $this->sections_by_issue[$issue->id()];
			}
			else
			{
				return $this->get_sections();
			}
		}
		/**
		*  Returns an array of the news sections associated with this publication.
		*  Format: $section_id => $section_entity
		*  @return array Array of the news sections for the publication
		*/
		function get_sections()
		{
			if(empty($this->sections))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Selecting news sections for this publication';
				$es->add_type( id_of('news_section_type'));
				$es->add_left_relationship( $this->publication->id(), relationship_id_of('news_section_to_publication') );
				$es->set_order('sortable.sort_order ASC');
				$this->sections=$es->run_one();
			}
			return $this->sections;
		}
		
		/**
		*  Returns an array of all the items in the publication organized by section id.
		*  Format: [$section_id][$item_id] = $item_entity
		*  @return array All items of the publication organized by section id
		*/
		function get_items_by_section()
		{
			if(!empty($this->_items_by_section))
			{
				return $this->_items_by_section;
			}
			foreach($this->items as $item)
			{
				if($this->has_sections() && $this->use_group_by_section_view())
				{
					$related_sections = $this->find_sections_for_this_item($item);
					$current_section = $this->get_current_section();
					if(!empty($related_sections))
					{
						foreach($related_sections as $section)
						{
							if(empty($current_section) || $section->id() == $current_section->id())
							{
								$this->_items_by_section[$section->id()][$item->id()] = $item;
							}
						}
					}	
				}
				else
					$this->_items_by_section[$this->no_section_key][$item->id()] = $item;
			}
			
			if (!empty($this->_items_by_section) && $this->has_sections() && $this->use_group_by_section_view())
			{
				$section_order = array_keys($this->get_sections());
				foreach ($section_order as $section_id)
				{
					if (array_key_exists($section_id, $this->_items_by_section))
					{
						$items_by_section_ordered[$section_id] = $this->_items_by_section[$section_id];
					}
				}
				$this->_items_by_section = $items_by_section_ordered;
			}
			return $this->_items_by_section;
		}
		
		
		/**
		*  Returns an array of the links to every section of this publication.
		*  Format: $section_id => $link_to_section
		*  Links in this case refers just to the url; does not include the <a> tag or the name of the link.
		*  @return array Links to every section of this publication.
		*/
		function get_links_to_sections()
		{
			$sections = $this->get_sections();
			$links = array();
			if(!empty($sections))
			{
				foreach($sections as $section_id => $section)
				{
					$links[$section->id()] = $this->get_link_to_section($section);
				}
			}
			return $links;
		}
		function get_link_to_section($section)
		{
			if(is_object($section))
				$section_id = $section->id();
			else
				$section_id = $section;
			$link_args = $this->get_query_string_values(array('issue_id', 'page'=> 1));
			$link_args['section_id'] = $section_id;
			return $this->construct_link(NULL, $link_args );
		}
		
		/**
		*  Return an array of the links to the sections that this news item is associated with.
		*  Format $section_id => ($section_name, $url)
		*  @param object $item The item entity.
		*  @return array Links to the sections that this item is related to.
		*/
		function get_links_to_sections_for_this_item($item)
		{
			$section_links = array();
			$related_sections = $this->find_sections_for_this_item($item);
			foreach($related_sections as $section)
			{
				$link_args = $this->get_query_string_values(array('issue_id', 'page'=> 1));
				$link_args['section_id'] = $section->id();
				$section_links[$section->id()]['url'] = $this->construct_link(NULL, $link_args );
				$section_links[$section->id()]['section_name'] = $section->get_value('name');
			}
			return $section_links;
		}
		
		/**
		* Returns an array of the sections of this publication that this news item is associated with.
		* Format: $section_id => $section_entity
		* @param object $item A news item entity
		* @return array The news sections of this publication that $item is associated with.
		*/
		function find_sections_for_this_item($item)
		{
			$related_sections_for_this_pub = array();
			
			$all_related_sections = $item->get_left_relationship( 'news_to_news_section' );
			if(!empty($all_related_sections))
			{
				foreach($all_related_sections as $section)
				{
					$sections = $this->get_sections();
					
					//check to make sure that this section is associated with this publication
					if(array_key_exists($section->id(), $sections))	
					{
						$related_sections_for_this_pub[$section->id()] = $section;
					}
				}
			}
			return $related_sections_for_this_pub;
		}
		
		/**
		* Returns a copy of the news section entity that's currently being viewed.
		* @return object the news section entity that's currently being viewed.
		*/
		function get_current_section()
		{	
			$sections = $this->get_sections();
			if(!empty($sections) && !empty($this->request['section_id']))
			{
				return (isset($sections[$this->request['section_id']])) ? $sections[$this->request['section_id']] : false;
			}
			else
				return false;
		}

		/**
		* If a section is specified in the request {@link request}, this constructs a link to view all all of the news 
		* items in this publication that are in the specified section regardless of what issue they're in.
		* "Link" in this case just means the url; no <a> tag, no name.
		* @todo Change this so that it has a section id as a parameter and looks for that rather than checking for a 
		*        section id in the {@link request}.
		* @return string Link to all news items in this section, regardless of issue.
		*/
		function get_all_items_in_section_link()
		{
			if(!empty($this->request['section_id']))
			{
				return $this->construct_link(NULL, array('section_id' => $this->request['section_id'], 'page'=> 1) );
			}
			else
				return false;
		}
		
		/**
		*  Determines whether or not news items should be grouped by section on the main list.  
		*  @return boolean True if news items should be grouped by section.
		*/
		function use_group_by_section_view()
		{
			if($this->group_by_section && $this->has_sections() && !$this->get_current_section())
				return true;
			else
				return false;
		}

///////////
// PERMISSION-RELATED FUNCTIONS
//////////

		/**
		*  Returns the group helper object for the group of users who can comment in this publication.
		*  @return object The group helper for users who can comment in this publication.
		*/
		function &get_comment_group_helper()
		{
			if(!isset($this->_comment_group_helper))
			{
				$group = $this->get_comment_group();
				if (!empty($group))
				{
					$this->_comment_group_helper = new group_helper();
					$this->_comment_group_helper->set_group_by_entity($group);
				}
				else
				{
					$this->_comment_group_helper = false;
				}
			}
			return $this->_comment_group_helper;
		}
		
		/**
		*  Returns the group entity that represents the users authorized to comment in this publication.
		*  Helper function to {@link get_comment_group_helper()}.
		*  @return object The group of users who can comment.
		*/
		function get_comment_group()
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Getting groups for this publication';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_authorized_commenting_group') );
			$es->set_num(1);
			$groups = $es->run_one();	
			if(!empty($groups))
			{
				$comment_group = current($groups);
				return $comment_group;
			}
			else
			{
				return new entity(id_of('nobody_group'));
			}
		}
		
		function get_comment_moderation_state()
		{
			if($this->publication->get_value('hold_comments_for_review') == 'yes')
			{
				return true;
			}
			else
				return false;
		}
		
		/**
		*  Finds the group that represents users who can post news items to this publication.
		*  @return entity the group that represents users who can post news items to this publication.
		*/
		function get_post_group()
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Getting groups for this publication';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_authorized_posting_group') );
			$groups = $es->run_one();
			if(!empty($groups))
			{
				$post_group = current($groups);
				return $post_group;
			}
			else
			{
				return new entity(id_of('nobody_group'));
			}
		}
		
		/**
		*  Instantiates a group helper for the group that represents users who can post news items to this publication.
		*  @return entity the group helper for users who can post to this publication
		*/
		function get_post_group_helper()
		{
			reason_include_once( 'classes/group_helper.php' );
			
			$group = $this->get_post_group();
			$post_group_helper = new group_helper();
			$post_group_helper->set_group_by_entity($group);
			return $post_group_helper;
		}

////////////
/// MISC.
////////////
		function get_feed_url()
		{
			if ($this->related_mode) return false; // hmmm not sure what to do when publication is in related mode for feed_url
			if(empty($this->feed_url))
			{
				$blog_type = new entity(id_of('publication_type'));
				if($blog_type->get_value('feed_url_string'))
				{
					$site = new entity($this->site_id);
					$base_url = $site->get_value('base_url');
					$this->feed_url = $base_url.MINISITE_FEED_DIRECTORY_NAME.'/'.$blog_type->get_value('feed_url_string').'/'.$this->publication->get_value('blog_feed_string');
				}
			}
			if(!empty($this->feed_url))
			{
				return $this->feed_url;
			}
			else
			{
				return false;
			}
		}
		
		function get_login_logout_link()
		{
			if ($this->show_login_link)
			{
				$sess_auth = reason_check_authentication('session');
				$auth = reason_check_authentication('server');
				if(!empty($sess_auth) || !empty($auth)) $class = 'login';
				else $class = 'logout';
				$ret = '<div class="loginlogout '.$class.'">';
				if(!empty($sess_auth))
				{
					$ret .= 'Logged in: '.$sess_auth.' <a href="'.REASON_LOGIN_URL.'?logout=true"><span class="action">Log Out</span></a>';
				}
				elseif(!empty($auth))
				{
					$ret .= 'Logged in as '.$auth;
				}
				else
				{
					$ret .= '<a href="'.REASON_LOGIN_URL.'"><span class="action">Log In</span></a>';
				}
				$ret .= '</div>';
				return $ret;
			}
			else parent::get_login_logout_link();
		}
		
		function show_filtering()
		{
			if($this->params['module_displays_search_interface'] || $this->params['module_displays_filter_interface'])
			{
				$markup = $this->get_filter_markup();
				if($this->params['module_displays_search_interface'] && !empty($markup['search']))
				{
					echo $markup['search'];
				}
				if($this->params['module_displays_filter_interface'] && !empty($markup['filter']))
				{
					echo $markup['filter'];
				}
			}
		}
		
		function get_filtering_markup()
		{
			ob_start();
			$this->show_filtering();
			return ob_get_clean();
		}
		
		function get_pagination_markup($class = '')
		{
			if($this->use_pagination && ( $this->show_list_with_details || empty( $this->current_item_id ) ) )
			{
				if(empty($this->total_pages))
					$this->total_pages = ceil( $this->total_count / $this->num_per_page );
				if($this->total_pages > 1)
				{
					if(empty($this->pagination_output_string))
					{
						$this->pagination_output_string = $this->_get_pagination_markup();
					}
					if(!empty($class))
					{
						$class = ' '.$class;
					}
					$class .= ' page'.htmlspecialchars($this->request['page']);
					return '<div class="pagination'.$class.'">'.$this->pagination_output_string.'</div>'."\n";
				}
			}
			return '';
		}
		function show_pagination($class = '')
		{
			if($this->params['show_pagination_in_module'])
				echo $this->get_pagination_markup($class);
		}

		/**
		*  Uses a list item markup generator to get the markup for each item of the list.
		*  @return array Array of markup strings, formatted $item_id => markup string of that item 
		*/
		function get_list_item_markup_strings()
		{
			$list_item_markup_strings = array();
			if($this->use_group_by_section_view())
			{
				//if we're grouping by section, we need to make sure that we limit the number of items per section
				$items_by_section = $this->get_items_by_section();
				foreach($items_by_section as $section_id => $items)
				{
					$num_per_section = $this->sections[$section_id]->get_value('posts_per_section_on_front_page');
					for($i=0; $i < $num_per_section; $i++)
					{
						$item = current($items);
						if(empty($item))
							break;
						next($items);
						$list_item_markup_generator = $this->set_up_generator_of_type('list_item', $item);
						$list_item_markup_strings[$item->id()] = $list_item_markup_generator->get_markup();
					}
				}
			}
			else
			{
				foreach($this->items as $item)
				{
					$list_item_markup_generator = $this->set_up_generator_of_type('list_item', $item);
					$list_item_markup_strings[$item->id()] = $list_item_markup_generator->get_markup();
				}
			}
			return $list_item_markup_strings;
		}
		
		/**
		*  Uses a featured item markup generator to get the markup for each featured item of the publication.
		*  @return array Array of markup strings, formatted $item_id => markup string of that item 
		*/		
		function get_featured_item_markup_strings()
		{
			$featured_item_markup_strings = array();
			$featured_items = $this->get_featured_items();

			//$es = new entity_selector( $this->site_id );
			//$es->description = 'Selecting featured news items for this publication';
			//$es->add_type( id_of('news') );
			//$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_featured_post') );
			//$temp = $es->run();
			//$featured_items = current($temp);
			
			if (!empty($featured_items))
			{
				foreach($featured_items as $id => $entity)
				{
					$featured_item_markup_generator = $this->set_up_generator_of_type('featured_item', $entity);
					$featured_item_markup_strings[$id] = $featured_item_markup_generator->get_markup();
				}
			}

			return $featured_item_markup_strings;
		}
		
		/**
		* Returns entity selector with featured items for the current publication - stored in a static variable
		* so that the entity selector will not be run multiple times by different modules or the same publication
		*/
		function get_featured_items()
		{
			if ($this->related_mode) return $this->get_related_featured_items();
			else
			{
				$page_num = isset($this->request['page']) ? $this->request['page'] : 1;
				if ($this->show_featured_items == false || $page_num > 1) return array();
				static $featured_items;
				if (!isset($featured_items[$this->publication->id()]))
				{
					$es = (isset($this->pre_pagination_es)) ? carl_clone($this->pre_pagination_es) : carl_clone($this->es);
					$es->description = 'Selecting featured news items for this publication';
					$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_featured_post') );
					$es->add_rel_sort_field($this->publication->id(), relationship_id_of('publication_to_featured_post'), 'featured_sort_order' );
					$es->set_order('featured_sort_order ASC');
					$es->set_num(-1);
					$featured_items[$this->publication->id()] = $es->run_one();
				}
				return $featured_items[$this->publication->id()];
			}
		}
		
		/**
		 * Grab featured items from related publications and populate $featured_items[$pub_id]
		 *
		 * - We optimize and sort differently depending on how many related publications we are picking from.
		 * - If just one, we use relationship_sort_order, otherwise we do datetime DESC.
		 */
		function get_related_featured_items()
		{
			$featured_items = array();
			$related_pub_ids = implode(",", array_keys($this->related_publications));
			if ($this->show_featured_items && !empty($related_pub_ids))
			{
				if (count($this->related_publications) > 1) // we use dated.datetime DESC for sort order
				{
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting featured news items from related publications';
					$es->add_type( id_of('news') );
					$es->set_env('site', $this->site_id);
					$alias['rel_pub_id'] = current($es->add_right_relationship_field( 'publication_to_featured_post', 'entity', 'id', 'related_publication_id' ));
					$alias['pub_id'] = current($es->add_left_relationship_field( 'news_to_publication', 'entity', 'id', 'publication_id' ));
					$es->add_relation($alias['rel_pub_id']['table'] . '.id IN ('.$related_pub_ids.')');
					$es->add_relation($alias['rel_pub_id']['table'] . '.id = '.$alias['pub_id']['table'] . '.id');
					$es->add_relation('status.status = "published"');
					$es->set_order('dated.datetime DESC');
					$fi = $es->run_one();
					if (!empty($fi))
					{
						foreach($fi as $k=>$v)
						{
							$featured_items[$k] = $v;
						}
					}
				}
				else // lets use relationship sort order since we have only 1 related publication.
				{
					$related_pub_id = $related_pub_ids;
					$es = new entity_selector( $this->site_id );
					$es->description = 'Selecting featured news items from a single related publication';
					$es->add_type( id_of('news') );
					$es->set_env('site', $this->site_id);
					$es->add_right_relationship( $related_pub_id, relationship_id_of('publication_to_featured_post') );
					$es->add_left_relationship( $related_pub_id, relationship_id_of('news_to_publication') );
					$es->add_rel_sort_field($related_pub_id, relationship_id_of('publication_to_featured_post'), 'featured_sort_order');
					$es->set_order('featured_sort_order ASC');
					
					$fi = $es->run_one();
					if (!empty($fi))
					{
						foreach($fi as $k=>$v)
						{
							$featured_items[$k] = $v;
							$featured_items[$k]->set_value('publication_id', $related_pub_id);
							$featured_items[$k]->set_value('related_publication_id', $related_pub_id);
						}
					}
				}
			}
			return $featured_items;
		}
		
		/**
		* Checks to see if there are existing values in $REQUEST for the given query strings.
		* @param $query_strings Array of variables whose values should be preserved in the new query string
		* @return array Query string variables with corresponding values
		*/	
		function get_query_string_values($query_strings)
		{
			$link_args = array();
			foreach($query_strings as $key=>$value)
			{
				if(is_int($key))
				{
					$query = $value;
					if(!empty($this->request[$query]))
						$link_args[$query] = $this->request[$query];
				}
				else
					$link_args[$key] = $value;
			}
			//If we're looking at the most recent issue, the issue id might not be set in the request array
			if(in_array('issue_id', $query_strings) && empty($this->request['issue_id']) && !empty($this->issue_id))
			{
				$link_args['issue_id'] = $this->issue_id;
			}
			return $link_args;
		}
		
		
		//overloaded from generic 3 so that we can preserve issue and section values in the links
		function get_pages_for_pagination_markup()
		{
			$pages = array();
			for($i = 1; $i <= $this->total_pages; $i++)
			{
				$args = $this->get_query_string_values(array('issue_id', 'section_id'));
				$args['page'] = $i;
				$pages[$i] = array('url' => $this->construct_link(NULL, $args) );
			}
			return $pages;
		}
		
		function get_search_interface_markup()
		{
			$markup = $this->get_filter_markup();
			if(!empty($markup['search']))
				return $markup['search'];
			return '';
		}
		
		function get_filter_interface_markup()
		{
			$markup = $this->get_filter_markup();
			if(!empty($markup['filter']))
				return $markup['filter'];
			return '';
		}
		
		/**	
		* Returns the number of comments associated with a news item.
		* @param entity news item
		* @return int number of comments associated with news item
		*/	
		function count_comments($item)
		{
			
			$es = new entity_selector( $this->site_id );
			$es->description = 'Counting comments for this news item';
			$es->add_type( id_of('comment_type') );
			$es->add_relation('show_hide.show_hide = "show"');
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_comment') );
			return $es->get_one_count();
		}

		/**
		*  Returns the permalink to an item.
		*  @return string the url of the permalink
		*/
		function construct_permalink($item)
		{
			$link = carl_make_link( array($this->query_string_frag.'_id'=>$item->id()), '', '', true, false );
			return $link;
		}
		
		function get_link_to_full_item(&$item)
		{
			if($this->related_mode)
			{
				return $this->get_link_to_related_item($item);
			}
			else
			{
				$link_args = $this->get_query_string_values(array('issue_id', 'section_id'));
				return $this->construct_link($item, $link_args);
			}
		}
		
		function get_link_to_full_item_other_pub(&$item)
		{
			$link_args = $this->get_query_string_values(array('issue_id', 'section_id'));
			return $this->construct_link($item, $link_args);
		}
	
		// THIS STUFF SHOULD ALL BE IN MARKUP GENERATORS
		
		//overloaded from generic3
		function show_style_string()
		{
			$class_string = ($this->related_mode) ? 'relatedPub' : 'publication';
			if(!empty( $this->current_item_id ) )
				$class_string .= ' fullPostDisplay';
			echo '<div id="'.$this->style_string.'" class="'.$class_string.'">'."\n";
		}
		
		//overloaded from generic3
		function show_back_link()
		{
			echo '<div class="back">';
			
			$main_list_name = $this->publication->get_value('name');
			$current_issue = $this->get_current_issue();
			if(!empty($current_issue))
				$main_list_name .= ', '.$current_issue->get_value('name');
			echo '<div class="mainBackLink"><a href="'.$this->construct_back_link().'">Return to '.$main_list_name.'</a></div>';
			
			
			$current_section = $this->get_current_section();
			if(!empty($current_section))
			{
				$section_name = $current_section->get_value('name').' ('.$main_list_name.')';
				echo '<div class="sectionBackLink"><a href="'.$this->construct_back_to_section_link().'">Return to '.$section_name.'</a></div>';
			}
			
			$back_to_filter_link = $this->construct_back_to_filters_link();
			if(!empty($back_to_filter_link))
			{
				echo '<div class="filtersBackLink"><a href="'.$back_to_filter_link.'">Return to your search/category results</a></div>';
			}
			
			echo '</div>';
		}
		
		function show_item_name( $item ) // {{{
		{
			//emptied out so that item markup generator can handle it
		} // }}}
		
		function get_inline_editing_info()
		{
			$inline_edit =& get_reason_inline_editing($this->page_id);
			
			$url = carl_make_link($inline_edit->get_activation_params($this));
			$available = $inline_edit->available_for_module($this);
			$active = $inline_edit->active_for_module($this);
			
			return array('url' => $url, 'available' => $available, 'active' => $active);
		}
		
		function construct_back_link()
		{
			//need to go back to page 1, since we could have a page value wherever we are now
			$args = array('page' => 1);
			if(!empty($this->issue_id))
			{
				$args[] = 'issue_id';
				if(!empty($this->filters))
				{
					$args['filter1'] = $args['filter2'] = $args['filter3'] = '';
				}
				if(!empty($this->request['search']))
				{
					$args['search'] = '';
				}
			}
			return $this->construct_link(NULL, $this->get_query_string_values($args));	
		}
		
		function construct_back_to_section_link()
		{
			if(!empty($this->request['section_id']) && !empty($this->request[$this->query_string_frag.'_id']) )
			{
				$args = array('section_id');
				
				//if we're looking at a section from an issue, we want to still just be looking at items from that issue
				if(!empty($this->issue_id))
				{
					$args[] = 'issue_id';
				}	
				
				return $this->construct_link(NULL, $this->get_query_string_values($args));
			}
			else 
				return false;
		}
		
		function construct_back_to_filters_link()
		{
			if((!empty($this->filters) || !empty($this->request['search'])) && !empty($this->issue_id) && !empty($this->request[$this->query_string_frag.'_id']) )
			{
				$args = array('filter1','filter2','filter3', 'search', 'issue_id' => '');
				
				return $this->construct_link(NULL, $this->get_query_string_values($args));
			}
			else 
				return false;
		}
	
		function get_teaser_image($item)
		{
			if (!isset($this->_teasers[$item->id()]))
			{
				$es = new entity_selector();
				$es->description = 'Finding teaser image for news item';
				$es->set_env( 'site' , $this->site_id );
				$es->add_type( id_of('image') );
				$es->add_right_relationship( $item->id(), relationship_id_of('news_to_teaser_image') );
				$es->set_num (1);
				$this->_teasers[$item->id()] = $es->run_one();
			}
			return $this->_teasers[$item->id()];
		}
		
		function get_item_number($item)
		{
			$item_keys = array_keys($this->items);
			$item_order_by_key = array_flip($item_keys);
			return (in_array($item->id(), $item_keys)) ? $item_order_by_key[$item->id()] : false;
		}
	
		function get_link_to_related_item(&$item)
		{
			$pub_id_field = $item->get_value('publication_id');
			$pub_id = (is_array($pub_id_field)) ? array_shift($pub_id_field) : $pub_id_field;
			$links = $this->get_basic_links_to_current_publications();
			if(isset($links[$pub_id]))
			{
				return $links[$pub_id] . carl_construct_query_string( array( $this->query_string_frag.'_id' => $item->id()), array('textonly'));
			}
			else
				return '';
		}
		function get_item_publication(&$item)
		{
			if(isset($this->related_publications[$item->get_value('publication_id')]))
			{
				return $this->related_publications[$item->get_value('publication_id')];
			}
			return;
		}
		function get_basic_links_to_current_publications()
		{
			if(empty($this->related_publications_links))
			{
				foreach($this->related_publications as $pub)
				{
					$page_id_field = $pub->get_value('page_id');
					$page_id = (is_array($page_id_field)) ? array_shift($page_id_field) : $page_id_field;
					if($page_id)
					{
						$this->related_publications_links[$pub->id()] = reason_get_page_url($page_id);
					}
				}
			}
			return $this->related_publications_links;
		}
		function get_links_to_current_publications()
		{
			$links = $this->get_basic_links_to_current_publications();
			if($this->textonly)
			{
				foreach($links as $id=>$link)
				{
					$links[$id] = $link.'?textonly=1';
				}
			}
			return $links;
		}
		function get_item_events($item)
		{
			$es = new entity_selector();
			$es->set_env( 'site' , $this->site_id );
			$es->description = 'Selecting events for this news item';
			$es->add_type( id_of('event_type') );
			$es->add_left_relationship( $item->id(), relationship_id_of('event_to_news') );
			$es->add_rel_sort_field( $item->id(), relationship_id_of('event_to_news') );
			$es->set_order('rel_sort_order');
			$events = $es->run_one();
			if (!empty($events))
			{
				$base_url = $this->get_events_page_url();
				foreach(array_keys($events) as $id)
				{
					$url = $base_url;
					if(!empty($url))
					{
						$url .= '?event_id='.$id;
						if($this->textonly)
							$url .= '&amp;textonly=1';
					}
					$events[$id]->set_value('event_url',$url);
				}
			}
			return $events;
		}
		function get_events_page_url()
		{
			if(!$this->queried_for_events_page_url)
			{
				reason_include_once('classes/module_sets.php');
				$ms =& reason_get_module_sets();
				$modules = $ms->get('event_display');
				$rpts =& get_reason_page_types();
				$events_page_types = $rpts->get_page_type_names_that_use_module($modules);
				$ps = new entity_selector($this->site_id );
				$ps->add_type( id_of('minisite_page') );
				$ps->set_num(1);
				$rels = array();
				foreach($events_page_types as $page_type)
				{
					$rels[] = 'page_node.custom_page = "'.$page_type.'"';
				}
				$ps->add_relation('( '.implode(' OR ', $rels).' )');
				$page_array = $ps->run_one();
				if (!empty($page_array))
				{
					$events_page = current($page_array);
					$this->events_page_url = reason_get_page_url($events_page->id());
				}
			}
			return $this->events_page_url;
		}
		function get_item_images($item)
		{
			if (!isset($this->_item_images[$item->id()]))
			{
				$es = new entity_selector();
				$es->set_env( 'site' , $this->site_id );
				$es->description = 'Selecting images for news item';
				$es->add_type( id_of('image') );
				$es->add_right_relationship( $item->id(), relationship_id_of('news_to_image') );
				$es->add_rel_sort_field( $item->id(), relationship_id_of('news_to_image') );
				$es->set_order('rel_sort_order');
				$this->_item_images[$item->id()] = $es->run_one();
			}
			return $this->_item_images[$item->id()];
		}
		function get_item_media($item)
		{
			if (!isset($this->_item_media[$item->id()]))
			{
				$es = new entity_selector();
				$es->set_env( 'site' , $this->site_id );
				$es->description = 'Selecting media for news item';
				$es->add_type( id_of('av') );
				$es->add_right_relationship( $item->id(), relationship_id_of('news_to_media_work') );
				$es->add_rel_sort_field( $item->id(), relationship_id_of('news_to_media_work') );
				$es->set_order('rel_sort_order');
				$es->add_relation( 'show_hide.show_hide = "show"' );
				$es->add_relation( '(media_work.transcoding_status = "ready" OR ISNULL(media_work.transcoding_status) OR media_work.transcoding_status = "")' );
				$this->_item_media[$item->id()] = $es->run_one();
			}
			return $this->_item_media[$item->id()];
		}
		function get_item_assets($item)
		{
			$es = new entity_selector();
			$es->description = 'Selecting assets for news item';
			$es->set_env( 'site' , $this->site_id );
			$es->add_type( id_of('asset') );
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_asset') );
			return $es->run_one();
		}
		function get_item_categories($item)
		{
			$es = new entity_selector();
			$es->description = 'Selecting categories for news item';
			$es->add_type( id_of('category_type') );
			$es->set_env('site',$this->site_id);
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_category') );
			$es->set_order( 'entity.name ASC' );
			$cats = $es->run_one();
			if(!empty($cats))
			{
				foreach(array_keys($cats) as $id)
				{
					$url = '?filters[1][type]=category&filters[1][id]='.$id;
					if($this->textonly)
						$url .= '&amp;textonly=1';
					$cats[$id]->set_value('category_url',$url);
				}
			}
			return $cats;
		}
		
		/**
		 * Get social sharing links from social sharing integrators that support social sharing links.
		 *
		 * Returns an array where each item is an array with these keys:
		 *
		 * - src (for the image)
		 * - alt (for the image)
		 * - href (the actual link)
		 *
		 * We only return items is the page is public and the publication has social sharing enabled.
		 *
		 * @param object
		 * @return array
		 */
		function get_item_social_sharing($item)
		{
			if ($this->page_is_public() && 
				$this->publication->has_value('enable_social_sharing') && (
				$this->publication->get_value('enable_social_sharing') == 'yes' )
				)
			{
				reason_include_once('classes/social.php');
				$helper = reason_get_social_integration_helper();
				$integrators = $helper->get_social_integrators_by_interface('SocialSharingLinks');
				if (!empty($integrators))
				{
					foreach ($integrators as $integrator_type => $integrator)
					{
						$item_social_sharing[$integrator_type]['icon'] = $integrator->get_sharing_link_icon();
						$item_social_sharing[$integrator_type]['text'] = $integrator->get_sharing_link_text();
						$item_social_sharing[$integrator_type]['href'] = $integrator->get_sharing_link_href();
					}
					return $item_social_sharing;
				}
			}
			return false;
		}
		function get_item_comments($item)
		{
			$es = new entity_selector();
			$es->description = 'Selecting comments for news item';
			$es->add_type( id_of('comment_type') );
			$es->set_env( 'site' , $this->site_id );
			$es->add_relation('show_hide.show_hide = "show"');
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_comment') );
			$es->set_order( 'dated.datetime ASC' );
			return $es->run_one();
		}
		function get_comment_has_errors($item)
		{
			return (isset($this->_comment_has_errors)) ? $this->_comment_has_errors : '';
		}
		function get_comment_form_markup($item)
		{
			if($this->comment_form_ok_to_run($item))
			{
				reason_include_once($this->comment_form_file_location);
				$identifier = basename( $this->comment_form_file_location, '.php');
				if(empty($GLOBALS[ '_publication_comment_forms' ][ $identifier ]))
				{
					trigger_error('Comment forms must identify their class name in the $GLOBALS array; the form located at '.$this->comment_form_file_location.' does not and therefore cannot be run.');
					return '';
				}
				
				$form_class = $GLOBALS[ '_publication_comment_forms' ][ $identifier ];
				
				ob_start();
				$form = new $form_class($this->site_id, $item, $this->get_comment_moderation_state(), $this->publication);
				$form->set_username($this->get_user_netid());
				$form->run();
				if ($form->has_errors())
					$this->_comment_has_errors = 'yes';
				else 
					$this->_comment_has_errors = 'no';
				$content = ob_get_contents();
				ob_end_clean();
				
				return $content;
			}
			return '';
		}
		/**
		 * Specifies which state commenting is currently in
		 *
		 * Possible statuses:
		 * - login_required (comments are on and require auth, but user not currently logged in)
		 * - open_comments (comments are on and do not require auth)
		 * - user_has_permission (comments are on and require auth; user has authenticated and is authorized to comment)
		 * - user_not_permitted (comments are on and require auth; user has authenticated but is not authorized to comment)
		 * - item_comments_off (comments are on for the publication, but comments have been turned off on this post)
		 * - publication_comments_off (comments are off for the entire publication)
		 *
		 * @param $item post entity
		 * @return string $status One of the above statuses
		 */
		function commentability_status($item)
		{
			if(empty($this->commenting_status[$item->id()]))
			{
				// if the item does not exist in the items array we return publication_comments_off without bothering to do the full check.
				$this->commenting_status[$item->id()] = (isset($this->items[$item->id()])) ? $this->get_commentability_status_full_check($item) : 'publication_comments_off';
			}
			return $this->commenting_status[$item->id()];
		}
		function get_commentability_status_full_check($item)
		{
			$group_helper = &$this->get_comment_group_helper();
			
			if(!$group_helper->group_has_members())
			{
				return 'publication_comments_off';
			}
			if($item->get_value('commenting_state') == 'off')
			{
				return 'item_comments_off';
			}
			if(!$group_helper->requires_login())
			{
				return'open_comments';
			}
		
			$netid = $this->get_user_netid();
			if(empty($netid))
			{
				return 'login_required';
			}
		
			if($group_helper->has_authorization($netid))
			{
				return 'user_has_permission';
			}
			else
			{
				return 'user_not_permitted';
			}
		}
		function comment_form_ok_to_run($item)
		{
			$status = $this->commentability_status($item);
			if($status == 'open_comments' || $status == 'user_has_permission')
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		function get_user_netid()
		{
			if(empty($this->user_netID))
			{
				$this->user_netID = reason_check_authentication();
			}
			return $this->user_netID;
		}
		
		function alter_relationship_checker_es($es)
		{
			$es->add_left_relationship( $this->publication->id(), relationship_id_of('news_to_publication') );
			$es->add_relation('status.status = "published"');
			return $es;
		}
		
		function get_sanitized_search_string()
		{
			if(!empty($this->request['search']))
			{
				return reason_htmlspecialchars($this->request['search']);
			}
			else
				return '';
		}
		function get_text_only_state()
		{
			return $this->textonly;
		}
		
		function get_previous_post($entity)
		{
			$p = $this->get_previous_item($entity->id());
			if(!empty($p))
				$p->set_value('link_url',$this->get_link_to_full_item($p));
			return $p;
		}
		function get_next_post($entity)
		{
			$p = $this->get_next_item($entity->id());
			if(!empty($p))
				$p->set_value('link_url',$this->get_link_to_full_item($p));
			return $p;
		}
		
		//////////////////////////////////////////
		// Inline Editing Functions
		//////////////////////////////////////////
		
		/**
		 * Determines whether or not the user can inline edit.
		 *
		 * Returns true in two cases:
		 *
		 * 1. User is a site administrator of the page the story belongs to.
		 * 2. User is the author of the post.
		 *
		 * @return boolean;
		 */
		function user_can_inline_edit()
		{
			if (!isset($this->_user_can_inline_edit))
			{
				if (!empty($this->current_item_id))
				{
					$story_id = $this->current_item_id;
					$story = new entity($story_id);
					if (reason_is_entity($story, 'news'))
					{
						$owner = get_owner_site_id($story_id);
						$this->_user_can_inline_edit = (!empty($owner) && reason_check_authentication() && ((reason_check_access_to_site($owner) || $this->user_is_author())));
					}
					else $this->_user_can_inline_edit = false;
				}
				else
				{
					$this->_user_can_inline_edit = false;
				}
			}
			return $this->_user_can_inline_edit;
		}
		
		/**
		* Checks to see if the user's id matches the auther of the current item.
		*/ 
		function user_is_author()
		{
			if (isset($this->current_item_id) && ($netid = reason_check_authentication()))
			{
				$item = new entity($this->current_item_id);
				if (reason_is_entity($item, 'news'))
				{
					if ($item->get_value('created_by') == get_user_id($netid))
					{
						return true;
					}
				}
			}
			return false;
		}
		
		function show_inline_editing_form()
		{
			echo '<div class="editable editing">';
			$item = new entity($this->current_item_id);
			$item_title = $item->get_value('release_title');
			$item_content = $item->get_value('content');
			$item_description = $item->get_value('description');
			$form = new Disco();
			$form->strip_tags_from_user_input = true;
			$form->allowable_HTML_tags = REASON_DEFAULT_ALLOWED_TAGS;
			$form->actions = array('save' => 'Save', 'save_and_finish' => 'Save and Finish Editing');
			
			$this->init_field($form, 'title_of_story', 'name', $item, 'text', 'solidtext');
			//$form->add_element('title_of_story', 'text');
			$form->set_display_name('title_of_story', 'Title');
			$form->set_value('title_of_story', $item_title);
			
			$this->init_field($form, 'editable_content', 'content', $item, html_editor_name($this->site_id), 'wysiwyg_disabled', html_editor_params($this->site_id, get_user_id($this->get_user_netid())));
			//$form->add_element('editable_content', html_editor_name($this->site_id), html_editor_params($this->site_id, get_user_id($this->get_user_netid())));
			$form->set_display_name('editable_content','Content');
			$form->set_value('editable_content', $item_content);
			
			$this->init_field($form, 'description_of_story', 'description', $item, html_editor_name($this->site_id), 'wysiwyg_disabled', html_editor_params($this->site_id, get_user_id($this->get_user_netid())));
			//$form->add_element('description_of_story', html_editor_name($this->site_id), html_editor_params($this->site_id, get_user_id($this->get_user_netid())));
			$form->set_display_name('description_of_story', 'Excerpt/Teaser (displayed on post listings; not required):');
			$form->set_value('description_of_story', $item_description);
			
			$form->add_callback(array(&$this, 'process_editable'),'process');
			$form->add_callback(array(&$this, 'where_to_editable'), 'where_to');
			$form->add_callback(array(&$this, 'run_error_checks_editable'), 'run_error_checks');
			$form->run();
			echo '</div>';
		}
		
		
		/**
		* Inits a disco form element as a locked or unlocked field.
		*/
		function init_field($form, $field_name, $entity_field_name, $item, $type, $lock_type, $params = null)
		{
			if ($netid = $this->get_user_netid())
			{
				if ($user_id = get_user_id($netid))
				{
					$user = new entity($user_id);
					if ($item->user_can_edit_field($entity_field_name, $user))
					{
						$form->add_element($field_name, $type, $params);
					}
					else
					{
						$form->add_element($field_name, $lock_type);
						$form->set_comments($field_name, '');
						$form->set_comments($field_name, '<img class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" />', 'before' );
					}
				}
			}
		}

		/**
		* Update the Reason entity that the user edited.
		*/
		function process_editable(&$disco)
		{
			$values = array();
			$values['release_title'] = trim(strip_tags($disco->get_value('title_of_story')));
			$values['content'] = trim(tidy($disco->get_value( 'editable_content' )));
			$values['description'] = trim(tidy($disco->get_value('description_of_story')));
			$archive = ($disco->get_chosen_action() == 'save_and_finish') ? true : false;
			reason_update_entity($this->current_item_id, get_user_id($this->get_user_netid()), $values, $archive );
		}
		
		// Callback for disco edit form
		function where_to_editable(&$disco)
		{
			if( $disco->get_chosen_action() == 'save' )
			{
				$url = get_current_url();
			}
			else
			{
				$inline_edit =& get_reason_inline_editing($this->page_id);
				$url = carl_make_redirect($inline_edit->get_deactivation_params($this));
			}
			return $url;
		}		
		
		function run_error_checks_editable(&$disco)
		{
		}
	}
?>
