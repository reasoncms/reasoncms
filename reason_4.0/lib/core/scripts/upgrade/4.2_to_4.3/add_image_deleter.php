<?php
/**
 * @package reason
 * @subpackage scripts
 */
$GLOBALS['_reason_upgraders']['4.2_to_4.3']['add_image_deleter'] = 'ReasonUpgrader_43_AddImageDeleter';
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

/**
 * This script applies the image content deleter
 *
 * @author Matt Ryan
 */
class ReasonUpgrader_43_AddImageDeleter implements reasonUpgraderInterface
{
	protected $user_id;
	protected $image_type;
	
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
		return 'Add Image Content Deleter';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		$str = "<p>This upgrade adds the image content deleter, so that images are removed from the filesystem when the entity is expunged.</p>";
		return $str;
	}
	
	/**
	 * Do a test run of the upgrader
	 * @return string HTML report
	 */
	public function test()
	{
		if ($image_type = $this->get_image_type_entity())
		{
			if($image_type->get_value('custom_deleter'))
			{
				$ret = 'Your image type already has a deleter.';
				if('image.php' == $image_type->get_value('custom_deleter'))
					$ret .= ' This upgrader has already been run.';
				return '<p>'.$ret.'</p>';
			}
			else
			{
				return '<p>Would set the image type custom deleter to image.php</p>';
			}
			
		}
		else return '<p>Your instance doesn\'t have the image type. There is nothing to do.</p>';
	}
	
    /**
     * Run the upgrader
     * @return string HTML report
     */
	public function run()
	{
		if ($image_type = $this->get_image_type_entity())
		{
			if($image_type->get_value('custom_deleter'))
			{
				$ret = 'Your image type already has a deleter.';
				if('image.php' == $image_type->get_value('custom_deleter'))
					$ret .= ' This upgrader has already been run.';
				return '<p>'.$ret.'</p>';
			}
			else
			{
				reason_update_entity($image_type->id(), $this->_user_id, array('custom_deleter' => 'image.php'));
				return '<p>Added the image deleter.</p><p>NOTE: If you want Reason to move deleted image files instead of simply deleting them, specify a directory in a REASON_IMAGE_GRAVEYARD setting.</p>';
			}
			
		}
		else return '<p>Your instance doesn\'t have the image type. There is nothing to do.</p>';
	}
	
	/**
	 * @return object loki 1 entity if it exists
	 */
	protected function get_image_type_entity()
	{
		if(!isset($this->image_type))
		{
			$this->image_type = new entity(id_of('image'));
		}
		return $this->image_type;
	}
}
?>