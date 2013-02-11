<?php
/**
 * @package reason
 * @subpackage function_libraries
 */

require_once CARL_UTIL_INC."basic/filesystem.php";
reason_include_once('classes/url_manager.php');

 /**
  * Build standardized markup for a list of assets
  * @todo move away from this style of coding towards templates and models
  *
  * @param array $assets (each asset is an entity)
  * @param object $site (the site entity that the assets are being displayed within)
  * @param array $fields (the fields to display)
  * @param string $date_format (how to format the dates)
  * @return string XHTML markup
  */

function make_assets_list_markup( $assets, $site, $fields = array('name','file_size','file_type','description','datetime'), $date_format = 'j F Y' )
{
	if(empty($fields))
	{
		trigger_error('make_assets_list_markup(): $fields must be a populated array');
		return '';
	}
	$recognized_field_names = array('name','file_size','file_type','description','author','datetime');
	foreach($recognized_field_names as $field_name)
	{
		$var_name = '_show_'.$field_name;
		if(in_array($field_name, $fields))
		{
			$$var_name = true;
		}
		else
		{
			$$var_name = false;
		}
	}
	$file_types = array(	'pdf'=>'PDF Document',
							'doc'=>'Word Document','docx'=>'Word Document',
							'xls'=>'Excel Document','xlsx'=>'Excel Document',
							'ppt'=>'Powerpoint Document','pptx'=>'Powerpoint Document',
							'html'=>'HTML Document','htm'=>'HTML Document',
							'txt'=>'Plain Text Document',
							'rtf'=>'Rich Text Document',
							'csv'=>'Comma-Separated Values File',
							'eps'=>'Encapsulated Postscript Document',
							'zip'=>'Compressed ZIP Archive',
							'sit'=>'Compressed Stuffit Archive',
							'sit'=>'Compressed Stuffit Archive',
							'svg'=>'Scalable Vector Graphics Document',
							'psd'=>'Adobe Photoshop Document',
							'tif'=>'TIFF Image File',
							'tiff'=>'TIFF Image File',
							'mpp'=>'Microsoft Project Document',
							'exe'=>'Windows Executable Program',
							'css'=>'Cascading Style Sheet File',
							'xml'=>'XML data file',
						);
	if(!empty($assets))
	{
		$txt = '<ul>'."\n";
		foreach( $assets as $asset )
		{
			$owner_site = $asset->get_owner();
			$type = '';
			if($asset->get_value('file_type') && array_key_exists($asset->get_value('file_type'), $file_types))
			{
				$type = $file_types[$asset->get_value('file_type')];
			}
			$url = '';

			$txt .= '<li>';
			
			if( $_show_name || $_show_file_size || $_show_file_type )
			{
				$txt .= '<div class="name">';
				if($_show_name)
				{
					$txt .= '<a href="'.htmlspecialchars(reason_get_asset_url($asset, $owner_site), ENT_QUOTES).'"><strong>'.$asset->get_value('name').'</strong></a>';
				}
				if( $_show_file_size || $_show_file_type )
				{
					if(!empty($type) || $asset->get_value('file_size'))
					{
						$txt .= ' (';
						if( $asset->get_value('file_size') && $_show_file_size )
							$txt .= $asset->get_value('file_size').' KB';
						if(!empty($type) && $_show_file_type)
							$txt .= ' '.$type;
						$txt .= ')';
						
					}
				}
				$txt .= '</div>'."\n";
			}
			if($_show_author && $asset->get_value('author'))
			{
				$txt .= '<div class="author">'.$asset->get_value('author').'</div> ';
			}
			if($_show_datetime && $asset->get_value('datetime'))
			{
				$txt .= '<div class="date">'.prettify_mysql_datetime($asset->get_value('datetime'), $date_format).'</div> ';
			}
			if($_show_description && $asset->get_value('description'))
			{
				$txt .= '<div class="description">'.$asset->get_value('description').'</div>'."\n";
			}
			$txt .= '</li>'."\n";
		}
		$txt .= '</ul>'."\n";
		return $txt;
	}
	else
	{
		return '';
	}
}

