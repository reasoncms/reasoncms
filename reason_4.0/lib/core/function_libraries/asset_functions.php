<?php

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
						$txt .= '<a href="'.$owner_site->get_value( 'base_url' ) .MINISITE_ASSETS_DIRECTORY_NAME.'/'.$asset->get_value( 'file_name' ).'"><strong>'.$asset->get_value('name').'</strong></a>';
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
?>
