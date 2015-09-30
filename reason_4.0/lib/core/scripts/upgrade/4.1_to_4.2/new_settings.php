<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.1_to_4.2']['new_settings'] = 'ReasonUpgrader_41_NewSettings';
include_once('reason_header.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

class ReasonUpgrader_41_NewSettings implements reasonUpgraderInfoInterface
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
		$exists = (defined('REASON_MAINTENANCE_MODE'));
		$str = '<h3>New Setting - REASON_MAINTENANCE_MODE</h3>';
		$str .= '<p>Reason 4.2 defines a new settings called REASON_MAINTENANCE_MODE. ';
		$str .= 'This setting is designed to stop people without the priviliges to administer Reason from performing actions that insert or update the database. ';
		$str .= 'Currently, inline editing and publication posting and commenting check this settings. ';
		$str .= 'The setting defaults, obviously, to false, and is useful when doing things like database indexing or other processes where you want to minimize or avoid database modification.</p>';
		if (!$exists)
		{
			$str .= '<p><strong>You do not yet have this setting defined, please copy and paste the following into reason_settings.php</strong></p>';
			$str .= '<textarea rows="9" cols="110">';
$str .= <<<EOD
/**
 * REASON_MAINTENANCE_MODE
 * Set this to true during database maintenance or upgrades.
 *
 * When REASON_MAINTENANCE_MODE is true, users without the db_maintenance privilege
 * will be unable to access the administrative interface, and modules should restrict
 * database writes / updates.
 */
 define( 'REASON_MAINTENANCE_MODE', false );
EOD;
			$str .= '</textarea>';
		}
		else
		{
			$str .= '<p><strong>Your Reason installation already has this setting in place.</strong></p>';
		}
		return $str;
	}
}
?>