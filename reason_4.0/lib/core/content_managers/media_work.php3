<?php
/**
 * @package reason
 * @subpackage content_managers
 */
 
reason_include_once('classes/url_manager.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('content_managers/default.php3');
reason_include_once('function_libraries/url_utils.php');
reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once('classes/kaltura_shim.php');
include_once(INCLUDE_PATH.'kaltura/KalturaClient.php');
reason_include_once( 'classes/media_work_displayer.php' );
include_once(CARL_UTIL_INC . 'basic/mime_types.php');
reason_include_once('ssh/ssh.php');
 
	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'av_handler';

	/**
	 * A content manager for Media Works.  This class support kaltura integration if it is turned on.
	 */
	class av_handler extends ContentManager
	{
		
		/**
		* Extensions that are allowed to be imported and managed by Reason for Kaltura.
		* @var array
		*/
		var $recognized_extensions = array('flv', 'f4v', 'mov', 'mp4', 'wmv', 'qt', 'm4v', 'avi', /*'asf',*/ 'wvm', 'mpg', /*'m1v', 'm2v', 'mkv',*/ 'ogg', 'rm', 'webm', 'mp3', 'aiff', 'mpeg', 'wav', 'm4a', 'aac', 'ogv');
		
		var $fields_to_remove = array('rating', 'standalone');
		var $field_order = array ('name', 'datetime', 'author', 'description', 'keywords', 'content','transcript_status', 'rights_statement', 'show_hide');
		
		var $admissions_field_order = array ('name', 'datetime', 'author', 'description', 'keywords', 'content','rating','transcript_status', 'rights_statement', 'show_hide');
		
		/**
		 * The content manager uses logic to figure out if it has all the information
		 * it needs to manage the media (e.g. is Reason set to manage the media,
		 * do we have a class to do it, does the class exist)
		 * If those conditions are met the content manager changes the value of
		 * this variable to true.
		 * @var bool
		 */
		var $manages_media = true;
		
		/*
		* @var bool
		*/
		var $kaltura_integrated_work;
		
		var $kaltura_shim;
		
		function init( $externally_set_up = false )
		{
			// This media work is kaltura-integrated if it is a brand new media work with kaltura 
			// integration turned on, or if it is an existing kaltura-integrated media work.
			$this->kaltura_integrated_work = $this->get_value('integration_library') == 'kaltura' || (KalturaShim::kaltura_enabled() && $this->get_value('datetime') == null);
			
			if ($this->kaltura_integrated_work)
			{
				$this->form_enctype = 'multipart/form-data';
				$this->admin_page->head_items->add_javascript(JQUERY_URL, true);		$this->admin_page->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'content_managers/media_work_content_manager.js');
				$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH .'css/reason_admin/content_managers/media_work.css');
			}
			
			parent::init();
		}
		
		
		function alter_data()
		{
			$this->set_display_name ('name', 'Title');
			$this->set_display_name ('datetime', 'Date Originally Recorded/Created');
			$this->add_comments ('datetime', form_comment('The date this work was made or released'));
			$this->add_comments ('description', form_comment('The brief description that will appear with the work'));

			$editor_name = html_editor_name($this->admin_page->site_id);
			$wysiwyg_settings = html_editor_params($this->admin_page->site_id, $this->admin_page->user_id);
			$min_wysiwyg_settings = $wysiwyg_settings;
			if(strpos($editor_name,'loki') === 0)
			{
				$min_wysiwyg_settings['widgets'] = array('strong','em','linebreak','lists','link');
				if(reason_user_has_privs( $this->admin_page->user_id, 'edit_html' ))
				{
					$min_wysiwyg_settings['widgets'][] = 'source';
				}
			}
			$this->change_element_type( 'description' , $editor_name , $min_wysiwyg_settings );
			$this->set_element_properties( 'description', array('rows'=>5));
			
			$this->change_element_type( 'content' , $editor_name , $wysiwyg_settings );
			$this->set_element_properties('content', array('rows'=>12));
			$this->add_comments ('content', form_comment('Full content, such as a transcript of the media work. You can leave this blank if you don\'t have time to transcribe the content of the work.'));
			$this -> set_display_name ('content', 'Transcript');
			
			$this->change_element_type( 'rights_statement' , $editor_name , $min_wysiwyg_settings );
			$this->set_element_properties('rights_statement', array('rows'=>3));
			$this -> add_comments ('rights_statement', form_comment('e.g. "Some rights reserved. '.FULL_ORGANIZATION_NAME.' licenses this work under the <a href="http://creativecommons.org/licenses/by/2.5/">Creative Commons Attribution 2.5 License</a>." or "Copyright Margaret Smith, 1983. All rights reserved. Used with permission." You may leave this field blank if you are not sure about what license applies to this work.'));
			
			$this -> set_display_name ('show_hide', 'Show or Hide?');
			$this -> change_element_type( 'show_hide', 'radio_no_sort', array('options'=>array('show'=>'Show this work on the public site','hide'=>'Hide this work from the public site')));
			$this -> add_required ('show_hide');
			$show_hide_val = $this->get_value('show_hide');
			if (empty($show_hide_val)) $this->set_value('show_hide', 'show');
			
			$this -> set_display_name ('author', 'Creator');
			$this -> add_comments ('author', form_comment('The person or entity who made this work (e.g. director/producer)'));
			
			$this -> add_comments ('keywords', form_comment('Help others find this by entering the key terms and ideas presented in this work.'));
			
			$this -> add_comments ('transcript_status', form_comment('Choose "Published" when the transcript is finalized'));
			
			if($this->get_value('media_publication_datetime') && $this->get_value('media_publication_datetime') != '0000-00-00 00:00:00')
			{
				$this->change_element_type('media_publication_datetime','solidText',array('display_name'=>'Published'));
			}
			if (!empty($this->fields_to_remove))
			{
				foreach ($this->fields_to_remove as $field)
				{
					$this->remove_element($field);
				}
			}
			$this->set_order ($this->field_order);
			
			$this->change_element_type('tmp_file_name', 'hidden');
			
			if ($this->kaltura_integrated_work)
			{
				// Add a status report field that displays a message if the media is currently transcoding in Kaltura
				if ($this->get_value('transcoding_status') == 'converting')
				{
					$msg = 'Your media file is currently being processed.';
					if ($this->get_value('email_notification'))
					{
						$msg .= ' An email will be sent when processing is complete.';
					}
					$msg .= '<br /><img src="'.REASON_HTTP_BASE_PATH.'modules/av/in_progress_bar.gif" width="220" height="19" alt="" />';
					$this->add_element( 'status_report' , 'commentWithLabel' , array('text'=>$msg));
				}
				elseif ($this->get_value('transcoding_status') == 'error')
				{
					$this->add_element( 'status_report' , 'commentWithLabel' , array('text'=>'<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/error.png" width="16" height="16" alt="Error" /> There was an media-processing error. Please try again, or contact an administrator for assistance.'));
				}
				
				$this->_add_restriction_selector();
				$this->_add_file_upload_element();
				$this->_add_file_preview();
				
				$this->_add_embed_code();
				
				$this->do_basic_kaltura_field_modification();
			
				if (!empty($this->fields_to_remove))
				{
					foreach ($this->fields_to_remove as $field)
					{
						$this->remove_element($field);
					}
				}
				
				$this->set_order (array ( 'status_report', 'file_preview', 'name', 'upload_file',  'email_notification', 'show_download', 'show_embed', 'embed_small', 'embed_medium', 'embed_large', 'link', 'replacement_header', 'file_info_header', 'av_type', 'description', 'keywords', 'datetime', 'author', 'content', 'transcript_status', 'rights_statement', 'media_duration', 'media_publication_datetime', 'access_header', 'show_hide', 'restricted_group', 'no_share'  ));
			}
			else
			{
				// Hide all of the kaltura-related fields in the form
				$this->change_element_type('entry_id', 'hidden');
				$this->change_element_type('av_type', 'hidden');
				$this->change_element_type('media_duration', 'hidden');
				$this->change_element_type('transcoding_status', 'hidden');
				$this->change_element_type('integration_library', 'hidden');
				$this->change_element_type('email_notification', 'hidden');
				$this->change_element_type('show_embed', 'hidden');
				$this->change_element_type('show_download', 'hidden');
			}
		}
		
		/**
		 * Simple form manipulation happens in this method related to kaltura-integrated things
		 */
		function do_basic_kaltura_field_modification()
		{
			if (KalturaShim::kaltura_enabled()) {
				$this->kaltura_shim = new KalturaShim();
			}
			$this->change_element_type('av_type', 'hidden');
	
			$this->add_required ('av_type');
			
			$this -> set_display_name ('av_type', 'Media Type');
			$this->change_element_type( 'av_type', 'radio_no_sort', array('options'=>array('Audio'=>'Audio <span class="smallText formComment">(e.g. sound-only music, speech, etc.)</span>','Video'=>'Video <span class="smallText formComment">(e.g. movies, videos, etc.)</span>',/*'Panorama'=>'Panorama <span class="smallText formComment">(e.g. 360 degree still images)</span>','Interactive'=>'Interactive <span class="smallText formComment">(e.g. game, navigable data, etc.)</span>'*/)));
			
			if ( $this->get_value('media_duration') ) $this->change_element_type('media_duration', 'solidtext');
			else $this->change_element_type('media_duration', 'hidden');
			
			$this->change_element_type('integration_library', 'hidden');
			
			$this -> add_comments ('description', form_comment('(e.g. "A Tour of Northfield")'));
			
			$this->add_element( 'file_info_header', 'comment', array('text'=>'<h4>Media Info</h4>'));
			
			$this->add_element( 'access_header', 'comment', array('text'=>'<h4>Access and Sharing</h4>'));
			
			$this->change_element_type('transcoding_status', 'hidden');
			$this->change_element_type('entry_id', 'hidden');
			
			$this->change_element_type( 'email_notification', 'checkbox');
			$this->set_display_name('email_notification', 'Email Alert');
			$this->add_comments('email_notification', '<span class="smallText">Check this box to receive an email when your media finishes processing.</span>');
			
			$this->set_value('email_notification', true);
			
			$this->change_element_type('show_embed', 'checkbox');
			$this->change_element_type('show_download', 'checkbox');
		}	
		
		function process()
		{
			$old_entity = new entity($this->get_value('id'));
			if($this->get_value('show_hide') == 'show' && ($old_entity->get_value('show_hide') == 'hide' || !$old_entity->get_value('show_hide') ) )
			{
				$this->set_value('media_publication_datetime',date('Y-m-d H:i:s'));
			}
			elseif($this->get_value('show_hide') == 'hide' && $old_entity->get_value('media_publication_datetime'))
			{
				$this->set_value('media_publication_datetime','');
			}
			
			if ($this->kaltura_integrated_work)
			{
				// The checkboxes aren't working without these lines...why?
				if ($this->get_value('email_notification'))
					$this->set_value('email_notification', true);
				if ($this->get_value('show_embed'))
					$this->set_value('show_embed', true);
				if ($this->get_value('show_download'))
					$this->set_value('show_download', true);
				
				$entity = new entity($this->get_value('id'));
				$entry_id = $entity->get_value('entry_id');
				if ( !empty($entry_id))
				{
					$changed = false;
					$changed_fields = array('title' => '', 'description' => '', 'tags' => '');
					if ($this->get_value('name') != $entity->get_value('name'))
					{
						$changed_fields['title'] = $this->get_value('name');
						$changed = true;
					}
					if ($this->get_value('description') != $entity->get_value('description'))
					{
						$changed_fields['description'] = $this->get_value('description');
						$changed = true;	
					}
					if ($this->get_value('keywords') != $entity->get_value('keywords'))
					{
						$changed_fields['tags'] = explode(" ", $this->get_value('keywords'));
						$changed = true;	
					}
					
					if ($changed && KalturaShim::kaltura_enabled())
					{
						$user = new entity( $this->admin_page->authenticated_user_id );
						$this->kaltura_shim->update_media_entry_metadata($this->get_value('entry_id'), $user->get_value('name'), $changed_fields['title'], $changed_fields['description'], $changed_fields['tags']);
					}
				}
			}
			
			parent::process();
		}
		
		/**
		* We do the actual uploading for kaltura-integrated media works here because of possible 
		* errors with the upload form.
		*/
		function run_error_checks()
		{	
			parent::run_error_checks();
			
			if ($this->kaltura_integrated_work)
			{
				if ( !$this->has_error('upload_file') )
				{
					$file_uploaded = false;
					
					$upload_element = $this->get_element('upload_file');
					
					if ( !empty($upload_element) && KalturaShim::kaltura_enabled())
					{
						$file = $this->get_element( 'upload_file' );
						if( ($file->state == 'received' OR $file->state == 'pending') AND file_exists( $file->tmp_full_path ) )
						{
							$user = new entity( $this->admin_page->authenticated_user_id );
							$filePath = $file->tmp_full_path;
							$filename_parts = explode('/', $filePath);
							$filename = end($filename_parts);
							$this->set_value('tmp_file_name', $filename);
							
							$this->_process_work($filePath, $filePath);
						}
					}
				}
			}
		}	
		
		/**
		* Uploads the media work to Kaltura.  $method
		*/
		function _process_work($tmp_path, $filePath)
		{
			// Remove the old associated image if it was a thumbnail
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->add_right_relationship($this->get_value('id'), relationship_id_of('av_to_primary_image'));
			$cur_image = current($es->run_one());
			
			if ($cur_image && strpos($cur_image->get_value('name'), 'Thumbnail)') !== false)
			{
				delete_relationships(array('entity_a' => $this->get_value('id'), 'type' => relationship_id_of('av_to_primary_image')));
			}
			
			
			$user = new entity( $this->admin_page->authenticated_user_id );
			if ($this->get_value('av_type') == 'Video')
			{
				$entry = $this->kaltura_shim->upload_video($filePath, $this->get_value('name'), $this->get_value('description'), explode(" ", $this->get_value('keywords')), $this->_get_categories(), $user->get_value('name'));
			}
			elseif ($this->get_value('av_type') == 'Audio')
			{
				$filename_parts = explode('.', $tmp_path);
				$extension = end($filename_parts);
				
				if ($extension == 'mp3')
					$transcoding_profile = KALTURA_AUDIO_MP3_SOURCE_TRANSCODING_PROFILE;
				elseif ($extension == 'ogg')
					$transcoding_profile = KALTURA_AUDIO_OGG_SOURCE_TRANSCODING_PROFILE;
				else
					$transcoding_profile = KALTURA_AUDIO_TRANSCODING_PROFILE;
	
				$entry = $this->kaltura_shim->upload_audio($filePath, $this->get_value('name'), $this->get_value('description'), explode(" ", $this->get_value('keywords')), $this->_get_categories(), $user->get_value('name'), $transcoding_profile);
			}
			else
			{
				$this->set_error('av_type', 'The Media Type field is required.');
				return;
			}
			
			if (!$entry)
			{
				$this->set_error('upload_file', 'There was an error during the upload process.');
				return;
			}
			
			// If there are already entries in Kaltura for this Media Work, we should delete them
			// and remove the Media Files from Reason, too.
			$existing_entry_id = $this->get_value('entry_id');
			if ( !empty($existing_entry_id) || $this->get_value('integration_library') != 'kaltura' )
			{
				$this->_remove_existing_media($existing_entry_id);
			}
			
			$this->_update_fields_with_file_data($entry);	
		}		
		
		/**
		* A helper function for _process_work().  Returns all of the categories that this
		* media work refers_to or is_about.
		*/
		function _get_categories()
		{
			$es = new entity_selector();
			$es->add_type(id_of('category_type'));
			$es->add_right_relationship($this->get_value('id'), relationship_id_of('av_is_about_category'));
			$abouts = $es->run_one();
			
			$es = new entity_selector();
			$es->add_type(id_of('category_type'));
			$es->add_right_relationship($this->get_value('id'), relationship_id_of('av_refers_to_category'));
			$refers = $es->run_one();
			
			$names = array();
			foreach ($abouts as $cat)
			{
				$names[] = $cat->get_value('name');
			}
			foreach ($refers as $cat)
			{
				$names[] = $cat->get_value('name');
			}
			
			return $names;
		}

		function _update_fields_with_file_data($kaltura_entry)
		{
			$this->set_value('transcoding_status', 'converting');
			$this->set_value('entry_id', $kaltura_entry->id);
			$this->set_value('integration_library', 'kaltura');
		}
		
		function _remove_existing_media($entry_id)
		{
			// First grab all related Media Files
			$es = new entity_selector();
			$es->add_type(id_of('av_file'));
			$es->add_right_relationship($this->get_value('id'), relationship_id_of('av_to_av_file'));
			$media_files = $es->run_one();
			
			$user = new entity( $this->admin_page->user_id );
			
			// Delete actual Reason entities for media files
			foreach ($media_files as $entity)
			{
				reason_expunge_entity($entity->id(), $user->id());
			}
			
			// Remove Entries from Kaltura
			$this->kaltura_shim->delete_media($entry_id, $user->get_value('name'));
		}

		
		/**
		* For Kaltura-integrated Media Works, the upload element is added to the form.
		*
		* @access private
		*/
		function _add_file_upload_element()
		{
			if(KalturaShim::kaltura_enabled() && $this->manages_media && $this->get_value('transcoding_status') != 'converting')
			{
				$authenticator = array("reason_username_has_access_to_site", $this->get_value("site_id"));
				$params = array('authenticator' => $authenticator,
								'acceptable_extensions' => $this->recognized_extensions,
								'max_file_size' => $this->_get_actual_max_upload_size(),
								'head_items' => &$this->head_items);
				$this->add_element( 'upload_file', 'ReasonUpload', $params);
				
				$this->add_comments('upload_file',form_comment('If the file is on your computer, browse to it here.') );
				$this->add_comments('upload_file',form_comment('File must have one of the following extensions: .'.implode(', .', $this->recognized_extensions) ) );
				$this->add_comments('upload_file',form_comment('<div class="maxUploadSizeNotice">Maximum file size for uploading is '.format_bytes_as_human_readable($this->_get_actual_max_upload_size()).'. </div>'));
				
				if ($this->get_value('transcoding_status') == 'ready')
				{
					$this->set_display_name('upload_file', 'Upload Replacement File');
				}
			}
		}	
		
		// helper function used in _add_file_upload_element()
		function _get_actual_max_upload_size()
		{
			$sizes = array();
			$post_max_size = get_php_size_setting_as_bytes('post_max_size');
			$upload_max_filesize = get_php_size_setting_as_bytes('upload_max_filesize');
			$reason_max_media_upload = MEDIA_MAX_UPLOAD_FILESIZE_MEGS*1024*1024;
			
			if($post_max_size < $reason_max_media_upload || $upload_max_filesize < $reason_max_media_upload)
			{
				if($post_max_size < $upload_max_filesize)
				{
					trigger_error('post_max_size in php.ini is less than Reason setting MEDIA_MAX_UPLOAD_FILESIZE_MEGS; using post_max_size as max upload value');
					return $post_max_size;
				}
				else
				{
					trigger_error('upload_max_filesize in php.ini is less than Reason setting MEDIA_MAX_UPLOAD_FILESIZE_MEGS; using upload_max_filesize as max upload value');
					return $upload_max_filesize;
				}
			}
			else
			{
				return $reason_max_media_upload;
			}
		}
		
		/**
		* For Kaltura-integrated Media Works, adds a preview of the Work to the form.
		*/
		function _add_file_preview()
		{
			if($this->get_value('transcoding_status') == 'ready')
			{
				$displayer = new MediaWorkDisplayer();
	
				$entity = new entity($this->get_value('id'));
				$displayer->set_media_work($entity);
				$embed_markup = '';
				
				if ($this->get_value('av_type') == 'Video')
				{
					$embed_markup .= '<div id="videoPreviews">';
					$displayer->set_height('small');
					$embed_markup .= '<div class="videoPreview small">'.$displayer->get_iframe_markup().'</div>';
					
					$displayer->set_height('medium');
					$embed_markup .= '<div class="videoPreview medium">'.$displayer->get_iframe_markup().'</div>';
					
					$displayer->set_height('large');
					$embed_markup .= '<div class="videoPreview large">'.$displayer->get_iframe_markup().'</div>';
					
					$embed_markup .= '</div>';
					
					$image_picker_url = carl_make_link(array('cur_module' => 'MediaImagePicker'));
					$image_picker_link = '<br/><a href="'.$image_picker_url.'"><strong>Choose a still frame for this video</strong></a>';
					$embed_markup .= $image_picker_link;
				}
				else
				{
					$embed_markup .= $displayer->get_iframe_markup();
				}
					
				if(!empty($embed_markup))
				{
					$this->add_element( 'file_preview' , 'commentWithLabel' , array('text'=>$embed_markup));
					$this->set_display_name('file_preview', 'Preview');
				}
			}
		}
		
		
		/**
		* For Kaltura-integrated Media Works, adds some fields containing copy-paste-able 
		* embedding code.
		*/
		function _add_embed_code()
		{
			if($this->get_value('transcoding_status') == 'ready')
			{
				$displayer = new MediaWorkDisplayer();
				
				$entity = new entity($this->get_value('id'));
				$displayer->set_media_work($entity);
				
				if ($this->get_value('av_type') == 'Video')
				{
					$displayer->set_height('small');
					$embed_markup_small = $displayer->get_iframe_markup();
					
					$displayer->set_height('medium');
					$embed_markup_medium = $displayer->get_iframe_markup();
					
					$displayer->set_height('large');
					$embed_markup_large = $displayer->get_iframe_markup();
		
					if (!empty($embed_markup_small))
					{
						$this->add_element('embed_small', 'text', array('maxlength'=>99999));
						$this->set_value('embed_small', $embed_markup_small);
						$this->set_display_name('embed_small', 'Small Embed Code');
						
						$this->add_element('embed_medium', 'text', array('maxlength'=>99999));
						$this->set_value('embed_medium', $embed_markup_medium);
						$this->set_display_name('embed_medium', 'Medium Embed Code');
						
						$this->add_element('embed_large', 'text', array('maxlength'=>99999));
						$this->set_value('embed_large', $embed_markup_large);
						$this->set_display_name('embed_large', 'Large Embed Code');
					}
				}
				else
				{
					$embed_markup_small = $displayer->get_iframe_markup();
					if (!empty($embed_markup_small))
					{
						$this->add_element('embed_small', 'text');
						$this->set_value('embed_small', $embed_markup_small);
						$this->set_display_name('embed_small', 'Audio Embedding Code');
					}
				}
			}
		}
		
		/**
		* For Kaltura-integrated Media Works, adds a selector for restricting access.
		*/
		function _add_restriction_selector()
		{
			$this->add_relationship_element('restricted_group', id_of('group_type'), relationship_id_of('av_restricted_to_group'), 'right', 'select', true, $sort = 'entity.name ASC');
			$this->set_display_name('restricted_group', 'Limit Access');
		}
		
	}
?>
