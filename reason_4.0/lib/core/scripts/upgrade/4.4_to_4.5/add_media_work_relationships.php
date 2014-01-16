<?php
/**
 * Upgrader that adds storage-related fields to media works.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.4_to_4.5']['add_media_work_relationships'] = 'ReasonUpgrader_45_AddMediaWorkRelationships';

/**
 * @todo also add event_to_media_work relationship
 */
class ReasonUpgrader_45_AddMediaWorkRelationships implements reasonUpgraderInterface
{

	protected $user_id;
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
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
		return 'Add news and event to media work relationships';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This upgrade adds the news_to_media_work and event_to_media_work allowable relationships.</p>';
	}

	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		if(reason_relationship_name_exists('news_to_media_work'))
		{
			if(reason_relationship_name_exists('event_to_media_work'))
			{
				return '<p>This update has already run.</p>';
			}
			else
			{
				return '<p>The news_to_media_work allowable relationship has already been added.</p><p>This update will add the event_to_media_work allowable relationship.</p>';
			}
		}
		elseif(reason_relationship_name_exists('event_to_media_work'))
		{
			return '<p>The event_to_media_work allowable relationship has already been added.</p><p>This update will add the news_to_media_work allowable relationship.</p>';
		}
		else
		{
			return '<p>This update will add the news_to_media_work and event_to_media_work allowable relationships.</p>';
		}
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		$run_message = '';
		if(!reason_relationship_name_exists('news_to_media_work'))
		{
			$news_to_media_work_definition = array (
				'description'=>'News / Post shows Media Work',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'yes',
				'display_name'=>'Media',
				'display_name_reverse_direction'=>'News / Posts',
			);	
			if(create_allowable_relationship(id_of('news'),id_of('av'),'news_to_media_work', $news_to_media_work_definition))
				$run_message .= '<p>Added the news_to_media_work allowable relationship.</p>';
			else
				$run_message .= '<p>Failed to create the news_to_media_work allowable relationship. Try again. If you are not successful, you may wish to try to add this relationship type manually: In Master Admin, go to Allowable Relationship Manager, add a row, then create a relationship between News / Post and Media Work named news_to_media work. The other values are:</p>'.spray($news_to_media_work_definition);
		}	
		if(!reason_relationship_name_exists('event_to_media_work'))
		{
			$event_to_media_work_definition = array (
				'description'=>'Event shows Media Work',
				'directionality'=>'bidirectional',
				'connections'=>'many_to_many',
				'required'=>'no',
				'is_sortable'=>'yes',
				'display_name'=>'Media',
				'display_name_reverse_direction'=>'Events',
			);	
			if(create_allowable_relationship(id_of('event_type'),id_of('av'),'event_to_media_work', $event_to_media_work_definition))
				$run_message .= '<p>Added the event_to_media_work allowable relationship.</p>';
			else
				$run_message .= '<p>Failed to create the event_to_media_work allowable relationship. Try again. If you are not successful, you may wish to try to add this relationship type manually: In Master Admin, go to Allowable Relationship Manager, add a row, then create a relationship between Event and Media Work named event_to_media work. The other values are:</p>'.spray($event_to_media_work_definition);
		}		
		if(!empty($run_message))
		{
			return $run_message;
		}
		else
		{
			return 'This update has already run.';
		}
	}
}
?>
