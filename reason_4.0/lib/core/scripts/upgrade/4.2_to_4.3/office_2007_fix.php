<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.2_to_4.3']['office_2007_fix'] = 'ReasonUpgrader_43_Office2007MimeFix';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('classes/field_to_entity_table_class.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * This script changes the mime type of certain assets that contain MS Office 2007 files.
 *
 * Specifically, we look for files with these extensions:
 *
 * - pptx
 * - docx
 * - xlsx
 * 
 * AND one of these mime types:
 *
 * - application/zip
 * - application/x-zip
 * - application/vnd.openxmlformats-o
 *
 * We replace the mime type with this - which seems to make office 2007 documents work correctly:
 *
 * - application/vnd.openxmlformats
 *
 * We update the live entity. We do it transparently, maintaining last_edited_by and last_modified.
 *
 * We do all this because installs that use php's finfo_file may have been incorrectly setting the mime types for these files for some time.
 * While in most browsers things probably worked okay, there are some instances where browsers do not properly download and open the office
 * 2007 files with mime types that look like .zip files.
 *
 * While we just look for the three mime types mentioned above, (and I'm not sure how we got the application/vnd.openxmlformats-o ones in our database),
 * others might find they have other incorrect mime types for Office 2007 extensions, and may need to modify the class variables to tweak 
 * other extension, mime type combinations as needed.
 *
 * @author Nathan White
 */
class ReasonUpgrader_43_Office2007MimeFix implements reasonUpgraderInterface
{
	protected $user_id;
	protected $seek_mime_types = array('application/zip','application/x-zip','application/vnd.openxmlformats-o');
	protected $seek_file_extensions = array('pptx','docx','xlsx');
	protected $new_mime_type = 'application/vnd.openxmlformats';
	
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
		return 'Fix mime type on office 2007 generated assets';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade updates the stored mime types for office 2007 documents if any incorrect types are found.</p>";
		return $str;
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if ($assets_needing_fixing = $this->get_assets_needing_fixing())
		{
			$count = count(array_keys($assets_needing_fixing));
			return '<p>Would fix ' . $count . ' assets needing fixing.</p>';
			
		}
		else return '<p>There are no assets needing fixing.</p>';
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if ($assets_needing_fixing = $this->get_assets_needing_fixing())
		{
			$count = count(array_keys($assets_needing_fixing));
			foreach ($assets_needing_fixing as $k => $asset)
			{
				$new_values['last_edited_by'] = $asset->get_value('last_edited_by');
				$new_values['last_modified'] = $asset->get_value('last_modified');
				$new_values['mime_type'] = $this->new_mime_type;
				reason_update_entity($k, $new_values['last_edited_by'], $new_values, false);
			}
			return '<p>Fixed ' . $count . ' assets needing fixing.</p>';
		}
		else return '<p>There are no assets needing fixing.</p>';
	}
	
	/**
	 * Find all files with one of these extensions .pptx, .docx, or xlsx where mime type is not application/vnd.openxmlformats
	 *
	 * @todo implement me
	 */
	protected function get_assets_needing_fixing()
	{
		$es = new entity_selector();
		$es->limit_tables(array('entity','asset'));
		$es->limit_fields(array('mime_type', 'file_type', 'last_edited_by', 'last_modified'));
		$es->add_type(id_of('asset'));
		$es->add_relation('`file_type` IN ("'.implode('","', $this->seek_file_extensions).'")');
		$es->add_relation('`mime_type` IN ("'.implode('","', $this->seek_mime_types).'")');
		$result = $es->run_one();
		return (!empty($result)) ? $result : FALSE;
	}
}
?>