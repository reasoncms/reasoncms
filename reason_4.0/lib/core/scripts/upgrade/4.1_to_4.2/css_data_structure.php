<?php

include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/admin_actions.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['css_data_structure'] = 'ReasonUpgrader_42_CSSDataStructure';

class ReasonUpgrader_42_CSSDataStructure implements reasonUpgraderInterface
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
		return 'Simplify the External CSS data structure';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script moves the External CSS contents of the meta and url tables into the external_css table for better database performance. It also assigns a content manager to the CSS type if the type does not have one.</p>';
	}
    /**
     * Do a test run of the upgrader
     * @return string HTML report
     */
	public function test()
	{
		$ret = '';
		
		$e = new entity(id_of('css'));
		if(!$e->get_value('custom_content_handler'))
			$ret .= '<p>The CSS type does not have a content manager. The CSS content manager will be added to the type.</p>'."\n";
		else
			$ret .= '<p>The CSS type has a content manager, so this script will not modify the CSS type.</p>'."\n";
		
		$tables = get_entity_tables_by_type( id_of('css'), false );
		$has_meta = in_array('meta',$tables);
		$has_url = in_array('url',$tables);
		if($has_meta || $has_url)
		{
			$ret = '';
			if(!$has_meta)
			{
				$ret .= '<p>The table move has been partially completed. The meta table has been moved, but not the url table. It will add the url field to the external_css table, move the content of that field from the url table, and delete the external css rows from the url table.</p>'."\n";
			}
			elseif(!$has_url)
			{
				$ret .= '<p>The table move has been partially completed. The url table has been moved, but not the meta table. It will add the description and keywords fields to the external_css table, move the content of those fields from the meta table, and delete the external css rows from the meta table.</p>'."\n";
			}
			else
			{
				$ret .= '<p>The table move has not yet been accomplished. It will add new url, author, and keywords fields to the external_css table, move the content of those fields from the url and meta tables, and delete the external css rows from the url and meta tables.</p>'."\n";
			}
			$ret .= '<h4>Notes</h4><ol><li>It is especially important to make a backup of your Reason instance before running this script. An unexpected server failure during this upgrade step will result in a corrupted Reason database.</li>
			<li>We recommend that you turn off your administrative interface (by changing DISABLE_REASON_ADMINISTRATIVE_INTERFACE to true in settings/reason_settings.php) before running this script.</li>
			<li>Any database queries for external css in local Reason modifications that reference the url or meta table directly will need to be updated to either not reference a table (preferred) or to reference the external_css table instead.</li></ol>';
		}
		else
		{
			$ret .= '<p>The table move has been run.</p>';
		}
		return $ret;
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{
		$ret = '';
		
		$e = new entity(id_of('css'));
		if(!$e->get_value('custom_content_handler'))
		{
			reason_update_entity( $e->id(), $this->user_id(), array('custom_content_handler'=>'css.php') );
			$ret .= '<p>The CSS type has been assigned a content manager.</p>'."\n";
		}
		
		
		$tables = get_entity_tables_by_type( id_of('css'), false );
		$has_meta = in_array('meta',$tables);
		$has_url = in_array('url',$tables);
		if($has_meta || $has_url)
		{
			set_time_limit(3600);
			if($has_meta)
			{
				$ret .= $this->_move_table_fields('meta');
			}
			if($has_url)
			{
				$ret .= $this->_move_table_fields('url');
			}
		}
		if(empty($ret))
		{
			$ret .= '<p>This upgrade has already been run. There is nothing to do.</p>';
		}
		return $ret;
	}
	
	protected function _move_table_fields($from)
	{
		$ret = '';
		set_time_limit(3600); 
		$success = reason_move_table_fields( id_of('css'), $from, 'external_css', $this->user_id() );
		if($success)
			$ret .= '<p>Successfully moved external css content in the '.$from.' table to the external_css table.</p>';
		else
		{
			$ret = '<p>Unable to move the content from the '.$from.' table to the external_css table. Please look in your PHP error logs for information about the cause of this error. This error may cause problems with Reason modules or queries; we recommend restoring your latest backup, identifying and fixing the cause on a testing server, and re-running this upgrade.</p>';
			$err = error_get_last();
			if(!empty($err))
				$ret .= '<p>Last error: "'.htmlspecialchars($err['message']).'"</p>';
		}
		return $ret;
	}
}
?>