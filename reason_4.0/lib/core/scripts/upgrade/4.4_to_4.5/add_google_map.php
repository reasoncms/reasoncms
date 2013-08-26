<?php
/**
 * Upgrader that adds the google map type created at Luther College to Reason CMS.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_google_map'] = 'ReasonUpgrader_45_SetupGoogleMapType';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class ReasonUpgrader_45_SetupGoogleMapType  extends reasonUpgraderDefault implements reasonUpgraderInterface
{
	var $google_map_type_details = array (
			'new' => 0,
			'unique_name' => 'google_map_type',
			'custom_content_handler' => 'google_map.php',
			'plural_name' => 'Google Maps'
	);	
	var $google_map_table_fields = array(
			'google_map_zoom_level' => array('db_type' => 'integer'),
			'google_map_latitude' => array('db_type' => 'double'),
			'google_map_longitude' => array('db_type' => 'double'),
			'google_map_show_campus_template' => array('db_type' => 'enum("yes", "no")'),
			'google_map_msid' => array('db_type' => 'text'),
			'google_map_show_primary_pushpin' => array('db_type' => 'enum("show", "hide")'),
			'google_map_primary_pushpin_latitude' => array('db_type' => 'double'),
			'google_map_primary_pushpin_longitude' => array('db_type' => 'double'),
			'google_map_show_directions' => array('db_type' => 'enum("show", "hide")'),
			'google_map_destination_latitude' => array('db_type' => 'double'),
			'google_map_destination_longitude' => array('db_type' => 'double'),
	);	
	var $page_to_google_map_details = array (
			'description' => 'Page to Google Map',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name' => 'Place Google Map on this page'
	);	
	var $event_to_google_map_details = array (
			'description' => 'Event to Google Map',
			'directionality' => 'unidirectional',
			'connections' => 'one_to_many',
			'required' => 'no',
			'is_sortable' => 'no',
			'display_name' => 'Show Google Map'
	);
	
	/**
	 * Get the title of the upgrader
	 * @return string
	 */
	public function title()
	{
		return 'Add Google Map type';
	}
	
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>Adds google map type created at Luther College to Reason CMS.</p>';
	}
	
	public function test()
	{
		if($this->google_map_type_exists())
		{
			return '<p>The google map type exists. This script has already run.</p>';
		}
		else
		{
			return '<p>Would create google map type.</p>';
		}
	}
	
	public function run()
	{
		if($this->google_map_type_exists())
		{
			return '<p>The google map type exists. This script has already run.</p>';
		}
		else
		{
			$this->create_google_map_type();
			return '<p>Created google map type.</p>';
		}
	}
	
	/**
	 * Create the google map type.
	 */
	protected function create_google_map_type()
	{
		// make sure the cache is clear
		reason_refresh_relationship_names();
		reason_refresh_unique_names();
		$str = '';
		$google_map_id = reason_create_entity(id_of('master_admin'), id_of('type'), $this->user_id(), 'Google Map', $this->google_map_type_details);
		create_default_rels_for_new_type($google_map_id);
		create_reason_table('google_map', $this->google_map_type_details['unique_name'], $this->user_id());
		
		
		$ftet = new FieldToEntityTable('google_map', $this->google_map_table_fields);
		$ftet->update_entity_table();
		ob_start();
		$ftet->report();
		$str .= ob_get_contents();
		ob_end_clean();	
		
		// create all the necessary relationships for the google map type
		create_allowable_relationship(id_of('minisite_page'), id_of('google_map_type'), 'page_to_google_map', $this->page_to_google_map_details);
		create_allowable_relationship(id_of('event_type'), id_of('google_map_type'), 'event_to_google_map', $this->event_to_google_map_details);
		$str .= '<p>Created page_to_google_map and even_to_google_map allowable relationships</p>';
		return $str;
	}
	
	protected function google_map_type_exists()
	{
		return reason_unique_name_exists('google_map_type', false);
	}
}
?>