<?php
/**
 * @package reason
 * @subpackage scripts
 */

/**
 * Include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/image_tools.php');
reason_include_once('function_libraries/util.php');
reason_include_once('classes/field_to_entity_table_class.php');

$GLOBALS['_reason_upgraders']['4.1_to_4.2']['image_extension_clean_up'] = 'ReasonUpgrader_42_ImageExtensionCleanUp';

/**
 * Creates new fields for an image's 'thumbnail_image_type' and an image's 'original_image_type'.
 *
 * Sets these fields to match the image's 'image_type,' since in the past an image's main image, thumbnail 
 * image, and original image were all required to be the same type. Takes a long time to run, but should
 * be run immediately upon upgrade. 
 * 
 */
 
class ReasonUpgrader_42_ImageExtensionCleanUp implements reasonUpgraderInterface
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
		return 'Give thumbnails and original images their own types';
	}
	/**
	 * Get a description of what this upgrade script will do
	 * 
	 * @return string HTML description
	 */
	public function description()
	{
		return '<p>This script creates new fields in the image entity table for thumbnail type and main image type.
		As a result, users can upload thumbnails that are of a different image type than the main image.
		This upgrade may take several minutes to run and require a person to monitor it. However, it is important
		to run it as soon as possible once you have upgraded to this Reason version so that image filename generation
		will work properly.</p>';
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		$all_have_main_images = false;
		if($all_have_main_images)
			return '<p>This script has already run (says test).</p>';
		else
            return '<p>This script creates new fields in the image entity table for thumbnail type 
            (\'thumbnail_image_type\') and original image type (\'original_image_type\')
			As a result, users can upload thumbnails that are of a different image type than the main image.</p>';
	}
	/**
	 * Run the upgrader
	 *
	 * @return string HTML report
	 */
	public function run()
	{

        /* 
			Check for progress in script -- a starting index of -1 implies that all photos have 
			already been processed during this session
		*/
		
        $current_user = check_authentication();
        if (!reason_user_has_privs( get_user_id ( $current_user ), 'db_maintenance' ) )
        {
	        die('don\'t have the right permissions?');
        }
		if(isset($_SESSION['reason_image_type_script']['last_end_index']))
		{
			$start_index = $_SESSION['reason_image_type_script']['last_end_index'];
		}
		else
		{
			$start_index = 0;
		}
		if($start_index == -1)
		{
			echo '<p>This script has already processed all images during this session!' . '</p>';
		}
		else
        {
            $start_time = time();
            $php_max_time = ini_get('max_execution_time');
            if($php_max_time == 0)
            {
                $time_limit = 30;
            }
            else
            {
                $time_limit = min($php_max_time/2, 50);
            }
            $es = new entity_selector();
	        $es->add_type(id_of('image'));
		    $all_image_ids = $es->get_ids('', 'All', 'get_ids_error');
            
            /* 
                3 cases for which FILES actually exist
                1. only standard -- no need to update the image Entity table
                2. standard and thumbnail -- give these entities a thumbnail_image_type field
                3. standard, thumbnail, original (full-size) -- give these entites a thumbnail_image_type and
                original_image_type field
            */

            $ids_with_tn = array();
            $ids_with_tn_full = array();
            chdir(PHOTOSTOCK);
            $total_num_image_ids = count($all_image_ids);
            
            // make sure fields we are going to write to actually exist
            $this->add_unexisting_image_fields();
            
            for($index = $start_index; $index < $total_num_image_ids; $index++)
            {
                $image_id = $all_image_ids[$index];
                if( file_exists(reason_get_image_path($image_id, 'thumbnail')) )
                {
                	$image = new entity($image_id);
                	// Check if the entity already has a thumbnail_image_type to avoid clobbering an old value
                	// when the UPDATE SQL query is run
                	// Note: at this point, we have determined a thumbnail exists, so 'thumbnail_image_type'
                	// will not be null if the field already exists
                	if( !$image->has_value('thumbnail_image_type') )
					{
						if( file_exists(reason_get_image_path($image_id, 'original')) )
						{
							$ids_with_tn_full[] = $image_id;
						}
						else
						{
							$ids_with_tn[] = $image_id;
						}
                    }
                }
                
                if( time() - $start_time > $time_limit )
                    break;
            }
            $this->run_sql_queries($ids_with_tn, $ids_with_tn_full);
            
            // Session stuff
            if($index >= $total_num_image_ids)
			{
				$_SESSION['reason_image_type_script']['last_end_index'] = -1;
				$_SESSION['reason_image_type_script']['run_again'] = false;
			}
			else
			{
				$_SESSION['reason_image_type_script']['last_end_index'] = $index;
				$_SESSION['reason_image_type_script']['run_again'] = true;
			
			}
			echo '<p>Started at index ' . $start_index . ' out of ' . $total_num_image_ids . '<br />';
			echo 'Ended at index ' . $index . ' (' . round( ( $index / $total_num_image_ids ) * 100, 2) . '%)<br />';
			echo 'Processed ' . ($index - $start_index) . ' total images<br />';
			echo 'There were a total of ' . count($ids_with_tn) . ' ids with a main image and thumbnail <br />';
			echo 'There were a total of ' . count($ids_with_tn_full) . ' ids with a main image, thumbnail, and full</p>';
	    }
	}
	
	/**
	 * Constructs and executes 2 SQL queries to give values to thumbnail_image_type and/or original_image_type
	 * in the image entity table
	 * 
	 * @param array $with_tn all the ids of images that have just a main image and a thumbnail
	 * @param array $with_tn_full_size all the ids of images that have a main image, thumbnail, and original/full size
	 *
	 * @return true if queries successful, false if not
	 */
	function run_sql_queries($with_tn, $with_tn_full_size)
	{
	   $with_tn_set = $this->format_set($with_tn);
	   $with_tn_full_size_set = $this->format_set($with_tn_full_size);
	   
	   $with_tn_query = 'UPDATE `image` 
                        SET `thumbnail_image_type` = `image_type` 
                        WHERE `id` IN ' . $with_tn_set;
       $with_tn_full_size_query = 'UPDATE `image` 
                        SET `thumbnail_image_type` = `image_type`, `original_image_type` = `image_type` 
                        WHERE `id` IN ' . $with_tn_full_size_set;
        // connect and run the queries
        
        connectDB( REASON_DB );
        $tn_result = db_query($with_tn_query, 'Failed to update field for tn_type');
        $tn_and_original_result = db_query($with_tn_full_size_query, 'Failed to update field for tn_type and/or main_type');
        return $tn_result && $tn_and_original_result;
	}
	/**
	 * Formats an array of image ids to be used in the "in" clause of a SQL query
	 *
	 * @param array $set an array of image ids
	 *
	 * @return string formatted for SQL query, i.e. in form ('123', '12412', '4214')
	 */
	function format_set($set)
	{
	    return '("' . implode('","', $set) . '")';
	}
	
	/**
	 * Checks if image table has our new fields (thumbnail_image_type and original_image_type) --
	 * if not, then creates these fields in the image table
	 * 
	 * @return boolean whether or not new fields were actually created
	 */
	function add_unexisting_image_fields()
	{
	    $table_name = 'image';
	    $new_field_names = array('thumbnail_image_type' => array('db_type' => 'tinytext'),
	    						'original_image_type' => array('db_type' => 'tinytext'));
	    $existing_field_names = get_fields_by_content_table($table_name);
	
	    // check if our new fields exist yet
	    $new_fields_needed = false;
	    foreach(array_keys($new_field_names) as $field_name)
	    {
	    	if( !in_array($field_name, $existing_field_names) )
	    		$new_fields_needed = true;
	    }
	    if( $new_fields_needed )
	    {
			$field_updater = new FieldToEntityTable($table_name, $new_field_names);
			$field_updater->update_entity_table();
			$field_updater->report();
			return true;
	    }
		else
	    	return false;
	}
	
	
	public function standalone()
	{
		return true;
	}
	public function run_again()
	{
		return $_SESSION['reason_image_type_script']['run_again'];
	}
}
?>
