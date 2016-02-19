<?php
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('classes/url_manager.php');
reason_include_once('function_libraries/url_utils.php');
reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once('classes/default_access.php');
reason_include_once('classes/media/interfaces/media_work_content_manager_modifier_interface.php');
reason_include_once('classes/media/zencoder/shim.php');
reason_include_once('classes/media/zencoder/displayer_chrome/size_switch.php');
reason_include_once('content_managers/image.php3');

/**
 * A class that modifies the given Media Work content manager for Zencoder-integrated 
 * Media Works.
 */
class ZencoderMediaWorkContentManagerModifier implements MediaWorkContentManagerModifierInterface
{
	/**
	 * The disco form this modifier class will modify.
	 */
	protected $manager;
	
	protected $shim;
		
	protected $displayer_chrome;
	
	protected $recognized_extensions;
			
	function set_content_manager($manager)
	{
		$this->manager = $manager;
		$manager->recognized_extensions = array();
		$this->shim = new ZencoderShim();
		
		if ( $manager->get_value('entry_id') )
		{
			$this->displayer_chrome = new ZencoderSizeSwitchDisplayerChrome();
			$this->displayer_chrome->set_media_work(new entity($manager->get_value('id')));
		}		
		$this->recognized_extensions = ZencoderShim::get_recognized_extensions();
	}
	