/**
 * Gets a unique version of the given asset filename.
 *
 * Returns a version of the given filename that has been modified (if 
 * necessary) so that it does not conflict with the filename of an existing
 * asset.
 * 
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 * @since Reason 4.0 beta 8
 * @param string $filename the filename to make unique
 * @param int $site_id the ID of the Reason minisite in which the filename must
 *        be unique
 * @param int $skip the ID of an asset to ignore in checking for a unique
 *        filename. If the caller is trying to overwrite an existing asset
 *        file, the ID of that asset should be passed as $skip.
 * @return unique $filename
 */
function reason_get_unique_asset_filename($filename, $site_id, $skip=null) {
	$selector = new entity_selector($site_id);
	$selector->add_type(id_of("asset"));
	$assets = $selector->run_one("", "All");
	
	$existing_filenames = array();
	foreach ((array) $assets as $asset) {
		if (!$skip || $asset->id() != $skip)
			$existing_filenames[] = $asset->get_value("file_name");
	}
	
	list($base, $extension) = get_filename_parts($filename);
	if (!empty($extension))
		$extension = ".$extension";
	
	$index = 1;
	while (in_array($filename, $existing_filenames)) {
		$index++;
		$filename = "{$base}_{$index}{$extension}";
	}
	
	return $filename;
}

/**
 * Get the URL of a Reason asset
 *
 * Returns a URL relative to the server base.
 *
 * @param entity $asset
 * @param entity $owner_site
 * @return string URL
 */
function reason_get_asset_url($asset, $owner_site = NULL)
{
	if(empty($owner_site))
		$owner_site = $asset->get_owner();
	return $owner_site->get_value( 'base_url' ) .MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value( 'file_name' );
}

/**
 * Get the filesystem location of a Reason asset
 *
 * Returns the location of the asset in the filesystem.
 *
 * @param entity $asset
 * @return string filesystem path
 */
function reason_get_asset_filesystem_location($asset)
{
	return ASSET_PATH.$asset->id().'.'.$asset->get_value('file_type');
}

		
function reason_get_asset_max_upload_size()
{
	static $max;
	if(!empty($max))
	return $max;
			
	$post_max_size = get_php_size_setting_as_bytes('post_max_size');
	$upload_max_filesize = get_php_size_setting_as_bytes('upload_max_filesize');
	
	if(!defined('REASON_ASSET_MAX_UPLOAD_SIZE_MEGS'))
		return $post_max_size < $upload_max_filesize ? $post_max_size : $upload_max_filesize;
	
	$reason_max_asset_upload = REASON_ASSET_MAX_UPLOAD_SIZE_MEGS*1024*1024;
	
	if($post_max_size < $reason_max_asset_upload || $upload_max_filesize < $reason_max_asset_upload)
	{
		if($post_max_size < $upload_max_filesize)
		{
			trigger_error('post_max_size in php.ini is less than Reason setting REASON_ASSET_MAX_UPLOAD_SIZE_MEGS; using post_max_size as max upload value');
			return $max = $post_max_size;
		}
		else
		{
			trigger_error('upload_max_filesize in php.ini is less than Reason setting REASON_ASSET_MAX_UPLOAD_SIZE_MEGS; using upload_max_filesize as max upload value');
			return $max = $upload_max_filesize;
		}
	}
	else
	{
		return $max = $reason_max_asset_upload;
	}
}

/**
 * Does basic aspects of making an asset.
 *
 * - Makes sure file exists.
 * - Makes sure desired filename is unique on the site.
 * - Verify / discover mime_type, extension, and file size.
 * - Move the file to the correct storage location for the asset.
 * - Create / update the entity.
 *
 * The content manager should use this. So should anything else that needs to make an asset.
 *
 * After the asset is created and moved to the right spot, reason_update_entity should be used to update anything that needs updating.
 *
 * @param int site_id owner site id
 * @param int user_id id of creator
 * @param file array with these keys: path, name (name is name of the file)
 * @return mixed entity_id of existing asset or FALSE on failure
 *
 * @todo modify asset content manager to use this.
 * @todo update rewrites
 *
 * @author Nathan White
 */
