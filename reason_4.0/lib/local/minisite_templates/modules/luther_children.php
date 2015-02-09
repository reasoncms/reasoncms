<?php

/**
 * @package reason
* @subpackage minisite_modules
*/

/**
 * Include base class & register module with Reason
*/

reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'minisite_templates/modules/children.php' );
reason_include_once( 'classes/sized_image.php' );
reason_include_once( 'function_libraries/url_utils.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherChildrenModule';


/**
 * A minisite module whose output is all the child pages of the current page (or page specified in parent_unique_name param), in sort order.
 *
 * Various parameters are available for variant behavior.
 */
class LutherChildrenModule extends ChildrenModule
{
	var $es;
	var $acceptable_params = array(
			'description_part_of_link' => false,
			'provide_az_links' => false,
			'provide_images' => false,
			'randomize_images' => false,
			'show_only_pages_in_nav' => false,
			'show_only_pages_not_in_nav' => false,
			'show_external_links' => true,
			'exclude' => array(),
			'limit_to' => array(),
			'thumbnail_width' => 0,
			'thumbnail_height' => 0,
			'thumbnail_crop' => '',
			'parent_unique_name' => '',
			'force_full_page_title' => false,
			'html5' => false,
			'chunks' => 1,
	);
	var $offspring = array();
	var $az = array();

	function init( $args = array() ) // {{{
	{
		parent::init( $args );

		$this->es = new entity_selector();
		$this->es->description = 'Selecting children of the page';

		// find all the children of the parent page
		$this->es->add_type( id_of('minisite_page') );
		$this->es->add_left_relationship( $this->get_parent_page_id(), relationship_id_of( 'minisite_page_parent' ) );
		if($this->params['show_only_pages_in_nav'])
		{
			$this->es->add_relation('nav_display = "Yes"');
		}
		elseif($this->params['show_only_pages_not_in_nav'])
		{
			$this->es->add_relation('nav_display = "No"');
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
			
		if(isset($this->offspring[$this->get_parent_page_id()]))
		{
			unset($this->offspring[$this->get_parent_page_id()]);
		}
			
		if(!empty($this->params['provide_az_links']))
		{
			foreach($this->offspring as $child)
			{
				$page_name = $this->get_page_name($child);
				$letter = carl_strtoupper(substr($page_name,0,1), 'UTF-8');
				if(!in_array($letter, $this->az))
				{
					$this->az[$child->id()] = $letter;
				}
			}
		}
	}
}