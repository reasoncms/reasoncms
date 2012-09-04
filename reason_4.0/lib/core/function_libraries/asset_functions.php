<?php
/**
 * @package reason
 * @subpackage function_libraries
 */

require_once CARL_UTIL_INC."basic/filesystem.php";
 
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
							'doc'=>'Word Document',
							'xls'=>'Excel Document',
							'ppt'=>'Powerpoint Document',
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
