<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['indexing_info'] = 'ReasonUpgrader_41_IndexingInfo';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_41_IndexingInfo implements reasonUpgraderInfoInterface 
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
     * Return information about new settings
     * @return string HTML report
     */
	public function run()
	{
		$str = '<h3>Indexing Information</h3>';
		$str .= '<p>Reason 4.2 includes changes which eliminate some and simplify most queries. You likely want to put in some multicolumn indexes on the relationship table (entity_a, type and entity_b, type) and entity table (type, state) in order to take full advantage of the changes.</p>';
		$str .= '<p>For details on how and why to do this, read the post <a href="https://apps.carleton.edu/opensource/reason/developers/changes/?story_id=836757">Reason 4.2 Performance & Database Indexing Outline</a> on the Reason Developer Notebook Blog.</p>'; 
		return $str;
	}
}
?>