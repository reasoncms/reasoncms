<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once(DISCO_INC.'disco.php');
	reason_include_once('classes/plasmature/upload.php');
	reason_include_once('classes/kaltura_shim.php');
	reason_include_once('classes/csv.php');
	/**
	 * 
	 */
	class KalturaImportModule extends DefaultModule
	{
		protected $metadata;
		function KalturaImportModule( &$page )
		{
			$this->admin_page =& $page;
		}
		function init()
		{
			$this->admin_page->title = 'Batch Import Media';
		}
		function get_recognized_extensions()
		{
			return KalturaShim::get_recognized_extensions();
		}
		function get_default_values()
		{
			return array(
						'no_share' => '0',
						'email_notification' => '0',
						'show_embed' => '1',
						'show_download' => '1',
						'description' => '',
						'keywords' => '',
						'categories' => '',
						'show_hide' => 'show',
					);
		}
		function get_acceptable_values()
		{
			return array(
						'av_type' => array('Audio','Video'),
						'no_share' => array('0','1'),
						'email_notification' => array('0','1'),
						'show_embed' => array('0','1'),
						'show_download' => array('0','1'),
					);
		}
		function run()
		{
			if(!KalturaShim::kaltura_enabled())
			{
				echo '<p>Sorry; in order to use this module your instance of Reason must be integrated with Kaltura</p>'."\n";
				return;
			}
			if(empty($this->admin_page->site_id))
			{
				echo '<p>Please pick a site first to use this module.</p>'."\n";
				return;
			}
			
			// $this->admin_page->user_id
			// $this->admin_page->site_id
			$d = new Disco;
			$d->form_enctype = 'multipart/form-data';
			$d->add_callback(array($this,'get_instructions'),'pre_show_form');
			$d->add_element('zip_file','ReasonUpload',array('acceptable_extensions'=>array('zip')));
			$d->add_required('zip_file');
			$d->add_callback(array($this,'error_check'),'run_error_checks');
			$d->add_callback(array($this,'process_form'),'process');
			$d->run();
		}
		
		function get_instructions($d)
		{
			$ret = '<p>Package your media files in a flat zip archive, with a CSV spreadsheet in the archive named metadata.csv. The first row of the CSV file should contain the following column names (* = required for processing).</p>'."\n";
			$ret .= '<ul>
				<li><strong>filename*</strong> (Must be one of the following file types: '.implode(', ',$this->get_recognized_extensions()).')</li>
				<li><strong>name*</strong></li>
				<li><strong>av_type*</strong> (must be either "Audio" or "Video"</li>
				<li><strong>no_share</strong> (0 to share, 1 to not share; default 0)</li>
				<li><strong>author</strong></li>
				<li><strong>description</strong></li>
				<li><strong>keywords</strong></li>
				<li><strong>datetime</strong> (A date/time value indicating when the work was created)</li>
				<li><strong>transcript</strong></li>
				<li><strong>transcript_status</strong></li>
				<li><strong>rights_statement</strong></li>
				<li><strong>email_notification</strong> (1 to send notification email when processing is complete; default 0)</li>
				<li><strong>show_embed</strong> (1 to offer embedding in the front-end interface; default 1)</li>
				<li><strong>show_download</strong> (1 to offer download of the file in the front-end interface; default 1</li>
			</ul>'."\n";
			$ret .= '<p>If there are any required values not present, no processing of the entire import will occur. (This import will attempt to provide a helpful summary of the issues.)</p>'."\n";
			$ret .= '<p>If any of the non-required columns are not present or contain no value, no value (or the default value indicated) will be used.</p>'."\n";
			return $ret;
		}
		function error_check($d)
		{
			if ( !$d->has_error('zip_file') )
			{
				$file_uploaded = false;
				
				$upload_element = $d->get_element('zip_file');
				
				if ( !empty($upload_element) )
				{
					$file = $d->get_element( 'zip_file' );
					if( ($file->state == 'received' OR $file->state == 'pending') AND file_exists( $file->tmp_full_path ) )
					{
						$file_path = $file->tmp_full_path;
						$zip = new ZipArchive;
						$res = $zip->open($file_path);
						if ($res === TRUE)
						{
							$this->temp_location = REASON_TEMP_DIR.uniqid().'/';
							mkdir($this->temp_location);
							$zip->extractTo($this->temp_location);
							$zip->close();
							$errors = $this->sanity_check($this->temp_location);
							foreach($errors as $error)
							{
								$d->set_error('zip_file',$error);
							}
						}
						else
						{
							$d->set_error('zip_file','Unable to unzip uploaded zip file.');
						}
					}
				}
			}
		}
		function sanity_check($dir)
		{
			$errors = array();
			$dir = $this->get_data_directory($dir);
			if(!file_exists($dir))
			{
				$errors[] = 'No metadata.csv file in zip';
			}
			else
			{
				$metadata = $this->get_metadata_array($dir);
				if(!empty($metadata))
				{
					$first = reset($metadata);
					if(!isset($first['filename']))
					{
						$errors[] = 'Required filename column not present in CSV';
					}
					if(!isset($first['name']))
					{
						$errors[] = 'Required name column not present in CSV';
					}
					if(!isset($first['av_type']))
					{
						$errors[] = 'Required av_type column not present in CSV';
					}
					if(empty($errors))
					{
						$ok_values = $this->get_acceptable_values();
						foreach($metadata as $line_number => $item)
						{
							if(empty($item['filename']))
							{
								$errors[] = 'At least one item does not have a filename value';
							}
							if(empty($item['name']))
							{
								$errors[] = 'At least one item does not have a filename value';
							}
							if(empty($item['av_type']))
							{
								$errors[] = 'At least one item does not have a av_type value';
							}
							if(!empty($errors))
								break;
							foreach($ok_values as $k=>$v)
							{
								if(isset($item[$k]) && !in_array($item[$k],$v,true))
									$errors[] = 'At least one item\'s '.$k.' value is neither '.implode(' nor ',$v).'. (First problem: '.htmlspecialchars($item['filename']).', "'.htmlspecialchars($item[$k]).'")';
							}
							if(isset($item['datetime']) && strlen($item['datetime']) > 0 && strtotime($item['datetime']) === false)
							{
								$errors[] = 'At least one item in the csv does not have a valid datetime value (First not  found: '.htmlspecialchars($item['filename']).', "'.htmlspecialchars($item['datetime']).'")';
							}
							$metadata[$line_number]['file_location'] = $dir.$this->safe_filename($item['filename']);
							if(!file_exists($metadata[$line_number]['file_location']))
							{
								$errors[] = 'At least one item in the csv does not appear to have a media file present (First not  found: '.htmlspecialchars($item['filename']).')';
							}
							if($extension = $this->get_extension($metadata[$line_number]['file_location']))
							{
								if(!in_array($extension, $this->get_recognized_extensions()))
									$errors[] =  'At least one filename in the csv does not have a recognized extension ('.htmlspecialchars($item['filename']).')';
							}
							else
							{
								$errors[] = 'At least one filename in the csv does not appear to have an extension ('.htmlspecialchars($item['filename']).')';
							}
							if(!empty($errors))
								break;
						}
						if(empty($errors))
						{
							$this->metadata = $metadata;
						}
					}
				}
				else
				{
					$errors[] = 'Badly formatted CSV file. All values must be quoted and comma-separated.';
				}
			}
			return $errors;
		}
		function get_extension($file_path)
		{
			return strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
		}
		function safe_filename($filename)
		{
			return str_replace('../','someone_is_trying_to_spelunk_our_filesystem',$filename);
		}
		function get_media_file_list($dir)
		{
			$list = array();
			if ($handle = opendir($this->temp_location))
			{
				while (false !== ($entry = readdir($handle)))
				{
					if(strpos($entry, '.') !== 0 && !is_dir($this->temp_location.'/'.$entry))
					{
						$list[] = $entry;
					}
				}
    			closedir($handle);
			}
			else
			{
				trigger_error('Unable to read media file directory '.$dir);
			}
			return $list;
		}
		function get_data_directory($dir)
		{
			if(file_exists($dir.'metadata.csv'))
				return $dir;
			
			if ($handle = opendir($dir))
			{
				while (false !== ($entry = readdir($handle)))
				{
					if(strpos($entry, '.') !== 0 && is_dir($dir.$entry))
					{
						if($file = $this->get_data_directory($dir.$entry.'/'))
							return $file;
					}
				}
    			closedir($handle);
			}
			else
			{
				trigger_error('Unable to read media file directory '.$dir);
			}
			
			return false;
		}
		function get_metadata_array($dir)
		{
			$original_line_ending_value = ini_get('auto_detect_line_endings');
			if(!$original_line_ending_value)
			{
				ini_set('auto_detect_line_endings',1);
			}
			$csv = new CSV($dir.'metadata.csv', true, ',', 10000, '"');
			$ret = $csv->csv_to_array();
			if(!$original_line_ending_value)
				ini_set('auto_detect_line_endings',$original_line_ending_value);
			return $ret;
		}
		function process_form($d)
		{
			$user = new entity( $this->admin_page->user_id );
			foreach($this->metadata as $info)
			{
				$prepped_info = $this->prep_item_info($info);
				$shim = new KalturaShim();
				$entry = $this->kaltura_import($prepped_info, $user, $shim);
				$id = $this->reason_import($prepped_info, $entry, $user);
			}
			echo 'Imported '.count($this->metadata).' item(s).';
		}
		function kaltura_import($info, $user, $shim)
		{
			if ($info['av_type'] == 'Video')
			{
				$entry = $shim->upload_video($info['tmp_file_path'], $info['name'], $info['description'], $this->comma_string_to_array($info['keywords']), $this->comma_string_to_array($info['categories']), $user->get_value('name'));
			}
			elseif($info['av_type'] == 'Audio')
			{				
				$entry = $shim->upload_audio($info['tmp_file_path'], $info['name'], $info['description'], $this->comma_string_to_array($info['keywords']), $this->comma_string_to_array($info['categories']), $user->get_value('name'), $info['tmp_file_name']);
			}
			return $entry;
		}
		function comma_string_to_array($str)
		{
			return array_map('trim',explode(',',$str));
		}
		function reason_import($info, $entry, $user)
		{
			$info['entry_id'] = $entry->id;
			return reason_create_entity( $this->admin_page->site_id, id_of('av'), $user->id(), $info['name'], $info);
			// todo: categories
		}
		function prep_item_info($info)
		{
			foreach($this->get_default_values() as $k=>$v)
			{
				if(!isset($info[$k]))
					$info[$k] = $v;
			}
			if(isset($info['datetime']) && strlen($info['datetime']) > 0)
			{
				$info['datetime'] = date('Y-m-d H:i:s', strtotime($info['datetime']));
			}
			$info['transcoding_status'] = 'converting';
			$info['integration_library'] = 'kaltura';
			$info['new'] = '0';
			if('show' == $info['show_hide'])
				$info['media_publication_datetime'] = date('Y-m-d H:i:s');
			$info['tmp_file_name'] = uniqid('imported-media-work-',true).'.'.$this->get_extension($info['file_location']);
			$info['tmp_file_path'] = substr_replace(WEB_PATH,"",-1).WEB_TEMP.$info['tmp_file_name'];
			rename($info['file_location'], $info['tmp_file_path']);
			// todo: tidy, strip tags
			return $info;
		}
	}
?>
