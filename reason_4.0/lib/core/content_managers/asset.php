<?php
	reason_include_once( 'classes/url_manager.php' );
	reason_include_once( 'content_managers/default.php3' );

	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'AssetManager';

	class AssetManager extends ContentManager
	{
		var $form_enctype = 'multipart/form-data';

		function alter_data() // {{{
		{
			$this->add_element( 'asset', 'AssetUpload' );
	
			$this->set_comments( 'name', form_comment('A name for internal reference.') );
			$this->set_comments( 'content', form_comment('A long description of the document, if it needs it. This field is not required.') );
			$this->set_comments( 'description', form_comment('A description of the document.') );
			$this->set_comments( 'keywords', form_comment('A few words to aid in searching for the document.') );
			$this->set_comments( 'datetime', form_comment('mm/dd/yyyy') );
			$this->set_comments ( 'asset', form_comment('Your filename may be modified if it is has already been taken, or includes spaces or unusual characters.'));

			$this->add_required( 'description' );
			
			$this->set_display_name( 'asset', 'File' );
			$this->set_display_name( 'datetime', 'Publication Date' );
			
			$this->change_element_type( 'file_size', 'hidden' );
			$this->change_element_type( 'file_name', 'hidden' );
			$this->change_element_type( 'file_type', 'hidden' );
			$this->change_element_type( 'mime_type', 'hidden' );
		} // }}}
		function on_every_time() // {{{
		{
			// set up existing file.  if it exists.
			$web_asset_path = WEB_ASSET_PATH.$this->_id.'.'.$this->get_value('file_type');
			$full_asset_path = ASSET_PATH.$this->_id.'.'.$this->get_value('file_type');
			if( file_exists( $full_asset_path ) )
				$this->change_element_type( 'asset','AssetUpload',array('existing_file' => $full_asset_path, 'allow_upload_on_edit' => true, 'file_display_name' => $this->get_value( 'file_name' ) ) );
		} // }}}
		function pre_error_check_actions() // {{{
		{
			// if a file has been uploaded or a file exists, show the file_name field
			// otherwise, keep it hidden
			$asset = $this->get_element( 'asset' );
			if( $asset->state == 'uploaded' OR $asset->state == 'pending' OR $asset->state == 'existing' )
			{
				$this->add_required( 'file_name' );
				$this->change_element_type( 'file_name', 'text' );
			}

			// on an upload, set the file_name field within Disco to perform error checks.
			// we also need to make the value isn't already set - we don't want to clobber it.
			// shit.  or do we?
			if( $asset->state == 'uploaded' )
				$this->set_value( 'file_name', $asset->file[ 'name' ] );


			$this->set_order(
				array(
					'name',
					'description',
					'keywords',
					'author',
					'datetime',
					'content',
					'file_name',
					'asset',
					'file_size',
					'mime_type',
					'no_share',
				)
			);
		} // }}}
		
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
			$fext  = array_pop(explode('.', $filename));
			if ($fext == $filename) $fext = '';
			$fnamebase = basename($filename, '.'.$fext);
			if( !empty( $unsafe_to_safer[ $fext ] ) ) $fext = $unsafe_to_safer[$fext];
			$filename = $fnamebase;
			if (!empty($fext)) $filename .= '.' . $fext;
			return $filename;
		}

		function get_unique_filename ($filename, $asset_names)
		{
			// returns a unique filename not in array $asset_names - nwhite 11/28/05
			$fext  = array_pop(explode('.', $filename));
			if ($fext == $filename) $fext = '';
			$fnamebase = basename($filename, '.'.$fext);
			$index = 0;
			while ( in_array( $filename, $asset_names ) )
			{
				$index++;
				$filename = $fnamebase . $index;
				if (!empty($fext)) $filename .= '.' . $fext;
			}
		return $filename;
		}
		
		function run_error_checks() // {{{
		{
			// check to see if an asset has been uploaded
			$asset = $this->get_element( 'asset' );
			if( $asset->state == 'ready' )
				$this->set_error( 'asset', 'You must upload a file' );
			else
			{
				if( !$this->has_error( 'file_name' ) )
				{
					// sanitize excutable extensions
					$safename = $this->get_safer_filename($this->get_value('file_name' ));
					// convert unpleasant characters to underscores
					$this->set_value('file_name', sanitize_filename_for_web_hosting($safename));						
				}

				if( !$this->has_error( 'file_name' ) )
				{
					// get all file names of assets except for the file name of the current asset
					$es = new entity_selector( $this->get_value( 'site_id' ) );
					$es->add_type( id_of( 'asset' ) );
					$assets = $es->run_one('', 'All');
					if(!empty($assets))
					{
						$asset_names = array();
						foreach( $assets AS $asset )
						{
							if( $asset->id() != $this->_id )
								$asset_names[] = $asset->get_value( 'file_name' );
						}
	
						
						// transparently change filename to something unique
						if ( in_array( $this->get_value( 'file_name' ), $asset_names ) )
						{
							$this->set_value ('file_name', $this->get_unique_filename ( $this->get_value( 'file_name' ), $asset_names ) );
						}
					}

				}
			}
		} // }}}
		function post_error_check_actions() // {{{
		{
			// display the URL of the document or a warning if no doc dir is set up.
			
			$asset = $this->get_element( 'asset' );
			$site = new entity( $this->get_value( 'site_id' ) );
			if( $this->get_value( 'file_name' ) )
			{
				if( $this->has_error( 'asset' ) OR $this->has_error( 'file_name' ) )
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

			// something of a kludge.  asset_tmp_file should only be shown by the assetUpload plasmature type.
			// however, Disco was apparently seeing it in the request variables and thinking of it as
			// a field of its own and dumping a hidden field in addition to and after the plasmature dump.
			// so, removing the field here seems to solve the problem.  although, really, the problem it caused
			// was very minor.  but it should all be good now.
			$this->remove_element( 'asset_tmp_file' );
		} // }}}
		function process() // {{{
		{
			$document = $this->get_element( 'asset' );

			// see if document was uploaded successfully
			if( ($document->state == 'uploaded' OR $document->state == 'pending') AND file_exists( $document->tmp_full_path ) )
			{
				$path_parts = pathinfo($document->tmp_full_path);
				$suffix = (!empty($path_parts['extension'])) ? $path_parts['extension'] : '';

                // if there is no extension/suffix, try to guess based on the MIME type of the file
                // actually, this shouldn't matter.  if there is no suffix, I do not think that a MIME type
                // will come through.  But, this is something of a safety harness.
                
                if( empty( $suffix ) )
                {
                    $type_to_suffix = array(
                        'application/msword' => 'doc',
                        'application/vnd.ms-excel' => 'xls',
                        'application/vns.ms-powerpoint' => 'ppt',
                        'text/plain' => 'txt',
                        'text/html' => 'html',
                     );

                     if( !empty( $type_to_suffix[ $document->file['type'] ] ) )
                        $suffix = $type_to_suffix[ $document->file['type'] ];
                }
                if(empty($suffix)) 
                {
                	$suffix = 'unk';
                	trigger_error('uploaded asset at '.$document->tmp_full_path.' had an indeterminate file extension ... assigned to .unk');
                }
                
				// set up values for insertion into the DB
				// set file size
				$this->set_value('file_size', round(filesize( $document->tmp_full_path ) / 1024) );

				// set mime type
				$this->set_value('mime_type', $document->file[ 'type' ] );

				// set file type
				$this->set_value('file_type', $suffix );

				// move the file
				rename( $document->tmp_full_path, ASSET_PATH.$this->_id.'.'.$suffix );
			}

			// make sure to ignore the 'asset' field
			$this->_process_ignore[] = 'asset';

			// and, call the regular CM process method
			parent::process();

//			changed to use URL manager directly - this may be the last thing that was using
//			the update_global_rewrites.php script
//
//			include_once( REASON_INC.'micro_scripts/update_global_rewrites.php' );

			$um = new url_manager( $this->get_value( 'site_id') );
			$um->update_rewrites();

		} // }}}
	}

?>