	/**
	 * Sets the required head_items in the content manager.
	 */
	public function set_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/zencoder/media_work_content_manager.js');
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH .'media/zencoder/media_work_content_manager.css');
		if ($this->displayer_chrome)
			$this->displayer_chrome->set_head_items($head_items);
	}
	
	/**
	 * Add zencoder-integrated-specific fields to the disco form.  
	 */
	public function alter_data()
	{
		$this->manager->set_display_name ('name', 'Title');
		$this->manager->set_display_name ('datetime', 'Date Originally Recorded/Created');
		$this->manager->set_comments ('datetime', form_comment('The date this work was made or released'));
		$this->manager->set_comments ('description', form_comment('The brief description that will appear with the work'));

		$editor_name = html_editor_name($this->manager->admin_page->site_id);
		$wysiwyg_settings = html_editor_params($this->manager->admin_page->site_id, $this->manager->admin_page->user_id);
		$min_wysiwyg_settings = $wysiwyg_settings;
		if(strpos($editor_name,'loki') === 0)
		{
			$min_wysiwyg_settings['widgets'] = array('strong','em','linebreak','lists','link');
			if(reason_user_has_privs( $this->manager->admin_page->user_id, 'edit_html' ))
			{
				$min_wysiwyg_settings['widgets'][] = 'source';
			}
		}
		$this->manager->change_element_type( 'description' , $editor_name , $min_wysiwyg_settings );
		$this->manager->set_element_properties( 'description', array('rows'=>5));
		
		$this->manager->change_element_type( 'content' , $editor_name , $wysiwyg_settings );
		$this->manager->set_element_properties('content', array('rows'=>12));
		$this->manager->set_comments ('content', form_comment('Full content, such as a transcript of the media work. You can leave this blank if you don\'t have time to transcribe the content of the work.'));
		$this->manager->set_display_name ('content', 'Transcript');
		
		$this->manager->change_element_type( 'rights_statement' , $editor_name , $min_wysiwyg_settings );
		$this->manager->set_element_properties('rights_statement', array('rows'=>3));
		$this->manager->set_comments ('rights_statement', form_comment('e.g. "Some rights reserved. '.FULL_ORGANIZATION_NAME.' licenses this work under the <a href="http://creativecommons.org/licenses/by/2.5/">Creative Commons Attribution 2.5 License</a>." or "Copyright Margaret Smith, 1983. All rights reserved. Used with permission." You may leave this field blank if you are not sure about what license applies to this work.'));
		
		$this->manager->set_display_name ('show_hide', 'Show or Hide?');
		$this->manager->change_element_type( 'show_hide', 'radio_no_sort', array('options'=>array('show'=>'Show this work on the public site','hide'=>'Hide this work from the public site')));
		$this->manager->add_required ('show_hide');
		$show_hide_val = $this->manager->get_value('show_hide');
		if (empty($show_hide_val)) $this->manager->set_value('show_hide', 'show');
		
		$this->manager->set_display_name ('author', 'Creator');
		$this->manager->set_comments ('author', form_comment('The person or entity who made this work (e.g. director/producer)'));
		
		$this->manager->set_comments ('keywords', form_comment('Help others find this by entering the key terms and ideas presented in this work.'));
		
		$this->manager->set_comments ('transcript_status', form_comment('Choose "Published" when the transcript is finalized'));
		
		if($this->manager->get_value('media_publication_datetime') && $this->manager->get_value('media_publication_datetime') != '0000-00-00 00:00:00')
		{
			$this->manager->change_element_type('media_publication_datetime','solidText',array('display_name'=>'Published'));
		}
		if (!empty($this->manager->fields_to_remove))
		{
			foreach ($this->manager->fields_to_remove as $field)
			{
				$this->manager->remove_element($field);
			}
		}
		$this->manager->set_order($this->manager->field_order);
		
		$this->manager->change_element_type('tmp_file_name', 'protected');
		
		// Hide all of the integrated-related fields in the form
		$this->manager->change_element_type('entry_id', 'protected');
		$this->manager->change_element_type('av_type', 'protected');
		$this->manager->change_element_type('media_duration', 'protected');
		$this->manager->change_element_type('transcoding_status', 'protected');
		$this->manager->change_element_type('integration_library', 'protected');
		$this->manager->change_element_type('salt', 'cloaked');
		$this->manager->change_element_type('original_filename', 'protected');
		
		$this->manager->form_enctype = 'multipart/form-data';
		
		// Add a status report field that displays a message if the media is currently transcoding
		if ($this->manager->get_value('transcoding_status') == 'converting')
		{
			$msg = 'Your media file is currently being processed.';
			if ($this->manager->get_value('email_notification'))
			{
				$msg .= ' An email will be sent when processing is complete.';
			}
			$msg .= '<br /><img src="'.REASON_HTTP_BASE_PATH.'modules/av/in_progress_bar.gif" width="220" height="19" alt="" />';
			$this->manager->add_element( 'status_report' , 'commentWithLabel' , array('text'=>$msg));
		}
		elseif ($this->manager->get_value('transcoding_status') == 'error')
		{
			$this->manager->add_element( 'status_report' , 'commentWithLabel' , array('text'=>'<img src="'.REASON_HTTP_BASE_PATH.'silk_icons/error.png" width="16" height="16" alt="Error" /> There was an media-processing error. Please try again, or contact an administrator for assistance.'));
		}
		
		$this->_add_restriction_selector();
		$this->_add_file_upload_element();
		$this->_add_file_preview();
		$this->_add_embed_code();
		
		$this->do_basic_zencoder_field_modification();
	
		if (!empty($this->manager->fields_to_remove))
		{
			foreach ($this->manager->fields_to_remove as $field)
			{
				$this->manager->remove_element($field);
			}
		}
		
		$this->manager->set_order (array ('file_preview', 'name', 'upload_file', 'upload_url', 'email_notification', 'show_download', 'show_embed', 'embed_small', 'embed_medium', 'embed_large', 'link', 'replacement_header', 'file_info_header', 'av_type', 'description', 'keywords', 'datetime', 'author', 'content', 'transcript_status', 'rights_statement', 'media_duration', 'media_publication_datetime', 'access_header', 'show_hide', 'restricted_group', 'no_share'  ));
	}
	
	/**
	*  Add a callback to the disco form's process()
	*/
	public function process()
	{
		$this->manager->add_callback(array($this, '_process_callback'), 'process');
	}
	
	/**
	 * This callback generates the thumbnail image for the video.  It also updates some metadata 
	 * such as duration for the entity.
	 */
	public function _process_callback()
	{
	}
	
	/**
	 * Adds a preview of the media work to the form.
	 */
	function _add_file_preview()
	{
		if($this->manager->get_value('transcoding_status') == 'ready')
		{
			$entity = new entity($this->manager->get_value('id'));
			$embed_markup = '';
			$this->displayer_chrome->set_media_height('small');
			$this->displayer_chrome->set_google_analytics(false);
			$embed_markup .= $this->displayer_chrome->get_html_markup();
			
			if ($this->manager->get_value('av_type') == 'Video')
			{
				$image_picker_url = carl_make_link(array('cur_module' => 'ZencoderMediaImagePicker'));
				$image_picker_link = '<br/><a href="'.$image_picker_url.'"><strong>Choose a thumbnail for this video</strong></a>';
				$embed_markup .= $image_picker_link;
			}
			
			if(!empty($embed_markup))
			{
				$this->manager->add_element( 'file_preview' , 'commentWithLabel' , array('text'=>$embed_markup));
				$this->manager->set_display_name('file_preview', 'Preview');
			}
		}
	}
	
	/**
	 * Adds a selector for restricting access to the media work.
	 */
	function _add_restriction_selector()
	{
		$this->manager->add_relationship_element('restricted_group', id_of('group_type'), relationship_id_of('av_restricted_to_group'), 'right', 'select', true, $sort = 'entity.name ASC');
		$this->manager->set_display_name('restricted_group', 'Limit Access');
		if($this->manager->is_new_entity() && $this->manager->_is_first_time() && !$this->manager->get_value('name') && !$this->manager->get_value('restricted_group') && ($group_id = $this->get_default_restricted_group_id()))
		{
			$this->manager->set_value('restricted_group', $group_id);
		}
	}
	
	function get_default_restricted_group_id()
	{
		$da = reason_get_default_access();
		return $da->get($this->manager->get_value( 'site_id' ), $this->manager->get_value( 'type_id' ), 'av_restricted_to_group');
	}
	
	/**
	 * Adds some fields containing copy-paste-able embedding code.
	 */
	function _add_embed_code()
	{
		if ($this->manager->get_value('transcoding_status') == 'ready')
		{
			reason_include_once( 'classes/media/zencoder/media_work_displayer.php' );
			$displayer = new ZencoderMediaWorkDisplayer();
			$entity = new entity($this->manager->get_value('id'));
			$displayer->set_media_work($entity);
			
			if ($this->manager->get_value('av_type') == 'Video')
			{
				$displayer->set_height('small');
				$embed_markup_small = $displayer->get_display_markup();
							
				$displayer->set_height('medium');
				$embed_markup_medium = $displayer->get_display_markup();
				
				$displayer->set_height('large');
				$embed_markup_large = $displayer->get_display_markup();
		
				if (!empty($embed_markup_small))
				{
					$this->manager->add_element('embed_small', 'text', array('maxlength'=>9001));
					$this->manager->set_value('embed_small', $embed_markup_small);
					$this->manager->set_display_name('embed_small', 'Small Embed Code');
					
					$this->manager->add_element('embed_medium', 'text', array('maxlength'=>9001));
					$this->manager->set_value('embed_medium', $embed_markup_medium);
					$this->manager->set_display_name('embed_medium', 'Medium Embed Code');
					
					$this->manager->add_element('embed_large', 'text', array('maxlength'=>9001));
					$this->manager->set_value('embed_large', $embed_markup_large);
					$this->manager->set_display_name('embed_large', 'Large Embed Code');
				}
			}
			elseif ($this->manager->get_value('av_type') == 'Audio')
			{
				$embed_markup_small = $displayer->get_display_markup();
				if (!empty($embed_markup_small))
				{
					$this->manager->add_element('embed_small', 'text');
					$this->manager->set_value('embed_small', $embed_markup_small);
					$this->manager->set_display_name('embed_small', 'Audio Embedding Code');
				}
			}
		}
	}
	
	/**
	 * Simple form manipulation happens in this method related to zencoder-integrated things.
	 */
	function do_basic_zencoder_field_modification()
	{
		$this->manager->add_required ('av_type');
		$this->manager->set_display_name ('av_type', 'Media Type');
		$this->manager->change_element_type( 'av_type', 'radio_no_sort', array('options'=>array('Audio'=>'Audio <span class="smallText formComment">(e.g. sound-only music, speech, etc.)</span>','Video'=>'Video <span class="smallText formComment">(e.g. movies, videos, etc.)</span>',/*'Panorama'=>'Panorama <span class="smallText formComment">(e.g. 360 degree still images)</span>','Interactive'=>'Interactive <span class="smallText formComment">(e.g. game, navigable data, etc.)</span>'*/)));
		
		if ( $this->manager->get_value('media_duration') ) 
			$this->manager->change_element_type('media_duration', 'solidtext');
		else 
			$this->manager->change_element_type('media_duration', 'protected');
		
		$this->manager->change_element_type('integration_library', 'protected');
		
		$this->manager->set_comments ('description', form_comment('(e.g. "A Student Band Recording")'));
		
		$this->manager->add_element( 'file_info_header', 'comment', array('text'=>'<h4>Media Info</h4>'));
		$this->manager->add_element( 'access_header', 'comment', array('text'=>'<h4>Access and Sharing</h4>'));
		
		$this->manager->change_element_type('transcoding_status', 'protected');
		$this->manager->change_element_type('entry_id', 'protected');
		
		
		$this->manager->change_element_type( 'email_notification', 'checkbox');
		$this->manager->set_display_name('email_notification', 'Email Alert');
		$this->manager->set_comments('email_notification', '<span class="smallText">Check this box to receive an email when your media finishes processing.</span>');
		//$this->manager->change_element_type('email_notification', 'hidden');
		$this->manager->set_value('email_notification', true);
		// Add a status report field that displays a message if the media is currently transcoding
		if ($this->manager->get_value('transcoding_status') == 'converting')
		{
			$msg = 'Your media file is currently being processed.';
			if ($this->manager->get_value('email_notification'))
			{
				$msg .= ' An email will be sent when processing is complete.';
			}
			$msg .= '<br /><img src="'.REASON_HTTP_BASE_PATH.'modules/av/in_progress_bar.gif" width="220" height="19" alt="" style="vertical-align:middle;" />';
			
			if(method_exists($this->shim, 'get_progress'))
			{
				$work = new entity($this->manager->get_value('id'));
				$progress = $this->shim->get_progress($work);
				if(!empty($progress) && !empty($progress->state))
				{
					$translations = array(
						'pending' => 'Transferring',
						'waiting' => 'Transferring',
						'processing' => '',
						'finished' => 'Finishing',
						'failed' => 'Failed',
						'cancelled' => 'Cancelled',
					);
					$msg .= ' <span class="smallText">';
					if(isset($translations[$progress->state]))
						$msg .= $translations[$progress->state] . ' ';
					else
						$msg .= htmlspecialchars() . ' ';
					if(!empty($progress->progress))
						$msg .= htmlspecialchars(round($progress->progress,1)).'% done';
					$msg .= ' <a href="'.htmlspecialchars(get_current_url()).'">refresh</a></span>';
				}
			}
			$this->manager->add_element( 'status_report' , 'commentWithLabel' , array('text'=>$msg));
		}
		
		$this->manager->change_element_type('show_embed', 'checkbox');
		if (!$this->manager->get_value('entry_id'))
		{
			$this->manager->set_value('show_embed', true);
		}
		$this->manager->change_element_type('show_download', 'checkbox');
		if ($this->manager->is_new_entity())
		{
			$this->manager->set_value('show_download', true);
		}
	}
	
	/**
	 * Adds callback for run_error_checks() in the disco form.
	 */
	function run_error_checks() 
	{
		$this->manager->add_callback(array($this, '_run_error_checks_callback'), 'run_error_checks');
	}
	
	/**
	 * Checks for errors with the uploaded file and uploads to Zencoder.
	 */
	function _run_error_checks_callback()
	{
		if ( !$this->manager->has_error('upload_file') && !$this->manager->has_error('upload_url') )
		{
			// Make sure only one 
			if ($this->manager->get_value('upload_file') && $this->manager->get_value('upload_url'))
			{
				$this->manager->set_error('upload_file', 'Cannot upload file and provide a url at the same time. Only use one.');
				$this->manager->set_error('upload_url', 'Cannot upload file and provide a url at the same time. Only use one.');
				return;
			}
			
			if ($this->manager->get_value('upload_file'))
			{			
				$file = $this->manager->get_element('upload_file');
			
				if ( !empty($file))
				{
					if( ($file->state == 'received' OR $file->state == 'pending') AND file_exists( $file->tmp_full_path ) )
					{
						$this->manager->set_value('original_filename', $this->_sanitize_filename(urldecode($file->file['name'])));
						$this->_set_salt();
					
						$user = new entity( $this->manager->admin_page->authenticated_user_id );
						$filePath = $file->tmp_full_path;
						$filename_parts = explode('/', $filePath);
						$filename = end($filename_parts);
						$this->manager->set_value('tmp_file_name', $filename);
						
						// convert filePath to web accessible url -- as of Jan 2016, this needs to go through the
						// getTempFile script since temp files are no longer directly web accessible.
						// $filePath = carl_construct_link(array(), array(), WEB_TEMP.$filename);
						$filePath = carl_construct_link(array("f" => $filename), array(), REASON_HTTP_BASE_PATH . "scripts/upload/getTempFile.php");
						// echo("filepath [" . $filePath . "]<p>encoded [" . urlencode($filePath) . "]<P>special [" . htmlspecialchars($filePath) . "]<hr>");

						$this->_process_work($filePath, false);
					}
				}
			}
			elseif ($this->manager->get_value('upload_url')) // an upload url must have been provided
			{
				$url = $this->manager->get_value('upload_url');
				
				// check if url is valid
				// check to see if url exists
				$name = basename($url);
				$encoded_url = str_replace($name, rawurlencode($name), $url);
				$ch = curl_init($encoded_url);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_exec($ch);
				$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($retcode != 200)
				{
					$this->manager->set_error('upload_url', 'The provided url was invalid.');
					return;
				}
				
				$filename = basename($url);
				$sanitized_filename = $this->_sanitize_filename(urldecode($filename));
				$this->manager->set_value('original_filename', $sanitized_filename);
				$this->_set_salt();
				$this->manager->set_value('tmp_file_name', $url);
				
				$this->_process_work($url, true);
			}
		}
		
		// checkboxes are strange, so this hack ensures their values are correctly set.
		if ($this->manager->get_value('email_notification'))
			$this->manager->set_value('email_notification', true);
		if ($this->manager->get_value('show_embed'))
			$this->manager->set_value('show_embed', true);
		if ($this->manager->get_value('show_download'))
			$this->manager->set_value('show_download', true);
	}
	
	/**
	 * Uploads the media work to Zencoder, so it can perform encoding. // TODO: filename sanitation?
	 */
	function _process_work($url, $remote_url)
	{
		$user = new entity( $this->manager->admin_page->authenticated_user_id );
		$media_work = $this->manager->entity;
		$media_work->set_value('name', $this->manager->get_value('name'));
		$media_work->set_value('integration_library', $this->manager->get_value('integration_library'));
		$media_work->set_value('creation_date', $this->manager->entity->get_value('creation_date'));
		$media_work->set_value('created_by', $this->manager->entity->get_value('created_by'));
		$media_work->set_value('salt', $this->manager->get_value('salt'));
	
		if ($this->manager->get_value('av_type') == 'Video')
		{
			$media_work->set_value('original_filename', $this->manager->get_value('original_filename'));
			$encoding_job = $this->shim->upload_video($url, $media_work, $user->get_value('name'), $remote_url);
			if (!$encoding_job)
			{
				if ($remote_url)
				{
					$this->manager->set_error('upload_url', 'Invalid url was provided.');
				}
				else
				{
					$this->manager->set_error('upload_file', 'There was an error during the upload process.');
				}
				return;
			}
		}
		elseif ($this->manager->get_value('av_type') == 'Audio')
		{
			
			$media_work->set_value('original_filename', $this->manager->get_value('original_filename'));
			$encoding_job = $this->shim->upload_audio($url, $media_work, $user->get_value('name'), $remote_url);
			if (!$encoding_job)
			{
				if ($remote_url)
				{
					$this->manager->set_error('upload_url', 'Invalid url was provided.');
				}
				else
				{
					$this->manager->set_error('upload_file', 'There was an error during the upload process.');
				}				return;
			}
		}
		else
		{
			$this->manager->set_error('av_type', 'The Media Type field is required.');
			return;
		}
	
		$this->_update_fields_with_file_data($encoding_job);
	}
	
	function _set_salt()
	{
		// make sure we only set the salt once ever
		if (!$this->manager->get_value('salt'))
		{
			$this->manager->set_value('salt', str_replace('.', '', uniqid('', true)));
		}
	}
	
	function _update_fields_with_file_data($encoding_job)
	{
		$this->manager->set_value('transcoding_status', 'converting');
		$this->manager->set_value('entry_id', $encoding_job->id);
		$this->manager->set_value('integration_library', 'zencoder');
		$this->manager->set_value('media_publication_datetime',date('Y-m-d H:i:s'));
	}
	
	/**
	 * The upload element is added to the form.
	 */
	function _add_file_upload_element()
	{
		if ($this->manager->manages_media && $this->manager->get_value('transcoding_status') != 'converting')
		{
			$authenticator = array("reason_username_has_access_to_site", $this->manager->get_value("site_id"));
			$params = array('authenticator' => $authenticator,
							'acceptable_extensions' => $this->recognized_extensions,
							'max_file_size' => reason_get_actual_max_upload_size(),
							'head_items' => &$this->manager->head_items);
			$this->manager->add_element( 'upload_file', 'ReasonUpload', $params);
			
			$this->manager->set_comments('upload_file',form_comment('If the file is on your computer, browse to it here.').form_comment('File must have one of the following extensions: .'.implode(', .', $this->recognized_extensions)).form_comment('<div class="maxUploadSizeNotice">Maximum file size for uploading is '.format_bytes_as_human_readable(reason_get_actual_max_upload_size()).'. </div>') );
			
			$this->manager->add_element('upload_url');
			$this->manager->add_comments('upload_url', form_comment('Or, you can place the media in any web-accessible location and paste its web address in here. <em>Tip: try pasting the address into another tab first, to make sure you have the address right!</em>'));
			
			if ($this->manager->get_value('transcoding_status') == 'ready')
			{
				$this->manager->set_display_name('upload_file', 'Upload Replacement File');
				$this->manager->set_display_name('upload_url', 'Upload Replacement Url');
			}
		}
	}	
	
	// moved into carl_util/basic/misc.php and renamed to reason_get_actual_max_upload_size
	// function _get_actual_max_upload_size()
	
	/**
	 * Cleans up a filename by removing all bad characters and replacing spaces with an underscore.
	 * @param $filename
	 * @return string
	 */
	function _sanitize_filename($filename)
	{
		$index = strrpos($filename, '.');
		$name = substr($filename, 0, $index);	
	
		// replace all reserved url characters in the name of the file
		$pattern = '/;|\/|\?|:|@|=|&|\.|#/';
		return str_replace(' ', '_', preg_replace($pattern, '', $name)).substr($filename, $index);
	}
}
?>
