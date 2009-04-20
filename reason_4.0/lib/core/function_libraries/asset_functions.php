<?php
/**
 * @package reason
 * @subpackage function_libraries
 */
 
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
?>
