<?php
/**
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');
reason_include_once('function_libraries/image_tools.php');

$GLOBALS['_reason_upgraders']['4.0_to_4.1']['thumbnail_standardize'] = 'ReasonUpgrader_41_ThumbnailStandardize';

class ReasonUpgrader_41_ThumbnailStandardize implements reasonUpgraderInterface
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
		return 'Create main image files for those images which only have a thumbnail';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		return 'This upgrader creates main image files for those images which only have a thumbnail. Please note that
			it will probably to be run multiple times in order to process all images.';
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		$all_have_main_images = false;
		if($all_have_main_images)
			return 'This script has already run (says test)';
		else
			return 'This script will create main image files for those images which only have a thumbnail';
	}
        /**
         * Run the upgrader
         *
         *
         * @return string HTML report
         */
	public function run()
	{
		/* 
			Check for progress in script -- a starting index of -1 implies that all photos have 
			already been processed during this session
		*/
		if(isset($_SESSION['reason_thumbnail_standardize_script']['last_end_index']))
		{
			$start_index = $_SESSION['reason_thumbnail_standardize_script']['last_end_index'];
		}
		else
		{
			$start_index = 0;
		}
		if($start_index == -1)
			return 'This script has already processed all images during this session!' . '<br />';
		else
		{
			$start_time = time();
			$php_max_time = ini_get('max_execution_time');
			if($php_max_time == 0)
			{
				$time_limit = 15;
			}
			else
			{
				$time_limit = min($php_max_time/2, 15);
			}
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$all_image_ids = $es->get_ids();
			$ids_with_only_tb = array();
			$total_num_images = sizeof($all_image_ids);
			
			/* Start processing images where last time left off, or start at the beginning */
			
			/* Runs for $time_limit seconds at a time, continues process when script is run again */
			for($i = $start_index; $i < $total_num_images; $i++)
			{
				$main_image_path = reason_get_image_path($all_image_ids[$i]);
				/* If no main image exists, copy the thumbnail to be the main image */
				if(!file_exists($main_image_path))
				{
					$thumbnail_path = reason_get_image_path($all_image_ids[$i], 'thumbnail');
					if(file_exists($thumbnail_path))
					{
						$ids_with_only_tb[] = $all_image_ids[$i];
						
						if(!copy($thumbnail_path, $main_image_path))
						{
							echo 'failed to copy image of id ' . $all_image_ids[$i];
						}
					}
				}
				if(time() - $start_time > $time_limit)
				{
					break;
				}
			}
			
			/* If all images have been processed, reset the last index*/
			if($i >= $total_num_images)
			{
				$_SESSION['reason_thumbnail_standardize_script']['last_end_index'] = -1;
				$_SESSION['reason_thumbnail_standardize_script']['run_again'] = false;
			}
			else
			{
				$_SESSION['reason_thumbnail_standardize_script']['last_end_index'] = $i;
				$_SESSION['reason_thumbnail_standardize_script']['run_again'] = true;
			
			}
			/* Give user some info on how many more times script should be run */
			$percent_remaining = substr((($total_num_images - $i) / $total_num_images) * 100, 0, 4);
			echo '<br /> There are ' . $total_num_images . ' total images to process';
			echo '<br /> This execution of the script started at the ' . $start_index .'th photo';
			echo '<br /> It processed a total of ' .($i - $start_index) . ' images, and created new main
			images from ' . sizeof($ids_with_only_tb) . ' thumbnails.';
			echo '<br /> A total of ' . ($total_num_images - $i) . ', (' . $percent_remaining . '%)
			images still need to be processed <br />';
		}
	}
	public function standalone()
	{
		return true;
	}
	public function run_again()
	{
		return $_SESSION['reason_thumbnail_standardize_script']['run_again'];
	}

}
?>
