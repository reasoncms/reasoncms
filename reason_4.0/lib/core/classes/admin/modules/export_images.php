<?php
/**
 * @package reason
 * @subpackage admin
 */

include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php' );
reason_include_once('function_libraries/image_tools.php');
/**
 * Include the default module
 */
reason_include_once('classes/admin/modules/default.php');

/**
 * Exports reason images in a zip file
 */
class ReasonExportImagesModule extends DefaultModule
{
	protected $errors = array();
	function init()
	{
		$this->admin_page->title = 'Export Images';
	}

	function run()
	{
		if(empty($this->admin_page->request['site_id']))
		{
			echo 'Please select a site';
			return;
		}
		if(!class_exists('ZipArchive'))
		{
			echo 'This module requires the Zip extension. Please enable PHP Zip extension and try again.';
			return;
		}

		$d = $this->get_form();
		$d->run();

		if($d->successfully_submitted())
		{
			$timeout = isset( $_GET['timeout'] ) && intval( $_GET['timeout'] ) > 600 ? intval( $_GET['timeout'] ) : 600;
			set_time_limit( $timeout ); // give it at least 10 minutes to complete

			$site = new entity($this->admin_page->request['site_id']);

			$zip = new ZipArchive();
			$zip_filename = 'image-export-'.$site->get_value('unique_name').'-'.date('Y-m-d-h-i-s').'--'.uniqid().'.zip';
			$zip_filepath = REASON_TEMP_DIR.$zip_filename;

			if ($zip->open($zip_filepath, ZipArchive::CREATE) !== TRUE)
			{
				echo 'Unable to create zip file. Please have an administrator check filesystem permissions.';
				return;
			}

			$images = $this->get_images($d);

			if(empty($images))
			{
				echo 'No images to export';
				return;
			}

			$paths = $this->get_paths_for_images($images);


			if(empty($paths))
			{
				echo 'No images on filesystem to export';
				return;
			}

			foreach($paths as $image_id => $image_info)
			{
				$zip->addFile($image_info['path'], $image_info['filename']);
			}

			if($zip->close())
			{
				$file_handle = fopen($zip_filepath, "rb");
				$file_size = filesize($zip_filepath);
				if(false === $file_handle || 0 == $file_size || false === $file_size )
				{
					echo 'Zip file creation didn\'t work. Please contact an administrator to troubleshoot.';
					return;
				}

				while(ob_get_level() > 0)
				{
					ob_end_clean();
				}

				header('Pragma: public');
				header('Cache-Control: max-age=0'); //added to squash weird IE 6 bug where pdfs say that the file is not found
				header('Content-Type: applications/zip');
				header('Content-Disposition: attachment; filename="'.$zip_filename.'"');
				header('Content-Length: '.$file_size);
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('X-Robots-Tag: noindex');

				fpassthru($file_handle);

				fclose($file_handle);

				if(!unlink($zip_filepath))
				{
					trigger_error('Unable to delete temporary zip file at '.$zip_filepath);
				}
				exit();
			}
			else
			{
				echo 'Zip file creation didn\'t work. Please contact an administrator to troubleshoot.';
				return;
			}
		}
	}
	function get_statuses()
	{
		return array('Live'=>'Live','Pending'=>'Pending','Deleted'=>'Deleted');
	}
	function get_form()
	{
		$d = new Disco();
		$d->set_box_class('StackedBox');
		$d->add_element('status',
			'radio_no_sort',
			array(
				'options' => $this->get_statuses(),
			)
		);
		$d->add_required('status');
		$d->set_value('status', 'Live');
		$d->set_actions(array('run'=>'Get Images'));
		return $d;
	}
	function get_images($disco)
	{
		$es = new entity_selector($this->admin_page->request['site_id']);
		$es->add_type(id_of('image'));
		$status = in_array($disco->get_value('status'), $this->get_statuses()) ? $disco->get_value('status') : 'Live';
		$images = $es->run_one('', $status);
		return $images;
	}
	function get_paths_for_images($images)
	{
		$paths = array();

		foreach($images as $image)
		{
			$type = 'original';
			$path = reason_get_image_path($image, $type);
			if(empty($path) || !file_exists($path))
			{
				$type = 'standard';
				$path = reason_get_image_path($image, $type );
			}
			if(empty($path) || !file_exists($path))
			{
				$type = 'thumbnail';
				$path = reason_get_image_path($image, 'thumbnail');
			}
			if(!empty($path))
			{
				if(file_exists($path))
				{
					$paths[$image->id()] = array(
						'path' => $path,
						'filename' => reason_get_image_filename($image, $type),
						'image' => $image,
					);
				}
				else
				{
					$this->errors[] = 'File not found for image id ' . $image->id() . ' at ' . $path;
				}
			}
			else
			{
				$this->errors[] = 'No image path found for image id '.$image->id();
			}
		}
		return $paths;
	}
}
