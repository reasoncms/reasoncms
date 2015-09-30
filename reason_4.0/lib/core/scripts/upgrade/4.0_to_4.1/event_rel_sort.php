<?php
/**
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.0_to_4.1']['event_rel_sort'] = 'ReasonUpgrader_41_EventImageRelSort';

class ReasonUpgrader_41_EventImageRelSort implements reasonUpgraderInterface
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
		return 'Add Relationship Sorting on Event Images';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		return 'This upgrader allows images attached to events to be manually ordered.';
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if($this->_rel_is_sortable())
			return 'This script has already run';
		else
			return 'This script will make the event_to_image relationship sortable';
	}
        /**
         * Run the upgrader
         * @return string HTML report
         */
	public function run()
	{
		if($this->_rel_is_sortable())
			return 'This script has already run';
                else
		{
			if(update_allowable_relationship(relationship_id_of('event_to_image'),array('is_sortable'=>'yes')))
				echo 'Made event-to-image relationship sortable.';
			else
				echo 'An unknown problem prevented making the event-to-image relationship sortable. You can attempt to do this manually by going to the Allowable Relationship Manager and editing the is_sortable field of the event_to_image allowable relationship.';
		}
	}
	protected function _rel_is_sortable()
	{
		if($info = reason_get_allowable_relationship_info( relationship_id_of('event_to_image') ) )
		{
			return $info['is_sortable'] == 'yes' ? true : false;
		}
		trigger_error('unable to find event_to_image allowable relationship');
	}

}


?>
