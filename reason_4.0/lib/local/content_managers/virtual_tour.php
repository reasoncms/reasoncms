<?php
/**
 * @package reason
 * @subpackage content_managers
 */

/**
 * Include dependencies
 */
	reason_include_once('classes/url_manager.php');
	reason_include_once('classes/virtual_tour_upload.php');
	reason_include_once('content_managers/default.php3');
	//reason_include_once('function_libraries/asset_functions.php');
	reason_include_once('classes/default_access.php');
	
	require_once CARL_UTIL_INC.'basic/mime_types.php';
	require_once CARL_UTIL_INC.'basic/misc.php';

/**
 * Register content manager with Reason
 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'LutherVirtualTour';

/**
 * A content manager for PTgui virtual tours (http://www.ptgui.com)
 */
	
	class LutherVirtualTour extends ContentManager
	{
		var $form_enctype = 'multipart/form-data';
		
		function alter_data()
		{
			$authenticator = array("reason_username_has_access_to_site",
					$this->get_value("site_id"));
			
			$existing_asset_type = $this->get_value("file_type");
			$full_asset_path = ($this->get_value('file_type')) ? ASSET_PATH.$this->_id.'.'.$this->get_value('file_type') : false;
			$params = array('authenticator' => $authenticator,
					'max_file_size' => $this->get_actual_max_upload_size(),
					'head_items' => &$this->head_items,
					'acceptable_extensions' => array("zip"),
					'acceptable_types' => array("application/zip"),
					'file_display_size' => $this->get_value('file_size') * 1024,
					'file_display_name' => $this->get_value('file_name'));
			if (!empty($existing_asset_type)) {
				$params = array_merge($params, array(
						'existing_entity' => $this->_id,
						'allow_upload_on_edit' => true));
			}
			
			// default initial placement of viewer
			$pan = 0;
			$tilt = 0;
			$fov = 90;
			
			// pan, tilt, and fov is stored in keywords field
			$ka = explode(',', $this->get_value('keywords'));
			foreach($ka as $key => $value)
			{
				$ka[$key] = trim($value);
				if (preg_match("/pan:?\s?(\-?\d+)/", $value, $matches))
				{
					$pan = $matches[1];
					unset($ka[$key]);		
				}
				else if (preg_match("/tilt:?\s?(\d+)/", $value, $matches))
				{
					$tilt = $matches[1];
					unset($ka[$key]);
				}
				if (preg_match("/fov:?\s?(\d+)/", $value, $matches))
				{
					$fov = $matches[1];
					unset($ka[$key]);
				}
			}
			$this->set_value('keywords', implode(", ", $ka));
				
			$this->add_element('virtual_tour', 'VirtualTourUpload', $params);
			$asset = $this->get_element('virtual_tour');
				
			$this->set_comments( 'description', form_comment('A description of the virtual tour.') );
			$this->set_comments ( 'virtual_tour', form_comment('Your zip file name may be modified if it is has already been taken, or includes spaces or unusual characters.'));
				
			$this->add_required( 'description' );
			
			$this->set_display_name( 'virtual_tour', 'File' );
			
			$this->change_element_type( 'file_size', 'hidden' );
			$this->change_element_type( 'file_name', 'text' );
			$this->change_element_type( 'file_type', 'hidden' );
			$this->change_element_type( 'mime_type', 'hidden' );
			
			$this->add_restriction_selector();

			$this->add_element( 'pan', 'text', array('size' => 4));
			$this->set_comments( 'pan', form_comment('Alter the initial camera pan (default = 0)'));
			$this->set_value('pan', $pan);

			$this->add_element( 'tilt', 'text', array('size' => 4));
			$this->set_comments( 'tilt', form_comment('Alter the initial camera tilt (default = 0)'));
			$this->set_value('tilt', $tilt);
			
			$this->add_element( 'fov', 'text', array('size' => 4));
			$this->set_comments( 'fov', form_comment('Alter the initial camera field of view (default = 90)'));
			$this->set_value('fov', $fov);
		
		}
		
		/**
		 * Alter and/or hide the file name field depending upon the state of the asset
		 *
		 * - if just received, find a safe name, populate the field, and hide it - after the redirect
		 * - if state is "existing" - don't do anything - the field remains editable
		 * - if state is "pending" or "ready" (new) hide the field
		 *
		 */
		function pre_error_check_actions()
		{
			$asset = $this->get_element('virtual_tour');
				
			// on an upload, set the file_name field to a safe value
			$filename = ($asset->state == 'received') ? $asset->file["name"] : $this->get_value('file_name');
			if ($filename)
			{
				$filename = $this->get_safer_filename($filename);
				$filename = sanitize_filename_for_web_hosting($filename);
				$filename = reason_get_unique_asset_filename($filename, $this->get_value("site_id"), $this->_id);
				$this->set_value('file_name', $filename);
			}
				
			// hide the file_name field unless it is an existing valid asset
			if ($asset->state != 'existing') $this->change_element_type('file_name', 'hidden');
			else $this->add_required('file_name');
		}
		
		/**
		 * @access private
		 */
		function _get_filename_parts($filename)
		{
			$parts = explode('.', $filename);
		
			if (count($parts) <= 1) {
				return array(basename($filename), '');
			} else {
				$extension = array_pop($parts);
				return array(basename($filename, ".$extension"), $extension);
			}
		}
		
		function get_safer_filename ($filename)
		// returns a "safe" filename with .txt added to unsafe extensions - nwhite 12/12/05
		{
			$unsafe_to_safer = array(
					'py' => 'py.txt',
					'php' => 'php.txt',
					'asp' => 'asp.txt',
					'aspx' => 'aspx.txt',
					'pl' => 'pl.txt',
					'shtml' => 'shtml.txt',
					'cfm' => 'cfm.txt',
					'woa' => 'woa.txt',
					'php3' => 'php3.txt',
					'jsp' => 'jsp.txt',
					'js' => 'js.txt',
					'exe' => 'exe.txt',
					'cgi' => 'cgi.txt',
					'vb' => 'vb.txt',
					'bat' => 'bat.txt',
			);
			list($filename, $fext) = $this->_get_filename_parts($filename);
			if(!empty($unsafe_to_safer[$fext]))
				$fext = $unsafe_to_safer[$fext];
			if (!empty($fext)) $filename .= '.' . $fext;
			return $filename;
		}
		
		function run_error_checks()
		{
			// check to see if an asset has been uploaded
			$asset = $this->get_element( 'virtual_tour' );
			if( $asset->state == 'ready' )
			{
				$this->set_error( 'virtual_tour', 'You must upload a file' );
			}
				
		}
		
		function post_error_check_actions()
		{
			// display the URL of the document or a warning if no doc dir is set up.
				
			$asset = $this->get_element( 'virtual_tour' );
			$site = new entity( $this->get_value( 'site_id' ) );
			if( $this->get_value( 'file_name' ) )
			{
				if( $this->has_error( 'virtual_tour' ) OR $this->has_error( 'file_name' ) )
					$text = 'Document URL: Cannot be determined until errors are resolved.';
				$url = 'http://'.$_SERVER['HTTP_HOST'].$site->get_value( 'base_url' ).MINISITE_ASSETS_DIRECTORY_NAME.'/'.$this->get_value( 'file_name' );
				$text = 'Document URL: ';
				if( $asset->state == 'existing' && $this->get_value('state') == 'Live' && !$this->_has_errors())
				{
					$text .= '<a href="'.$url.'" target="_new">'.$url.'</a>';
				}
				elseif ($this->_has_errors())
				{
					$text .= $url.' (link may not work until errors are resolved)';
				}
				else
				{
					$text .= $url.' (will be live once saved)';
				}
				$this->add_element( 'doc_url', 'comment', array( 'text' => $text  ) );
			}
		}
		
		function process()
		{
			$document = $this->get_element( 'virtual_tour' );
			
			// see if document was uploaded successfully
			if( ($document->state == 'received' OR $document->state == 'pending') AND file_exists( $document->tmp_full_path ) )
			{
				$path_parts = pathinfo($document->tmp_full_path);
				$suffix = (!empty($path_parts['extension'])) ? $path_parts['extension'] : '';
			
				// if there is no extension/suffix, try to guess based on the MIME type of the file
				if( empty( $suffix ) )
				{
					$type_to_suffix = array(
							'application/zip' => 'zip',
					);
					 
					$type = $document->get_mime_type();
					if ($type) {
						$m = array();
						if (preg_match('#^([\w-.]+/[\w-.]+)#', $type, $m)) {
							// strip off any ;charset= crap
							$type = $m[1];
							if (!empty($type_to_suffix[$type]))
								$suffix = $type_to_suffix[$type];
						}
					}
				}
				if(empty($suffix))
				{
					$suffix = 'unk';
					trigger_error('uploaded virtual tour at '.$document->tmp_full_path.' had an indeterminate file extension ... assigned to .unk');
				}
			
				// set up values for insertion into the DB
				// set file size
				$this->set_value('file_size', round(filesize( $document->tmp_full_path ) / 1024) );
			
				// set mime type
				$this->set_value('mime_type', get_mime_type($document->tmp_full_path, 'application/octet-stream'));
			
				// set file type
				$this->set_value('file_type', $suffix );
			
				$asset_dest = ASSET_PATH.$this->_id.'.'.$suffix;
				// move the file - if windows and the destination exists, unlink it first.
				if (server_is_windows() && file_exists($asset_dest))
				{
					unlink($asset_dest);
				}
				rename ($document->tmp_full_path, $asset_dest );
				
				$this->delete_previous_tour(REASON_PATH . 'data/images/virtual_tours/' . $this->_id);
			}
			
			// make sure to ignore the 'virtual_tour' field
			$this->_process_ignore[] = 'virtual_tour';
			
			// put pan, tilt, and fov info put into keyword
			$kw = $this->get_value("keywords");
			if (preg_match("/^\-?\d+$/", $this->get_value("pan"), $matches) && $matches[0] != "0")
			{
				if ($kw != "")
				{
					$kw = $kw . ', ';
				}
				$kw = $kw . 'pan ' . $this->get_value("pan");
			}
			if (preg_match("/^\d+$/", $this->get_value("tilt"), $matches) && $matches[0] != "0")
			{
				if ($kw != "")
				{
					$kw = $kw . ', ';
				}
				$kw = $kw . 'tilt ' . $this->get_value("tilt");
			}
			if (preg_match("/^\d+$/", $this->get_value("fov"), $matches) && $matches[0] != "90")
			{
				if ($kw != "")
				{
					$kw = $kw . ', ';
				}
				$kw = $kw . 'fov ' . $this->get_value("fov");
			}
			$this->set_value('keywords', $kw);

			parent::process();
		}
		
		function get_actual_max_upload_size()
		{
			return reason_get_asset_max_upload_size();
		}
		
		protected function add_restriction_selector()
		{
			$this->add_relationship_element('limit_access', id_of('group_type'), relationship_id_of('asset_access_permissions_to_group'), 'right', 'select', true, $sort = 'entity.name ASC');
			if($this->is_new_entity() && $this->_is_first_time() && !$this->get_value('name') && !$this->get_value('limit_access') && ($group_id = $this->get_default_restricted_group_id()))
			{
				$this->set_value('limit_access', $group_id);
			}
		}
		
		protected function get_default_restricted_group_id()
		{
			$da = reason_get_default_access();
			return $da->get($this->get_value( 'site_id' ), $this->get_value( 'type_id' ), 'asset_access_permissions_to_group');
		}
		
		public static function delete_previous_tour($dirPath)
		{
			if (is_dir($dirPath))
			{
				if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
				{
					$dirPath .= '/';
				}
				$files = glob($dirPath . '*', GLOB_MARK);
				foreach ($files as $file)
				{
					if (is_dir($file))
					{
						self::deleteDir($file);
					}
					else 
					{
						unlink($file);
					}
				}
				$dotFiles = glob($dirPath . '.*', GLOB_MARK);
				foreach ($dotFiles as $dotFile)
				{
					if (!is_dir($dotFile))
					{
						unlink($dotFile);
					}
				}
				rmdir($dirPath);
			}
		}
		
	}
?>
