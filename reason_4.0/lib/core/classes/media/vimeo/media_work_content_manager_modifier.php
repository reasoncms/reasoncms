<?php
require_once(SETTINGS_INC.'media_integration/vimeo_settings.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('classes/url_manager.php');
reason_include_once('function_libraries/url_utils.php');
reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once('classes/default_access.php');
reason_include_once('classes/media/interfaces/media_work_content_manager_modifier_interface.php');
reason_include_once('classes/media/vimeo/shim.php');
reason_include_once('classes/media/vimeo/displayer_chrome/size_switch.php');
reason_include_once('content_managers/image.php3');
include_once( CARL_UTIL_INC . 'basic/misc.php' );

/**
 * A class that modifies the given Media Work content manager for Vimeo-integrated 
 * Media Works.
 */
class VimeoMediaWorkContentManagerModifier implements MediaWorkContentManagerModifierInterface
{
	/**
	 * The disco form this modifier class will modify.
	 */
	protected $manager;
	
	protected $shim;
		
	protected $displayer_chrome;
	
	protected $original_entry_id;
	
	protected $recognized_extensions;
	
	function set_content_manager($manager)
	{
		$this->manager = $manager;
		$manager->recognized_extensions = array();
		$this->shim = new VimeoShim();
		
		if ( $manager->get_value('entry_id') )
		{
			$this->displayer_chrome = new VimeoSizeSwitchDisplayerChrome();
			$this->displayer_chrome->set_media_work(new entity($manager->get_value('id')));
		}
		$this->original_entry_id = $manager->get_value('entry_id');
		$this->recognized_extensions = VimeoShim::get_recognized_extensions();
	}
	
	/**
	 * Sets the required head_items in the content manager.
	 */
	public function set_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		if (VIMEO_UPLOADING_ENABLED)
		{
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/vimeo/media_work_content_manager.js');
		}
		else
		{
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/vimeo/media_work_content_manager_no_upload.js');
		}
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH .'media/vimeo/media_work_content_manager.css');
		if ($this->displayer_chrome)
			$this->displayer_chrome->set_head_items($head_items);
	}
	
	/**
	 * Add vimeo-integrated-specific fields to the disco form.  
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
		$this->manager->change_element_type('show_embed', 'protected');
		$this->manager->change_element_type('show_download', 'protected');
		$this->manager->change_element_type('salt', 'cloaked');
		$this->manager->change_element_type('original_filename', 'protected');
		
		$this->manager->form_enctype = 'multipart/form-data';
	
		$this->_add_restriction_selector();
		if (VIMEO_UPLOADING_ENABLED)
		{
			$this->_add_file_upload_element();
		}
		$this->_add_url_element();
		$this->_add_file_preview();
		$this->_add_embed_code();
		
		$this->do_basic_vimeo_field_modification();
	
		if (!empty($this->manager->fields_to_remove))
		{
			foreach ($this->manager->fields_to_remove as $field)
			{
				$this->manager->remove_element($field);
			}
		}
		
		$this->manager->set_order (array ('file_preview', 'name', 'upload_file', 'vimeo_url', 'email_notification', 'show_embed', 'embed_small', 'embed_medium', 'embed_large', 'download_links', 'link', 'replacement_header', 'file_info_header', 'av_type', 'description', 'keywords', 'datetime', 'author', 'content', 'transcript_status', 'rights_statement', 'media_duration', 'media_publication_datetime', 'access_header', 'show_hide', 'restricted_group', 'no_share'  ));
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
		$username = reason_check_authentication();
		reason_update_entity($this->manager->get_value('id'), get_user_id($username), array('media_publication_datetime' => date('Y-m-d H:i:s')), false);
	
		if ($this->manager->get_value('vimeo_url') && $this->manager->get_value('entry_id') != $this->original_entry_id)
		{
			// create image file in the vimeo temp directory
			$tmp_path = VimeoShim::get_temp_dir().'tmp_thumbnail_'.$this->manager->get_value('id');
			$f = fopen($tmp_path, 'w');
			$image_url = $this->shim->get_thumbnail($this->manager->get_value('entry_id'));
			$contents = get_reason_url_contents($image_url);
			fwrite($f, $contents);
			fclose($f);
			
			// Create a reason entity out of the temp image file
			if( !empty($tmp_path) AND file_exists( $tmp_path) && $username)
			{
				if ($id = $this->create_image_entity($username))
				{
					$im = new ImageManager();
					$im->thumbnail_width = REASON_STANDARD_MAX_THUMBNAIL_WIDTH;
					$im->thumbnail_height = REASON_STANDARD_MAX_THUMBNAIL_HEIGHT;
					$im->max_width = REASON_STANDARD_MAX_IMAGE_WIDTH;
					$im->max_height = REASON_STANDARD_MAX_IMAGE_HEIGHT;
					$im->load_by_type( id_of('image'), $id, get_user_id($username) );
					
					$im->handle_standard_image($id, $tmp_path);		
					$im->create_default_thumbnail($id);
					
					$values = array();
					foreach($im->get_element_names() as $element_name)
					{
						$values[ $element_name ] = $im->get_value($element_name);
					}
					
					reason_update_entity( $id, get_user_id($username), $values, false );
					
					// Remove any existing association with an image and replace it with this new one
					delete_relationships(array('entity_a' => $this->manager->get_value('id'), 'type' => relationship_id_of('av_to_primary_image')));
					
					create_relationship($this->manager->get_value('id'), $id, relationship_id_of('av_to_primary_image'));
				}
			}
			 
			// update the duration field of the media work.
			$data_obj = $this->shim->get_video_data($this->manager->get_value('entry_id'));
			if ($data_obj)
			{
				reason_update_entity($this->manager->get_value('id'), get_user_id($username), array('media_duration' => format_seconds_as_human_readable(intval($data_obj->duration))), false);
			}
		}			
	}
	
	function create_image_entity($username)
	{
		$name = $this->manager->get_value('name').' (Generated Thumbnail)';
		$values = array();
		$values['new'] = '0';
		$values['description'] = 'A thumbnail image for Vimeo media work '.$this->manager->get_value('name');
		$values['no_share'] = '0';
		
		$e = new entity($this->manager->get_value('id'));
		$site_id = $e->get_owner()->id();
		
		if ($username)
		{
			return reason_create_entity($site_id, id_of('image'), get_user_id($username), $name, $values);
		}
		return false;
	}
	
	/**
	 * The url element is added to the form, where users paste in vimeo urls.
	 */
	function _add_url_element()
	{
		if($this->manager->manages_media)
		{
			$this->manager->add_element( 'vimeo_url', 'text');
			
			if ($this->manager->get_value('entry_id'))
			{
				$this->manager->set_display_name('vimeo_url', 'New Vimeo Video URL');
				$this->manager->set_comments('vimeo_url',form_comment('Simply copy/paste the url or id of a Vimeo video here, if you would like to change the video.').form_comment('A Vimeo video id can be found at the end of Vimeo urls.  Example: http://vimeo.com/<strong>53753901</strong>'));
			}
			else
			{
				$this->manager->set_display_name('vimeo_url', 'Vimeo Video URL');
				$this->manager->set_comments('vimeo_url',form_comment('Simply copy/paste the url or id of a Vimeo video here.').form_comment('A Vimeo video id can be found at the end of Vimeo urls.  Example: http://vimeo.com/<strong>53753901</strong>'));
			}
		}
	}	
	
	/**
	 * Adds a preview of the media work to the form.
	 */
	function _add_file_preview()
	{
		if ($this->manager->get_value('transcoding_status') == 'ready')
		{
			$entity = new entity($this->manager->get_value('id'));
			$embed_markup = '';
			
			$this->displayer_chrome->set_media_height('small');
			$embed_markup .= $this->displayer_chrome->get_html_markup();
			
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
		if ($this->manager->get_value('entry_id'))
		{
			reason_include_once( 'classes/media/vimeo/media_work_displayer.php' );
			$displayer = new VimeoMediaWorkDisplayer();
			$entity = new entity($this->manager->get_value('id'));
			$displayer->set_media_work($entity);
			
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
	}
	
	/**
	 * Simple form manipulation happens in this method related to vimeo-integrated things.
	 */
	function do_basic_vimeo_field_modification()
	{
		$this->manager->change_element_type('av_type', 'protected');
		$this->manager->set_value('av_type', 'Video');
		
		if ( $this->manager->get_value('media_duration') ) 
			$this->manager->change_element_type('media_duration', 'solidtext');
		else 
			$this->manager->change_element_type('media_duration', 'protected');
		
		$this->manager->change_element_type('integration_library', 'protected');
		
		$this->manager->set_comments ('description', form_comment('(e.g. "A Tour of Northfield")'));
		
		$this->manager->add_element( 'file_info_header', 'comment', array('text'=>'<h4>Media Info</h4>'));
		$this->manager->add_element( 'access_header', 'comment', array('text'=>'<h4>Access and Sharing</h4>'));
		
		$this->manager->change_element_type('transcoding_status', 'protected');
		$this->manager->change_element_type('entry_id', 'protected');
		
		$this->manager->change_element_type('email_notification', 'checkbox');		
		$this->manager->set_display_name('email_notification', 'Email Alert');
		$this->manager->set_comments('email_notification', '<span class="smallText">Check this box to receive an email when your media finishes processing.</span>');
		if (!$this->manager->get_value('new')) 
		{
			$this->manager->set_value('email_notification', true);
		}
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
		
		$this->manager->change_element_type('show_embed', 'checkbox');
		if (!$this->manager->get_value('entry_id'))
		{
			$this->manager->set_value('show_embed', true);
		}
		$this->manager->change_element_type('show_download', 'protected');
		$this->manager->set_value('show_download', false);
	}
	
	/**
	 * Adds callback for run_error_checks() in the disco form.
	 */
	function run_error_checks() 
	{
		$this->manager->add_callback(array($this, '_run_error_checks_callback'), 'run_error_checks');
	}
	
	/**
	 * We need to make sure that the user either upload a file or linked to a vimeo video.
	 */
	function _run_error_checks_callback()
	{	
		if (!$this->manager->get_value('entry_id') && !($this->manager->get_value('vimeo_url') || $this->manager->get_value('upload_url') || ($this->manager->get_value('upload_file') && VIMEO_UPLOADING_ENABLED)) && !$this->manager->has_errors())
		{
			if (VIMEO_UPLOADING_ENABLED)
			{
				$this->manager->set_error('vimeo_url', 'Upload File or Vimeo URL required.');
			}
			else
			{
				$this->manager->set_error('vimeo_url', 'Vimeo URL required.');
			}
		}
	
		if (!$this->manager->has_errors())
		{
			if ($url = trim($this->manager->get_value('vimeo_url')))
			{
				$this->_handle_vimeo_url($url);
			}
			else // user uploaded a file
			{
				$this->_handle_uploaded_file();
			}
		}
		
		if ($this->manager->get_value('email_notification'))
			$this->manager->set_value('email_notification', true);
		if ($this->manager->get_value('show_embed'))
			$this->manager->set_value('show_embed', true);
	}
	
	/**
	 * Checks for errors with the given vimeo url
	 */
	function _handle_vimeo_url($url)
	{
		$this->manager->set_value('vimeo_url', $url);
		// check to see if the provided vimeo info is valid
		// regexp found here: http://stackoverflow.com/questions/10488943/easy-way-to-get-vimeo-id-from-a-vimeo-url
		$regexp = '#(https?:\/\/)?(www.)?(player.)?vimeo.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*#';
		preg_match($regexp, $url, $matches);
		
		if (count($matches) >= 6)
		{
			$valid_url = $this->_check_valid_url($matches[5]);
		}
		else
		{
			$valid_url = false;
		}
		$valid_key = $this->_check_valid_key($url);
		
		if (!($valid_key || $valid_url))
		{	
			if (strpos($url, 'vimeo') === false)
			{
				$this->manager->set_error('vimeo_url', 'Invalid Vimeo video key');
			}
			else
			{
				$this->manager->set_error('vimeo_url', 'Invalid Vimeo url');
			}
			return;
		}

		if ($valid_url)
		{
			if ($matches[5])
			{
				$video_key = $matches[5];
				if (!is_numeric($video_key)) 
				{
					$this->manager->set_error('vimeo_url', 'Invalid video key detected.');
				}
				else
				{
					$this->manager->set_value('entry_id', $video_key);
				}
			}
			else
			{
				$this->manager->set_error('vimeo_url', 'Invalid Vimeo url');
			}
		}
		else
		{
			$this->manager->set_value('entry_id', $url);
		}
	}
	
	/**
	 * Handles uploading to Vimeo
	 */
	function _handle_uploaded_file()
	{
		if (!$this->manager->has_error('upload_file') && !$this->manager->has_error('upload_url'))
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
				$file = $this->manager->get_element( 'upload_file' );
				if( ($file->state == 'received' OR $file->state == 'pending') AND file_exists( $file->tmp_full_path ) )
				{
					$user = new entity( $this->manager->admin_page->authenticated_user_id );
					$filePath = $file->tmp_full_path;
					$filename_parts = explode('/', $filePath);
					$filename = end($filename_parts);
					$this->manager->set_value('tmp_file_name', $filename);
					
					// convert filePath to web accessible url
					$filePath = carl_construct_link(array(), array(), WEB_TEMP.$filename);
					$this->_process_work($filePath, $file->tmp_full_path, false);
				}
			}
			elseif ($this->manager->get_value('upload_url'))
			{				
				$this->_process_work($this->manager->get_value('upload_url'), '', true);
			}
		}
	}
	
	/**
	 * Uploads the media work to Vimeo
	 */
	function _process_work($filepath, $tmp_full_path, $remote_url = false)
	{	
		$user = new entity( $this->manager->admin_page->authenticated_user_id );
		$entry = $this->shim->upload_video($filepath, $this->manager, $user->get_value('name'), $remote_url);
		if ($entry)
		{
			$video_id = $entry->id;
		}
		else
		{
			$video_id = false;
		}
		
		if ( !$remote_url )
		{
			unlink($tmp_full_path);
		}
		
		if (!$video_id)
		{
			if ($remote_url)
			{
				$this->manager->set_error('upload_url', 'There was an error during the upload process.');
			}
			else
			{
				$this->manager->set_error('upload_file', 'There was an error during the upload process.');
			}
			return;
		}	
		$this->_update_fields_with_file_data($video_id, $remote_url);
	}
	
	function _update_fields_with_file_data($video_id, $remote_url)
 	{
 		$this->remove_existing_video($this->manager->get_value('entry_id'));
 		$this->manager->set_value('transcoding_status', 'converting');
 		$this->manager->set_value('entry_id', $video_id);
 		$this->manager->set_value('integration_library', 'vimeo');
 		$this->manager->set_value('media_publication_datetime',date('Y-m-d H:i:s'));
 		// update the duration field of the media work.
		$data_obj = $this->shim->get_video_data($this->manager->get_value('entry_id'));
		if ($data_obj)
		{
			$this->manager->set_value('media_duration', format_seconds_as_human_readable(intval($data_obj->duration)));
		}
		if ($remote_url)
		{
			$this->manager->set_value('tmp_file_name', '');
		}
 	}
 	
 	function remove_existing_video($video_id)
 	{
 		$vimeo = new phpVimeo(VIMEO_CLIENT_ID, VIMEO_CLIENT_SECRET, VIMEO_ACCESS_TOKEN, VIMEO_ACCESS_TOKEN_SECRET);
 		try
		{
			$vimeo->call('vimeo.videos.delete', array('video_id' => $video_id));
		}
		catch (VimeoAPIException $e) 
		{}
 	}
	
	/**
	 * Checks to see if the provided url is a valid url.
	 */
	function _check_valid_url($url)
	{
		return $this->_check_valid_key($url);
	}
	
	/**
	 * Checks to see if the provided key is a valid Vimeo video key
	 */
	function _check_valid_key($key)
	{
		$data = $this->shim->get_video_data($key);
		if (!$data)
		{
			return false;
		}
		return true;
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
							'max_file_size' => $this->_get_actual_max_upload_size(),
							'head_items' => &$this->manager->head_items);
			$this->manager->add_element( 'upload_file', 'ReasonUpload', $params);
			
			$this->manager->set_comments('upload_file',form_comment('If the file is on your computer, browse to it here.').form_comment('File must have one of the following extensions: .'.implode(', .', $this->recognized_extensions)).form_comment('<div class="maxUploadSizeNotice">Maximum file size for uploading is '.format_bytes_as_human_readable($this->_get_actual_max_upload_size()).'. </div>') );
			
			$this->manager->add_element('upload_url');
			$this->manager->add_comments('upload_url', form_comment('Or, you can place the media in any web-accessible location and paste its web address in here. <em>Tip: try pasting the address into another tab first, to make sure you have the address right!</em>'));
			
			if ($this->manager->get_value('transcoding_status') == 'ready')
			{
				$this->manager->set_display_name('upload_file', 'Upload Replacement File');
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
}
?>