function reason_create_asset($site_id, $user_id, $file)
{
	// first lets do a number of sanity checks and trigger errors as appropriate.
	if (empty($site_id) || (intval($site_id) != $site_id))
	{
		trigger_error('reason_create_asset was provided an invalid site_id parameter (' . $site_id .')');
		return false;
	}
	else
	{
		$site = new entity( intval($site_id) );
		if (!reason_is_entity($site, 'site'))
		{
			trigger_error('reason_create_asset was provided a site_id that is not a reason site entity id (' . $site_id .')');
			return false;
		}
	}
	
	if (empty($user_id) || (intval($user_id) != $user_id))
	{
		trigger_error('reason_create_asset was provided an invalid user_id parameter (' . $user_id .')');
		return false;
	}
	else
	{
		$user = new entity( intval($user_id) );
		if (!reason_is_entity($user, 'user'))
		{
			trigger_error('reason_create_asset was provided a user_id that is not a reason user entity id (' . $user_id .')');
			return false;
		}
	}
	
	if (empty($file) || (!is_array($file)))
	{
		trigger_error('reason_create_asset requires the file parameter to be an array with keys path, name');	
		return false;
	}
	elseif (empty($file['path']) || !file_exists($file['path']))
	{
		if (empty($file['path'])) trigger_error('reason_create_asset requires a file parameter with a key "path" that contains the full path to a file');
		else trigger_error('reason_create_asset was provided a file path (' . $file['path'] . ') that does not exist');
		return false;
	}
	elseif (empty($file['name']))
	{
		trigger_error('reason_create_asset requires a file parameter with a key "name" that specifies what filename should be used for downloading the file');
		return false;
	}
	
	// setup our values array	
	$values['mime_type'] = get_mime_type($file['path'], 'application/octet-stream');
	$values['file_name'] = _reason_get_asset_filename($site_id, $file['name']);
	$values['file_type'] = _reason_get_asset_extension($file['path'], $values['mime_type']);
	$values['file_size'] = _reason_get_asset_size($file['path']);
	$values['new'] = 0;
	
	$asset_id = reason_create_entity( $site_id, id_of('asset'), $user_id, $values['file_name'], $values );
	if (!$asset_id)
	{
		trigger_error('reason_create_asset failed to create the asset entity');
		return false;
	}
	
	// move the asset into place
	$asset_dest = ASSET_PATH.$asset_id.'.'.$values['file_type'];
	if (server_is_windows() && file_exists($asset_dest))
	{
		unlink($asset_dest);
	}
	rename ($file['path'], $asset_dest);
	
	// update rewrites
	$um = new url_manager($site_id);
	$um->update_rewrites();

	return $asset_id;
}

/**
 * Used to update an asset that is already in the filesystem.
 *
 * @param int asset_id id of asset
 * @param int user_id id of person modifying asset
 * @param file array with keys: path, name
 * @return mixed entity_id of existing asset or FALSE on failure
 */
function reason_update_asset($asset_id, $user_id, $file)
{
	if (!is_null($asset_id)) // an existing asset id was provided
	{
		if (empty($asset_id) || (intval($asset_id) != $asset_id))
		{
			trigger_error('reason_update_asset was provided an invalid asset_id parameter (' . $asset_id .')');
			return false;
		}
		else
		{
			$asset = new entity( intval($asset_id) );
			if (!reason_is_entity($asset, 'asset'))
			{
				trigger_error('reason_update_asset was provided a asset_id that is not a reason asset entity id (' . $asset_id .')');
				return false;
			}
		}
	}
	
	if (empty($user_id) || (intval($user_id) != $user_id))
	{
		trigger_error('reason_update_asset was provided an invalid user_id parameter (' . $user_id .')');
		return false;
	}
	else
	{
		$user = new entity( intval($user_id) );
		if (!reason_is_entity($user, 'user'))
		{
			trigger_error('reason_update_asset was provided a user_id that is not a reason user entity id (' . $user_id .')');
			return false;
		}
	}
	
	if (empty($file) || (!is_array($file)))
	{
		trigger_error('reason_update_asset requires the file parameter to be an array with keys path, name');	
		return false;
	}
	elseif (empty($file['path']) || !file_exists($file['path']))
	{
		if (empty($file['path'])) trigger_error('reason_update_asset requires a file parameter with a key "path" that contains the full path to a file');
		else trigger_error('reason_update_asset was provided a file path (' . $file['path'] . ') that does not exist');
		return false;
	}
	elseif (empty($file['name']))
	{
		trigger_error('reason_update_asset requires a file parameter with a key "name" that specifies what filename should be used for downloading the file');
		return false;
	}
	
	$cur_path = reason_get_asset_filesystem_location($asset);
	$cur_name = $asset->get_value('file_name');
	
	// if our name or path has changed lets update the asset
	if ( ($file['path'] != $cur_path) || ($file['name'] != $cur_name) ) // presumably a new file.
	{
		$asset_owner = $asset->get_owner();
		$values['mime_type'] = get_mime_type($file['path'], 'application/octet-stream');
		$values['file_name'] = _reason_get_asset_filename($asset_owner->id(), $file['name'], $asset->id());
		$values['file_type'] = _reason_get_asset_extension($file['path'], $values['mime_type']);
		$values['file_size'] = _reason_get_asset_size($file['path']);
	
		// if the entity update changed the file name lets do rewrites.
		if (reason_update_entity( $asset->id(), $user_id, $values, false))
		{
			if ($cur_name != $values['file_name']) // can we just do assets?
			{
				$um = new url_manager($asset_owner->id());
				$um->update_rewrites();
			}
		}
	
		if ($file['path'] != $cur_path)
		{
			$asset_dest = ASSET_PATH.$asset_id.'.'.$values['file_type'];
			if (server_is_windows() && file_exists($asset_dest))
			{
				unlink($asset_dest);
			}
			rename ($file['path'], $asset_dest);
		}
		return $asset_id;
	}
	return false;
}

