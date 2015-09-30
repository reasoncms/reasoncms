<?php
reason_include_once('classes/plasmature/upload.php');
reason_include_once('classes/url_manager.php');
reason_include_once('function_libraries/url_utils.php');
reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once('classes/default_access.php');
reason_include_once('classes/media/interfaces/media_work_content_manager_modifier_interface.php');
reason_include_once('classes/media/youtube/shim.php');
reason_include_once('classes/media/youtube/displayer_chrome/size_switch.php');
reason_include_once('content_managers/image.php3');
include_once( CARL_UTIL_INC . 'basic/misc.php' );

/**
 * A class that modifies the given Media Work content manager for YouTube-integrated 
 * Media Works.
 */
class YoutubeMediaWorkContentManagerModifier implements MediaWorkContentManagerModifierInterface
{
	/**
	 * The disco form this modifier class will modify.
	 */
	protected $manager;
	
	protected $shim;
		
	protected $displayer_chrome;
	
	protected $original_entry_id;
	
	function set_content_manager($manager)
	{
		$this->manager = $manager;
		$manager->recognized_extensions = array();
		$this->shim = new YoutubeShim();
		
		if ( $manager->get_value('entry_id') )
		{
			$this->displayer_chrome = new YoutubeSizeSwitchDisplayerChrome();
			$this->displayer_chrome->set_media_work(new entity($manager->get_value('id')));
		}
		$this->original_entry_id = $manager->get_value('entry_id');
	}
	
	/**
	 * Sets the required head_items in the content manager.
	 */
	public function set_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/youtube/media_work_content_manager.js');
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH .'media/youtube/media_work_content_manager.css');
		if ($this->displayer_chrome)
			$this->displayer_chrome->set_head_items($head_items);
	}
	
	/**
	 * Add youtube-integrated-specific fields to the disco form.  
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
		$this->manager->change_element_type('email_notification', 'protected');
		$this->manager->change_element_type('show_embed', 'protected');
		$this->manager->change_element_type('show_download', 'protected');
		$this->manager->change_element_type('salt', 'cloaked');
		$this->manager->change_element_type('original_filename', 'protected');
		
		$this->manager->form_enctype = 'multipart/form-data';
	
		$this->_add_restriction_selector();
		$this->_add_url_element();
		$this->_add_file_preview();
		$this->_add_embed_code();
		
		$this->do_basic_youtube_field_modification();
	
		if (!empty($this->manager->fields_to_remove))
		{
			foreach ($this->manager->fields_to_remove as $field)
			{
				$this->manager->remove_element($field);
			}
		}
		
		$this->manager->set_order (array ('file_preview', 'name', 'youtube_url', 'show_embed', 'embed_small', 'embed_medium', 'embed_large', 'download_links', 'link', 'replacement_header', 'file_info_header', 'av_type', 'description', 'keywords', 'datetime', 'author', 'content', 'transcript_status', 'rights_statement', 'media_duration', 'media_publication_datetime', 'access_header', 'show_hide', 'restricted_group', 'no_share'  ));
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
	
		if ($this->manager->get_value('youtube_url') && $this->manager->get_value('entry_id') != $this->original_entry_id)
		{
			// create image file in the youtube temp directory
			$tmp_path = YoutubeShim::get_temp_dir().'tmp_thumbnail_'.$this->manager->get_value('id');
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
			if (!empty($data_obj))
			{
				reason_update_entity($this->manager->get_value('id'), get_user_id($username), array('media_duration' => format_seconds_as_human_readable(intval($data_obj->length))), false);
			}
		}			
	}
	
	function create_image_entity($username)
	{
		$name = $this->manager->get_value('name').' (Generated Thumbnail)';
		$values = array();
		$values['new'] = '0';
		$values['description'] = 'A thumbnail image for YouTube media work '.$this->manager->get_value('name');
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
	 * The url element is added to the form, where users paste in youtube urls.
	 */
	function _add_url_element()
	{
		if($this->manager->manages_media)
		{
			$this->manager->add_element( 'youtube_url', 'text');
			if (!$this->manager->get_value('entry_id'))
				$this->manager->add_required('youtube_url');
			
			if ($this->manager->get_value('entry_id'))
			{
				$this->manager->set_display_name('youtube_url', 'New YouTube Video URL');
				$this->manager->set_comments('youtube_url',form_comment('Simply copy/paste the url or id of a YouTube video here, if you would like to change the video.').form_comment('A YouTube video id can be found at the end of YouTube urls.  Example: http://www.youtube.com/watch?v=<strong>FQor2NQwpZM</strong>'));
			}
			else
			{
				$this->manager->set_display_name('youtube_url', 'YouTube Video URL');
				$this->manager->set_comments('youtube_url',form_comment('Simply copy/paste the url or id of a YouTube video here.').form_comment('A YouTube video id can be found at the end of YouTube urls.  Example: http://www.youtube.com/watch?v=<strong>FQor2NQwpZM</strong>'));
			}
		}
	}	
	
	/**
	 * Adds a preview of the media work to the form.
	 */
	function _add_file_preview()
	{
		if ($this->manager->get_value('entry_id'))
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
			reason_include_once( 'classes/media/youtube/media_work_displayer.php' );
			$displayer = new YoutubeMediaWorkDisplayer();
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
	 * Simple form manipulation happens in this method related to youtube-integrated things.
	 */
	function do_basic_youtube_field_modification()
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
		$this->manager->set_value('transcoding_status', 'ready');
		$this->manager->change_element_type('entry_id', 'protected');
		
		$this->manager->change_element_type('email_notification', 'protected');
		$this->manager->set_value('email_notification', false);
		
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
	 * Checks for errors with the provided YouTube url.
	 */
	function _run_error_checks_callback()
	{	
		$url = trim($this->manager->get_value('youtube_url'));
		$this->manager->set_value('youtube_url', $url);
		if ($url)
		{
			// check to see if the provided youtube info is valid
			$valid_url = $this->_check_valid_url($url);
			$valid_key = $this->_check_valid_key($url);
			
			if (!($valid_key || $valid_url))
			{
				if (strpos($url, 'youtube') === false)
				{
					$this->manager->set_error('youtube_url', 'Invalid YouTube video key');
				}
				else
				{
					$this->manager->set_error('youtube_url', 'Invalid YouTube url');
				}
				return;
			}
	
			if ($valid_url)
			{	
				// parse out the video id from the url and save it
				// this regular expression is borrowed from: http://stackoverflow.com/questions/3452546/javascript-regex-how-to-get-youtube-video-id-from-url
				$regexp = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/';
				preg_match($regexp, $url, $matches);
				if (count($matches) >= 3)
				{
					$video_key = $matches[2];
					if (strlen($video_key) != 11) 
					{
						$this->manager->set_error('youtube_url', 'Invalid video key detected.');
					}
					else
					{
						$this->manager->set_value('entry_id', $video_key);
					}
				}
				else
				{
					$this->manager->set_error('youtube_url', 'Invalid YouTube url');
				}
			}
			else
			{
				$this->manager->set_value('entry_id', $url);
			}
		}
		if ($this->manager->get_value('show_embed'))
			$this->manager->set_value('show_embed', true);
	}
	
	/**
	 * Checks to see if the provided url is a valid url.
	 */
	function _check_valid_url($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($http_code != 200)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Checks to see if the provided key is a valid YouTube video key
	 */
	function _check_valid_key($key)
	{
		return $this->_check_valid_url('http://www.youtube.com/watch?v='.$key);
	}
}
?>