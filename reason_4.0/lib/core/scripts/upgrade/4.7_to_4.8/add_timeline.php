<?php
/**
 * Upgrader that adds the Knightlab timeline type created at HoneyCo to Reason CMS database.
 * See: https://timeline.knightlab.com/
 * and http://timeline.knightlab.com/docs/json-format.html
 *
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.7_to_4.8']['add_timeline'] = 'ReasonUpgrader_48_AddTimeline';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_48_AddTimeline implements reasonUpgraderInterface
{
	var $timeline_type_details = array (
			'new' => 0,
			'unique_name' => 'timeline_type',
			'custom_content_handler' => 'timeline.php',
			'plural_name' => 'Timelines'
	);
	
	var $timeline_item_type_details = array (
			'new' => 0,
			'unique_name' => 'timeline_item_type',
			'custom_content_handler' => 'timeline_item.php',
			'plural_name' => 'Timeline Items'
	);
	
	var $timeline_item_type_schema = array (
			'start_date' => array('db_type' => 'datetime'),
			'end_date' => array('db_type' => 'datetime'),
			'text' => array('db_type' => 'text'),
			'display_date' => array('db_type' => 'tinytext'),
			'background' => array('db_type' => 'tinytext'),
			'autolink' => array('db_type' => 'enum("yes", "no")'),
			'media' => array('db_type' => 'enum("reason_image", "reason_media_work", "reason_location", "other")'),
			'other_media' => array('db_type' => 'tinytext'),   // A simple URL/string per http://timeline.knightlab.com/docs/media-types.html
			'group' => array('db_type' => 'tinytext'),		
	);
	
	var $page_to_timeline = array (
			'description' => 'Page to Timeline',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Timeline',
			'display_name_reverse_direction'=>'Page(s)',
			'description_reverse_direction'=>'Page(s)'
	);
	
	var $timeline_to_timeline_item = array (
			'description' => 'Timeline includes Timeline Item',
			'directionality' => 'bidirectional',
			'connections' => 'many_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Timeline Items',
			'display_name_reverse_direction'=>'Timeline',
			'description_reverse_direction'=>'Timeline'
	);
	
	var $timeline_to_title_timeline_item = array (
			'description' => 'Timeline uses Timeline Item as Title Slide',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Title Item',
			'display_name_reverse_direction'=>'Title for Timeline',
			'description_reverse_direction'=>'Title for Timeline'
	);
	
	var $timeline_item_to_image = array (
			'description' => 'Timeline Item uses Image',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Image',
			'display_name_reverse_direction'=>'Timeline Item',
			'description_reverse_direction'=>'Timeline Item'
	);
	
	var $timeline_item_to_media_work = array (
			'description' => 'Timeline Item uses Media Work',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Media Work',
			'display_name_reverse_direction'=>'Timeline Item',
			'description_reverse_direction'=>'Timeline Item'
	);
	
	var $timeline_item_to_location = array (
			'description' => 'Timeline Item has Location',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Location',
			'display_name_reverse_direction'=>'Timeline Item',
			'description_reverse_direction'=>'Timeline Item'
	);
	
	var $timeline_item_to_category = array (
			'description' => 'Timeline Item has Category',
			'directionality' => 'unidirectional',
			'connections' => 'many_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name'=>'Categories',
			'display_name_reverse_direction'=>'Timeline Item',
			'description_reverse_direction'=>'Timeline Item'
	);	
	
	public function user_id( $user_id = NULL)
	{
		if (!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
	
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Add timeline';
	}
	
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	 public function description()
	 {
	 	$str = "<p>This upgrade creates new types for Knightlab timeline and timeline item data</p>";
	 	return $str;
	 }
	 
	 public function test()
	 {
	 	if ($this->timeline_type_exists() && $this->timeline_item_type_exists())
	 	{
	 		return '<p>Timeline support is set up. This script has already run.</p>';
	 	}
	 	else
	 	{
	 		$str = '';
	 		if (!$this->timeline_type_exists())
	 			$str .= '<p>Would create timeline type.</p>';
	 		if (!$this->timeline_item_type_exists())
	 			$str .= '<p>Would create timeline_item type.</p>';
	 		return $str;
	 	}
	 }
	 
	 /**
	  * Run the upgrader
	  * @return string HTML report
	  */
	 public function run()
	 {
	 	if (!$this->timeline_type_exists() && $this->timeline_item_type_exists())
	 	{
	 		$str = '<p>Timeline support is set up. This script has already run.</p>';
	 	}
	 	else
	 	{
	 		$str = '';
	 		if (!$this->timeline_type_exists())
	 		{
	 			$str .= $this->create_timeline_type();
	 		}
	 		if (!$this->timeline_item_type_exists())
	 		{
	 			$str .= $this->create_timeline_item_type();
	 		}
	 		$str .= $this->create_timeline_relationships();
	 		return $str;
	 	}
	 	return $str;
	 }
	 
	 /// FUNCTIONS THAT DO THE CREATION WORK
	 protected function create_timeline_type()
	 {
	 	$str = '';
	 
	 	$timeline_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Timeline', $this->timeline_type_details);
	 	$str .= '<p>Create timeline type entity</p>';
	 	create_default_rels_for_new_type($timeline_type_id);
	 	create_reason_table('timeline', $this->timeline_type_details['unique_name'], $this->user_id());	 
	 	
	 	return $str;
	 }
	 
	 protected function create_timeline_item_type()
	 {
	 	$str = '';
	 
	 	$timeline_item_type_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Timeline Item', $this->timeline_item_type_details);
	 	$str .= '<p>Create timeline item type entity</p>';
	 	create_default_rels_for_new_type($timeline_item_type_id);
	 	create_reason_table('timeline_item', $this->timeline_item_type_details['unique_name'], $this->user_id());
	 
	 	$ftet = new FieldToEntityTable('timeline_item', $this->timeline_item_type_schema);
	 		
	 	$ftet->update_entity_table();
	 	ob_start();
	 	$ftet->report();
	 	$str .= ob_get_contents();
	 	ob_end_clean();
	 
	 	return $str;
	 }
	 
	 protected function create_timeline_relationships()
	 {
	 	reason_refresh_unique_names();  // force refresh from the database just in case.
	 	$str = '';
	 	if (!reason_relationship_name_exists('page_to_timeline'))
	 	{
	 		create_allowable_relationship(id_of('minisite_page'), id_of('timeline_type'), 'page_to_timeline', $this->page_to_timeline);
	 		$str .= '<p>Created page to timeline relationship.</p>';
	 	}
	 	if (!reason_relationship_name_exists('timeline_to_timeline_item'))
	 	{
	 		create_allowable_relationship(id_of('timeline_type'), id_of('timeline_item_type'), 'timeline_to_timeline_item', $this->timeline_to_timeline_item);
	 		$str .= '<p>Created timeline to timeline item relationship.</p>';
	 	}
	 	if (!reason_relationship_name_exists('timeline_to_title_timeline_item'))
	 	{
	 		create_allowable_relationship(id_of('timeline_type'), id_of('timeline_item_type'), 'timeline_to_title_timeline_item', $this->timeline_to_title_timeline_item);
	 		$str .= '<p>Created timeline to title timeline item relationship.</p>';
	 	}
	 	if (!reason_relationship_name_exists('timeline_item_to_image'))
	 	{
	 		create_allowable_relationship(id_of('timeline_item_type'), id_of('image'), 'timeline_item_to_image', $this->timeline_item_to_image);
	 		$str .= '<p>Created timeline item to image relationship.</p>';
	 	}
	 	if (!reason_relationship_name_exists('timeline_item_to_media_work'))
	 	{
	 		create_allowable_relationship(id_of('timeline_item_type'), id_of('av'), 'timeline_item_to_media_work', $this->timeline_item_to_media_work);
	 		$str .= '<p>Created timeline item to media work relationship.</p>';
	 	}
	 	/*if (!reason_relationship_name_exists('timeline_item_to_location'))
	 	{
	 		create_allowable_relationship(id_of('timeline_item_type'), id_of('location'), 'timeline_item_to_location', $this->timeline_item_to_location);
	 		$str .= '<p>Created timeline item to location relationship.</p>';
	 	}*/
	 	if (!reason_relationship_name_exists('timeline_item_to_category'))
	 	{
	 		create_allowable_relationship(id_of('timeline_item_type'), id_of('category_type'), 'timeline_item_to_category', $this->timeline_item_to_category);
	 		$str .= '<p>Created timeline item to category relationship.</p>';
	 	}

	 	return $str;
	 }
	 	 
	 /// FUNCTIONS THAT CHECK IF WE HAVE WORK TO DO
	 protected function timeline_type_exists()
	 {
	 	reason_refresh_unique_names();  // force refresh from the database just in case.
	 	return reason_unique_name_exists('timeline_type');
	 }
	 
	 protected function timeline_item_type_exists()
	 {
	 	return reason_unique_name_exists('timeline_item_type');
	 }
	 	
}