/**
 * This function gets rid of any non filename friendly characters and adds .txt to extensions thought to be dangerous.
 *
 * It is called safer because it doesn't stop necessarily stop someone from uploading a file that could be dangerous.
 * 
 * If your asset directory is web accessible and executable, there may well be other files one could upload and execute.
 *
 * @todo the crazy logic used in this function should be simplified
 * @todo the notion of "safer" should be replaced with better security ... whitelist of uploadable extensions probably - add .txt to all others.
 */
function _reason_get_safer_asset_filename($filename)
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
	
	$parts = explode('.', $filename);
	if (count($parts) <= 1)
	{
		$parts_array = array(basename($filename), '');
	} 
	else
	{
		$extension = array_pop($parts);
		$parts_array = array(basename($filename, ".$extension"), $extension);
	}
	
	list($filename, $fext) = $parts_array;
	if(!empty($unsafe_to_safer[$fext])) $fext = $unsafe_to_safer[$fext];
	if (!empty($fext)) $filename .= '.' . $fext;
	
	$filename = sanitize_filename_for_web_hosting($filename);
	return $filename;
}

/**
 * Get a unique filename we'll use for an asset, considering the site_id, desired file_name, and existing asset_id (if any)
 */
function _reason_get_asset_filename($site_id, $desired_filename, $asset_id = null)
{
	// lets munge our filename for greater safety.
	$file_name = _reason_get_safer_asset_filename($desired_filename);
	
	// lets sanitize the filename for uniqueness.
	$file_name = reason_get_unique_asset_filename($file_name, $site_id, $asset_id);
	
	return $file_name;
}

/**
 * Get the extension we'll use for an asset. This is stored on the entity as file_type.
 *
 * @return string extension
 */
function _reason_get_asset_extension($path, $mime_type = null)
{
	// next lets determine the file extension
	$path_parts = pathinfo($path);
	if (!empty($path_parts['extension']))
	{
		$extension = $path_parts['extension'];
	}
	elseif (!empty($mime_type)) // lets map from mime_type
	{
		$type_to_extension = array(
			'application/msword' => 'doc',
			'application/vnd.ms-excel' => 'xls',
			'application/vns.ms-powerpoint' => 'ppt',
			'text/plain' => 'txt',
			'text/html' => 'html',
		);
		$m = array();
		if (preg_match('#^([\w-.]+/[\w-.]+)#', $mime_type, $m))
		{
			// strip off any ;charset= crap
			$my_mime_type = $m[1];
			if (!empty($type_to_suffix[$my_mime_type]))
			{
				$extension = $type_to_suffix[$my_mime_type];
			}
		}
	}
	if (empty($extension)) $extension = 'unk';  // we use this as an extension when it is indeterminate
	return $extension;
}

/**
 * Get the size of an asset. This is stored on the entity as file_size.
 *
 * @return int file_size
 */
function _reason_get_asset_size($path)
{
	return round(filesize($path) / 1024);
}