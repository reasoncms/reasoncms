<?php

/**
 * Content manager for Audio/Video/Multimedia Files
 * @package reason
 * @subpackage content_managers
 *
 * @todo Restrict file type to the extensions indicated ... right now .jpgs can be uploaded.
 * @todo make sure errors are properly handled
 * @todo make sure junk is not left behind in tmp directory
 * @todo add ability to store media on same server as Reason (e.g. just move file rather than ssh transferring it somewhere)
 * @todo remove constants from this file and move into Reason settings -- this belt-and-suspenders approach is just too confusing.
 */

/**
 * Include dependencies
 */
reason_include_once('classes/url_manager.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('content_managers/default.php3');
reason_include_once('function_libraries/url_utils.php');
reason_include_once( 'function_libraries/image_tools.php' );
include_once(CARL_UTIL_INC . 'basic/mime_types.php');

/**
 * Define the class name so that the admin page can use this content manager
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'avFileManager';

 /**
  * Make sure constants are defined before progressing
  */
if(!defined('REASON_AV_TRANSFER_UTILITY_LOCATION')) define('REASON_AV_TRANSFER_UTILITY_LOCATION','');
if(!defined('REASON_AV_TRANSFER_UTILITY_CLASS_NAME')) define('REASON_AV_TRANSFER_UTILITY_CLASS_NAME','');
if(!defined('REASON_MANAGES_MEDIA')) define('REASON_MANAGES_MEDIA',false);
if(!defined('NOTIFY_WHEN_MEDIA_IS_IMPORTED')) define('NOTIFY_WHEN_MEDIA_IS_IMPORTED',false);
if(!defined('MEDIA_FILESIZE_NOTIFICATION_THRESHOLD')) define('MEDIA_FILESIZE_NOTIFICATION_THRESHOLD',0);
if(!defined('MEDIA_ALLOW_DIRECT_UPLOAD')) define('MEDIA_ALLOW_DIRECT_UPLOAD',true);
if(!defined('MEDIA_ALLOW_IMPORT_FROM_FILESYSTEM')) define('MEDIA_ALLOW_IMPORT_FROM_FILESYSTEM',false);
if(!defined('MEDIA_MAX_UPLOAD_FILESIZE_MEGS')) define('MEDIA_MAX_UPLOAD_FILESIZE_MEGS',50);

/**
 * Include the file identified in the settings as being the file transfer handler
 * constant name: REASON_AV_TRANSFER_UTILITY_LOCATION
 */
if(defined('REASON_AV_TRANSFER_UTILITY_LOCATION') && REASON_AV_TRANSFER_UTILITY_LOCATION != '')
{
	reason_include_once(REASON_AV_TRANSFER_UTILITY_LOCATION);
}

/**
 * Content manager for Audio/Video/Multimedia Files
 *
 * Handles importing of media if Reason is set up for it
 *
 * @author Matt Ryan mryan@carleton.edu
 * @author Nate White nwhite@carleton.edu
 */
class avFileManager extends ContentManager
{
	/**
	 * Extensions that are allowed to be imported and managed by Reason
	 * @var array
	 */
	var $recognized_extensions = array('mp3','rm','mp4','mov','swf','wmv','wma','mpg','mpeg','aiff','wav','avi','flv','m4v');

	/**
	 * Mime types that are allowed to be imported and managed by Reason - this list will be merged with mime types dynamically
	 * generated from the recognized_extensions ... so ... if your mime.types file is setup this may not be needed ... we include
	 * it for the default types for the most complete functionality out of the box.
	 */
	var $acceptable_mime_types = array('video/mp4', 'video/x-flv', 'video/x-m4v', 'video/x-ms-wmv', 'audio/x-ms-wma', 'audio/mpeg', 'application/vnd.rn-realmedia', 'video/quicktime', 'application/x-shockwave-flash', 'video/mpeg', 'audio/x-aiff', 'audio/x-wav', 'video/x-msvideo');

	/**
	 * The delivery methods available to the users
	 * This array is used to generate the checkbox group element, so the values are the labels on the checkboxes
	 * @var array
	 */
	var $media_delivery_methods = array('media_is_progressively_downloadable'=>'Progressive Download<div class="formComment smallText">(If in doubt, use this choice. This is usually the best choice for media shorter than 15 min. This method allows visitors to play the file when they are not connected to the internet, a la podcasting.)</div>','media_is_streamed'=>'Streaming<div class="formComment smallText">(Usually the best choice for media longer than 15 min. Often files need to be specially encoded to take advantage of streaming. Visitors must be connected to the internet to view streamed files.)</div>');

	/**
	 * The name of the class used to transfer files
	 * This is taken from the setting REASON_AV_TRANSFER_UTILITY_CLASS_NAME
	 * @var string
	 */
	var $file_transfer_utility_class = REASON_AV_TRANSFER_UTILITY_CLASS_NAME;
	
	/**
	 * The content manager uses logic to figure out if it has all the information
	 * it needs to manage the media (e.g. is Reason set to manage the media,
	 * do we have a class to do it, does the class exist)
	 * If those conditions are met the content manager changes the value of
	 * this variable to true.
	 * @var bool
	 */
	var $manages_media = false;
	
	/**
	 * Stores the md5 hash of the file
	 * This is a class variable so we don't need to pipe it 
	 * around to everything that might want to use it
	 * @var string
	 */
	var $checksum = '';

	/**
	 * Add the necessary javascript
	 */
	function init_head_items()
	{
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/media_file_content_manager.js');
	}

	/**
	 * Most of the work in setting up the form happens here
	 * This function has been broken up into several sub-functions for ease of reading and modification:
	 * prefill_name(), massage_delivery_method_elements(), do_basic_field_modification(), add_file_import_selector(), modify_url_field(), and add_file_preview().
	 * 
	 */
	function alter_data()
	{
		if(REASON_MANAGES_MEDIA && !empty($this->file_transfer_utility_class) && class_exists($this->file_transfer_utility_class))
		{
			$this->manages_media = true;
		}
		if(MEDIA_ALLOW_DIRECT_UPLOAD)
		{
			$this->form_enctype = 'multipart/form-data';
		}
		$this->prefill_name();
		$this->massage_delivery_method_elements();
		$this->do_basic_field_modification();
		if(MEDIA_ALLOW_DIRECT_UPLOAD) $this->add_file_upload_element();
		if(MEDIA_ALLOW_IMPORT_FROM_FILESYSTEM) $this->add_file_import_selector();
		$this->modify_url_field();
		$this->add_file_preview();
		$this->add_parts_toggler();
		if ($this->get_element('caption_url') && $this->get_element('audio_description_url'))
		{
			$this->modify_caption_url_field();
			$this->modify_audio_description_field();
		}
		else
		{
			$script_link = carl_construct_link(array(''), array(''), REASON_HTTP_BASE_PATH . 'scripts/upgrade/4.0b7_to_4.0b8/update_types.php');
			trigger_error('The media file type is missing fields for captioning and audio description. Please run the Reason 4 Beta 8 upgrade types script ('.$script_link.').');
		}
		$this->set_order (array ( 'file_preview', 'name', 'link', 'replacement_header', 'upload_file','import_file_as_alternate','import_file', 'url', 'caption_url', 'audio_description_url', 'file_info_header', 'delivery_methods', 'default_media_delivery_method', 'media_format', 'av_type', 'media_duration', 'media_size','media_quality', 'width', 'height','parts_hr_1','parts','is_part','av_part_number', 'av_part_total', 'description','parts_hr_2', ));
	}
	
	function modify_caption_url_field()
	{
		$this->change_element_type('caption_url', 'hidden');
	}
	
	function modify_audio_description_field()
	{
		$this->change_element_type('audio_description_url', 'hidden');
	}
	
	function get_actual_max_upload_size()
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
	 * Simple form manipulation happens in this method
	 * Things like setting of comments, display names, requiredness, etc.
	 */
	function do_basic_field_modification()
	{
		$this -> set_comments ('name', form_comment('For internal use (this will not be used on the public site)'));
		$this -> add_required ('media_format');
		$this -> add_required ('av_type');
		$this -> set_display_name ('av_type', 'Media Type');
		$this->change_element_type( 'av_type', 'radio_no_sort', array('options'=>array('Audio'=>'Audio <span class="smallText formComment">(e.g. sound-only music, speech, etc.)</span>','Video'=>'Video <span class="smallText formComment">(e.g. movies, videos, etc.)</span>','Panorama'=>'Panorama <span class="smallText formComment">(e.g. 360 degree still images)</span>','Interactive'=>'Interactive <span class="smallText formComment">(e.g. game, navigable data, etc.)</span>')));
		$this -> set_display_name ('media_duration', 'Length of A/V Item');
		$this -> set_display_name ('media_size', 'File Size');
		$this -> set_display_name ('media_quality', 'Quality');
		$this -> set_comments ('media_duration', form_comment('The duration in time of the audio or video clip. If you are importing a Quicktime file, this will be automatically determined; otherwise, open the file in your media player to find this info.'));
		$this -> set_comments ('media_quality', form_comment('Use this field to indicate either the general quality level of the file (e.g. low/medium/high) or the specific quality-related specs of the file (e.g. "128 kbps" or "16 bit/44.1 khz".)'));
		$this -> set_comments ('media_size', form_comment('The size, in Kb or Mb, of the A/V file. If you are importing a file, this will be automatically determined.'));
		$this -> set_comments ('height', '<span class="smallText"> pixels (Not needed for audio files)</span><div class="smallText formComment">To determine the width and height of a file, open it in a player and find "get movie info," "clip info," or a similar menu option.</div>');
		$this->change_element_type( 'height', 'text', array('size'=>3));
		$this -> set_comments ('width', '<span class="smallText"> pixels (Not needed for audio files)</span>');
		$this->change_element_type( 'width', 'text', array('size'=>3));
		$this->change_element_type( 'reason_managed_media', 'hidden');
		
		$this->change_element_type( 'media_format', 'select_no_sort', array('options'=>array('Quicktime'=>'Quicktime (.mov, .m4v, .m4a, .mpeg, .mpg, .mp4, .aiff)','Windows Media'=>'Windows Media (.avi, .wav, .wma, .wmv)','Real'=>'Real (.rm, .ra)','Flash'=>'Flash Multimedia (.swf)','Flash Video'=>'Flash Video (.flv)','MP3'=>'mp3'),'display_name'=>'Format'));

		$this -> set_display_name ('av_part_number', 'Part Number');
		$this -> set_display_name ('av_part_total', 'Of');
		$this->change_element_type( 'keywords', 'hidden');
		$this->change_element_type( 'media_size_in_bytes', 'hidden');
		$this->change_element_type( 'description', 'text');
		$this->change_element_type( 'av_part_number', 'text', array('size'=>3));
		$this->change_element_type( 'av_part_total', 'text', array('size'=>3));
		$this -> add_comments ('av_part_number', '<span class="smallText"> (e.g. 2)</span>');
		$this -> add_comments ('av_part_total', '<span class="smallText"> (e.g. 5)</span>');
		$this -> set_display_name ('description', 'Part Description');
		$this -> add_comments ('description', form_comment('(e.g. "A Tour of Northfield")'));
		$this -> set_display_name ('default_media_delivery_method', 'Default Delivery Method');
		$this->add_element( 'parts_hr_1' , 'hr' );
		$this->add_element( 'parts_hr_2' , 'hr' );
		$this->add_element( 'file_info_header', 'comment', array('text'=>'<h4>File info</h4>'));
	}
	/**
	 * Creates the object/embed code to provide a in-page preview of the file
	 * And adds the element which contains said markup
	 */
	function add_file_preview()
	{
		if($this->get_value('url'))
		{
			reason_include_once( 'classes/av_display.php' );
			$avd = new reasonAVDisplay();
			$avd->disable_automatic_play_start();
			$entity = new entity($this->get_value('id'));
			if($image_info = reason_get_media_placard_image_info($entity))
			{
				$avd->set_placard_image($image_info['url']);
				$avd->set_placard_image_dimensions($image_info['width'], $image_info['height']);
			}
			$embed_markup = $avd->get_embedding_markup($this->get_value('id'));
			$problem_note = form_comment('If this preview is not working, please make sure you have selected the appropriate delivery method(s) and format for this file.');
			if(!empty($embed_markup))
			{
				$this->add_element( 'file_preview' , 'commentWithLabel' , array('text'=>$embed_markup.$problem_note) );
			}
		}
	}
	
	/**
	 * Automatically fills in the name of the a/v item this file is being created within
	 * This uses the request, which is probably not ideal, but I think at this point
	 * no relationships have yet been created in the DB
	 */
	function prefill_name()
	{
		if(!$this->get_value('name') && !empty($this->admin_page->request['__old_id']))
		{
			$avid = turn_into_int($this->admin_page->request['__old_id']);
			$av = new entity($avid);
			if($av->get_value('name'))
			{
				$this->set_value('name',$av->get_value('name'));
			}
		}
	}
	
	function add_parts_toggler()
	{
		$this->add_element('parts', 'comment', array('text'=>'<strong>Is this file one of several 
sequential parts</strong> that make up a media work -- like "part 3 of 7?"'));
		$this->add_element( 'is_part' , 'select_no_sort' , 
array('options'=>array('yes'=>'Yes','no'=>'No'),'display_name'=>'&nbsp;'), 'no' );
		if(!$this->get_value('av_part_number') && !$this->get_value('av_part_total') && !$this->get_value('description'))
		{
			$this->set_value('is_part','no');
		}
		else
		{
			$this->set_value('is_part','yes');
		}
	}
	
	/**
	 * Copies data from boolean fields to a newly created 
	 * delivery_methods checkboxgroup element
	 * we're doing this because the checkboxgroup is much nicer 
	 * in terms of labels & requiredness checking
	 * This is admittedly odd. it is really due to limitations in disco,
	 * and it would be better if we could stop doing this.
	 */
	function massage_delivery_method_elements()
	{
		$delivery_methods_vals = array();
		foreach($this->media_delivery_methods as $key=>$value)
		{
			if($this->get_value($key) == 'true')
			{
				$delivery_methods_vals[]=$key;
			}
		}
		if($this->get_value('url'))
		{
			if($this->get_value('reason_managed_media'))
			{
				if($this->get_value('media_is_progressively_downloadable') == 'true')
				{
					$http_url = alter_protocol($this->get_value('url'),'rtsp','http');
					$this->media_delivery_methods['media_is_progressively_downloadable'] = $this->media_delivery_methods['media_is_progressively_downloadable'].'<div class="smallText">Link: <a href="'.$http_url.'" title="Progressive download direct link">'.$http_url.'</a></div>';
				}
				if($this->get_value('media_is_streamed') == 'true')
				{
					$rtsp_url = alter_protocol($this->get_value('url'),'http','rtsp');
					$this->media_delivery_methods['media_is_streamed'] = $this->media_delivery_methods['media_is_streamed'].'<div class="smallText">Link: <a href="'.$rtsp_url.'" title="Streaming direct link">'.$rtsp_url.'</a></div>';
				}
			}
			/* else
			{
				$this->add_element('link','commentWithLabel',array('text'=>'<a href="'.$this->get_value('url').'">'.$this->get_value('url').'</a>') );
			} */
		}
		
		if($this->manages_media || $this->get_value('reason_managed_media'))
		{
			$this-> add_element( 'delivery_methods', 'checkboxGroup', array('options'=>$this->media_delivery_methods, 'display_name'=>'Delivery Method(s)' ) );
			$this->set_value('delivery_methods',$delivery_methods_vals);
		}
		else
		{
			$this-> add_element( 'delivery_methods', 'radio', array('options'=>$this->media_delivery_methods, 'display_name'=>'Delivery Method' ) );
			reset($delivery_methods_vals);
			$this->set_value('delivery_methods',current($delivery_methods_vals));
			$this->change_element_type( 'default_media_delivery_method','hidden');
		}
		$this->add_required('delivery_methods');
		$this->change_element_type( 'media_is_progressively_downloadable', 'hidden');
		$this->change_element_type( 'media_is_streamed', 'hidden');
	}
	
	/**
	 * Creates the select element so users can choose a file for import
	 */
	function add_file_import_selector()
	{
		if($this->manages_media)
		{
			$media_files = $this->grab_media_files_for_import();
			if(empty($media_files))
			{
				$media_files = array();
			}
			if($this->get_value('reason_managed_media'))
			{
				$this->add_element('import_file','select',array('options'=>$media_files,'display_name'=>'Replace file'));
			}
			else
			{
				$this->add_element( 'import_file', 'select_js', 
				array(	'options' => $media_files, 
						'display_name'=>'Import a file',
						'script_url' => REASON_HTTP_BASE_PATH.'js/media_file_content_manager.js',
					) );
			}
			
			if(!empty($media_files))
			{
				$this -> add_comments ('import_file', form_comment('Media files in your "home/webpub/reason_import_only" folder. To update the list of available files, add or remove files from your "home/webpub/reason_import_only" folder and refresh this page.'));
			}
			else
			{
				$this -> add_comments ('import_file', form_comment('To import a media file, create a folder named "reason_import_only" in your home/webpub directory, place the file in it, and then refresh this page.'));
			}
			if(MEDIA_ALLOW_DIRECT_UPLOAD)
			{
				$this->add_element('import_file_as_alternate', 'comment', array('text'=>'If your file is larger than '.format_bytes_as_human_readable($this->get_actual_max_upload_size()).', you can import the file from your home/webpub/reason_import_only folder. <p style="color: red">NOTE - this import folder (home/webpub/reason_import_only) is a new location - Reason no longer looks for media in the streaming_media folder.</p>'));
			}
		}
	}
	
	function add_file_upload_element()
	{
		if($this->manages_media)
		{
			$authenticator = array("reason_username_has_access_to_site", $this->get_value("site_id"));
			$params = array('authenticator' => $authenticator,
							'acceptable_types' => $this->get_acceptable_mime_types(),
							'max_file_size' => $this->get_actual_max_upload_size(),
							'head_items' => &$this->head_items);
			$this->add_element( 'upload_file', 'ReasonUpload', $params);
			$this->add_comments('upload_file',form_comment('If the file is on your computer, browse to it here.') );
			$this->add_comments('upload_file',form_comment('Maximum file size for uploading is '.format_bytes_as_human_readable($this->get_actual_max_upload_size())));
			$this->add_comments('upload_file',form_comment('File must have one of the following extensions: .'.implode(', .', $this->recognized_extensions) ) );
		}
	}
	
	function get_acceptable_mime_types()
	{
		static $mime_types_from_extension;
		if (!isset($mime_types_from_extension))
		{
			foreach ($this->recognized_extensions as $extension)
			{
				$mime_type = mime_type_from_extension($extension);
				if (!empty($mime_type)) $mime_types[$mime_type] = $mime_type;
			}
			$mime_types_from_extension = (isset($mime_types)) ? array_values($mime_types) : array();
		}
		$acceptable_mime_types = array_merge($this->acceptable_mime_types, $mime_types_from_extension);
		return array_unique($acceptable_mime_types);
	}
	
	/**
	 * Either hides or changes the label of the URL field, depending on conditions
	 * URL field should not be editable if media is Reason-managed
	 */
	function modify_url_field()
	{
		if($this->manages_media)
		{
			if($this->get_value('reason_managed_media'))
			{
				$this->change_element_type('url','hidden');
			}
			if($this->get_value('url'))
			{
				if($this->_is_element( 'upload_file' ))
					$this -> set_display_name ('upload_file', 'Upload replacement file');
				if($this->_is_element( 'import_file' ))
					$this -> set_display_name ('import_file', 'Import replacement file');
				$this->add_element('replacement_header','comment',array('text'=>'<h4>Replace file</h4>'));
			}
			else
			{
				$this->set_display_name('url','or provide the address of a file already on the web');
				$this->add_element('replacement_header','comment',array('text'=>'<h4>File location</h4>'));
			}
		}
	}
	
	/**
	 * Uses the file transfer utility class to grab a list of filenames
	 * Checks against the recognized extensions to assemble our list of media files
	 * @return array keys = values = filenames
	 */
	function grab_media_files_for_import()
	{
		if($this->manages_media)
		{
			$user = new entity( $this->admin_page->user_id );
			$stream = new $this->file_transfer_utility_class( $user->get_value('name') );
			$files = $stream->view_available_files();
			
			if(!empty($files))
			{
				$media_files = array();
				foreach($files as $file)
				{
					if(strpos($file,'.')) // exclude files with no extension or which start with a dot (e.g. hidden files)
					{
						$extension = $this->get_extension($file);
						if(in_array($extension, $this->recognized_extensions))
						{
							$media_files[$file] = $file;
						}
					}
				}
				return $media_files;
			}
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Determines the extension from a string
	 * Won't work if there are query strings or if there is no extension
	 * So this method would need to be more robust to be generalized
	 * @param string $filename
	 * @return string $extension
	 */
	function get_extension($filename)
	{
		return trim(strtolower(str_replace('.','',strrchr($filename,'.'))));
	}
	
	/**
	 * Gets the xml metadata so it can be stored with the file in reason-managed mode
	 * This method grabs the appropriate entitites
	 * and then uses build_xml_for_av() to actually generate the XML
	 * @return string XML representation of the av_file entitiy and its related av item
	 */
	function get_meta_info()
	{
		$e = new entity($this->get_value('id'));
		$av = $e->get_right_relationship( 'av_to_av_file' );
		if(!empty($av))
		{
			$entities = array( current($av), $e);
		}
		else
		{
			$entities = array( $e );
		}
		return $this->build_xml_for_av( $entities );
	}
	
	/**
	 * Generates the xml metadata
	 * This method takes the entities assembled by get_meta_info(0 and uses the reason_xml_export class to generate the xml.
	 * @param array $entities The entities to be represented in XML
	 * @return string XML representation of given entities
	 */
	function build_xml_for_av($entities)
	{
		reason_include_once( 'classes/xml_export.php' );
		$exp = new reason_xml_export();
		return $exp->get_xml($entities);
	}
	
	/**
	 * Provides the protocol to use for the URL stored in reason
	 * If both protocols are selected http:// will be used and rtsp links will be generated on the fly
	 * @return string either http or rtsp
	 */
	function get_protocol()
	{
		if($this->get_value('default_media_delivery_method') == 'streaming')
		{
			return 'rtsp';
		}
		else
		{
			return 'http';
		}
	}
	
	
	function check_url_sanity()
	{
		if(!$this->get_value('reason_managed_media') && $this->get_value('url'))
		{
			$progressive_xtra_text = '';
			if($this->get_value('media_is_streamed') != 'true')
			{
				$progressive_xtra_text = ' and check "Streaming"';
			}
			$progressive_error_conditions = array('rtsp'=>'Web addresses that start with "rtsp" can only be delivered using the "streaming" method. <strong>Uncheck "Progressive Download"'.$progressive_xtra_text.' to continue.</strong>',
								'ram'=>'Web addresses that end with ".ram" are pointers to streaming content, so files posted this way can only be delivered using the "streaming" method. <strong>Uncheck "Progressive Download"'.$progressive_xtra_text.' to continue.</strong>',
								'ramgen'=>'Web addresses that use the folder "ramgen" are pointers to streaming content, so files posted this way can only be delivered using the "streaming" method. <strong>Uncheck "Progressive Download"'.$progressive_xtra_text.' to continue.</strong>');
			$parsed = parse_url($this->get_value('url'));
			$state = '';
			if( $parsed['scheme'] == 'rtsp' )
			{
				$state = 'rtsp';
			}
			elseif( $parsed['scheme'] == 'http' && $this->get_extension($parsed['path']) == 'ram')
			{
				$state = 'ram';
			}
			elseif( $parsed['scheme'] == 'http' && strpos($parsed['path'],'/ramgen/') === 0 && $this->get_extension($parsed['path']) == 'rm' )
			{
				$state = 'ramgen';
			}
			if($this->get_value('media_is_progressively_downloadable') == 'true' && array_key_exists($state,$progressive_error_conditions))
			{
				$this->set_error('delivery_methods','<strong>URL does not support progressive downloading.</strong><br /> '.$progressive_error_conditions[$state]);
			}
		}
	}
	
	/**
	 * Make sure the delivery method(s) filled out make sense
	 */
	function check_delivery_method_sanity()
	 {
		if(!$this->get_value('reason_managed_media') && $this->get_value('media_is_streamed') == 'true' && $this->get_value('media_is_progressively_downloadable') == 'true' )
		{
			$this->set_error('delivery_methods','<p>If you provide an external URL rather than importing your file into Reason, <strong>please choose just one delivery method</strong> (<em>either</em> "Progressive Download" or "Streaming") that describes the way the file is delivered.</p><p><em>Hint:</em> if the web address starts with "rtsp," ends with ".ram," or contains the words "ramgen," the file is probably set up for streaming. Otherwise, the file is probably set up for progressive download.</p>');
		}
	 }
	
	/**
	 * copy data back from delivery_methods checkboxgroup to the individual boolean fields
	 * This is a workaround to make the user interface more pleasant
	 * and is the submit counterpart to the method massage_delivery_method_elements().
	 */
	function write_delivery_methods_to_fields()
	{
		if(!$this->has_error('delivery_methods'))
		{
			$delivery_methods_chosen = $this->get_value('delivery_methods');
			foreach($this->media_delivery_methods as $field=>$desc)
			{
					if(
						( is_array($delivery_methods_chosen) && in_array($field,$delivery_methods_chosen) )
						||
						( is_string($delivery_methods_chosen) && $field == $delivery_methods_chosen )
					)
					{
						$this->set_value($field, 'true');
					}
					else
					{
						$this->set_value($field,'false');
					}
			}
		}
	}
	
	/**
	 * Check to see if the file is a duplicate
	 * This currently only checks within the current site
	 * It would be nicer if it checked across all of Reason
	 * but we would need to make sure that the appropriate borrowing setup
	 * was in place, or it would lead to frustration
	 */
	function check_if_file_has_been_previously_imported($stream,$user)
	{
		$this->checksum = $this->get_checksum($stream);
		if(!empty($this->checksum))
		{
			$es = new entity_selector($this->get_value( 'site_id' ));
			$es->add_type(id_of('av_file'));
			$es->add_relation('media_md5_sum = "'.$this->checksum.'"');
			$es->add_relation('entity.id != '.$this->get_value('id'));
			$es->set_num(1);
			$conflicts = $es->run_one();
			if(!empty($conflicts))
			{
				$conflict = current( $conflicts );
				$this->set_error('import_file','This file has already been imported into Reason as <strong><a href="'.$conflict->get_value('url').'">'.$conflict->get_value('name').'</a></strong> (Reason ID: '.$conflict->id().')');
			}
		}
	}
	
	function get_checksum($stream)
	{
		return $stream->get_source_md5sum( $this->get_value('import_file') );
	}
	
	function set_default_delivery_method()
	{
		if($this->get_value('media_is_progressively_downloadable') == 'true' && $this->get_value('media_is_streamed') == 'true')
		{
			if(!$this->get_value('default_media_delivery_method'))
			{
				$this->set_value('default_media_delivery_method','progressive_download');
			}
		}
		elseif($this->get_value('media_is_progressively_downloadable') == 'true')
		{
			$this->set_value('default_media_delivery_method','progressive_download');
		}
		elseif($this->get_value('media_is_streamed') == 'true')
		{
			$this->set_value('default_media_delivery_method','streaming');
		}
	}
	
	/**
	 * Make sure that the parts fields are filled out in a way that makes sense
	 * If the "is_parts" selector is set to "no", this function clears out the parts fields
	 */
	function check_parts_input()
	{
		if($this->get_value('is_part') == 'yes')
		{
			if(!$this->get_value('description') && !$this->get_value('av_part_number') && !$this->get_value('av_part_total'))
			{
				$this->set_error('is_part','If this is a one of several sequential files that make up a media work, please enter the part number, total, and/or description');
			}
			elseif(!$this->get_value('av_part_number') && $this->get_value('av_part_total'))
			{
				$this->set_error('av_part_number','Please enter which part this file is.');
			}
		}
		elseif($this->get_value('is_part') == 'no')
		{
			$this->set_value('av_part_number',0);
			$this->set_value('av_part_total',0);
			$this->set_value('description','');
		}
	}
	
	/**
	 * Run the error checks
	 */
	function run_error_checks()
	{
		parent::run_error_checks();
		
		$this->write_delivery_methods_to_fields();
		
		$this->set_default_delivery_method();
		
		$this->check_parts_input();
		
		$file_uploaded = false;
		
		if($this->_is_element( 'upload_file' ))
		{
			$file = $this->get_element( 'upload_file' );
			if( ($file->state == 'received' OR $file->state == 'pending') AND file_exists( $file->tmp_full_path ) )
			{
				// name our file with the friendly uploaded file name
				$new_filename = sanitize_filename_for_web_hosting($file->file['name']);
				$new_path = dirname($file->tmp_full_path) . '/' . $new_filename;
				copy( $file->tmp_full_path, $new_path ); 
				$file_uploaded = true;
				$user = new entity( $this->admin_page->user_id );
				$stream = new $this->file_transfer_utility_class( $user->get_value('name'), $new_path );
				
				$this->check_if_file_has_been_previously_imported($stream,$user);
				
				if(!$this->_has_errors())
				{
					$this->transfer_uploaded_file($stream,$user,$new_path);
					unlink($file->tmp_full_path);
				}
				else
				{
					unlink($new_path);
				}
			}
			
		}
		if( !$file_uploaded && $this->get_value('import_file'))
		{
			$user = new entity( $this->admin_page->user_id );
			$stream = new $this->file_transfer_utility_class( $user->get_value('name') );
			
			$this->check_if_file_has_been_previously_imported($stream,$user);
			
			if(!$this->_has_errors())
			{
				$this->do_file_import($stream,$user);
			}
		}
		if( $this->get_value('reason_managed_media') && $this->get_value('delivery_methods') && $this->get_value('url') )
		{
			$this->do_protocol_switch();
		}
		if(!$this->get_value('reason_managed_media') && !$this->get_value('import_file'))
		{
			$this->check_delivery_method_sanity();
			$this->check_url_sanity();
		}
	}
	
	/**
	 * This function is to be run if the user has selected a file
	 * It grabs the file from wherever it is and moves it into Reason's file repository
	 * Most of the heavy lifting is done by the helper class specified in $this->file_transfer_utility_class
	 */
	function do_file_import($stream,$user)
	{
		if(!$stream->place_media($this->get_value('id'), $this->get_value('import_file')))
		{
			$this->set_error('import_file','File transfer wasn\'t successful. Reason: '.$stream->get_last_error() );
			trigger_error('File transfer wasn\'t successful. Reason: '.$stream->get_last_error());
		}
		else
		{
			$this->update_fields_with_file_data($stream);
		}
	}
	
	function transfer_uploaded_file($stream,$user,$local_temp_location)
	{
		if(!$stream->place_local_media($this->get_value('id')))
		{
			$this->set_error('upload_file','File transfer wasn\'t successful. Reason: '.$stream->get_last_error() );
			trigger_error('File transfer wasn\'t successful. Reason: '.$stream->get_last_error());
		}
		else
		{
			$this->update_fields_with_file_data($stream);
			if(!unlink($local_temp_location))
			{
				trigger_error('Unable to delete temp file at '.$local_temp_location);
			}
		}
	}
	
	function update_fields_with_file_data($stream)
	{
		$url = $this->get_protocol().'://'.$stream->get_media_url($this->get_value('id'));
		$this->set_value('url',$url);
		$this->set_value('reason_managed_media',1);
		$bytes = $stream->get_media_size_bytes($this->get_value('id'));
		if(!empty($bytes))
		{
			$this->set_value('media_size_in_bytes',$bytes);
			$this->set_value('media_size',format_bytes_as_human_readable( $bytes ));
		}
		if(!empty($this->checksum))
		{
			$this->set_value('media_md5_sum', $this->checksum);
		}
		$duration = $stream->get_media_duration($this->get_value('id'));
		if(!empty($duration))
		{
			$human_readable_duration = $this->get_human_readable_duration($duration);
			if(!empty($human_readable_duration))
			{
				$this->set_value('media_duration',$human_readable_duration);
			}
		}
		$this->send_email_notification();
	}
	// takes duration as 67.67s and returns 1 minute 18 seconds
	// this is apparently the standard(?) format for the exif duration field
	function get_human_readable_duration($duration)
	{
		$duration = trim($duration);
		//sanity check -- must have an s at the end
		if(!strpos($duration,'s'))
		{
			return null;
		}
		else
		{
			$duration = str_replace('s','',$duration);
		}
		list($seconds, $hundredths) = explode('.',$duration);
		$seconds = intval($seconds);
		$hundredths = intval($hundredths);
		if($hundredths >= 50)
		{
			$seconds++;
		}
		
		$days = floor($seconds/60/60/24);
		$hours = $seconds/60/60%24;
		$mins = $seconds/60%60;
		$secs = $seconds%60;
		
		$ret_array = array();
		
		if(!empty($days))
		{
			if($days == 1) $word = 'day';
			else $word = 'days';
			$ret_array[] = $days.' '.$word;
		}
		if(!empty($hours))
		{
			if($hours == 1) $word = 'hour';
			else $word = 'hours';
			$ret_array[] = $hours.' '.$word;
		}
		if(!empty($mins))
		{
			if($mins == 1) $word = 'minute';
			else $word = 'minutes';
			$ret_array[] = $mins.' '.$word;
		}
		if(!empty($secs))
		{
			if($secs == 1) $word = 'second';
			else $word = 'seconds';
			$ret_array[] = $secs.' '.$word;
		}
		
		return implode(' ',$ret_array);
	}
	
	/**
	 * Creates and sends the notification that a file has been imported
	 * Respects the following constants:
	 * NOTIFY_WHEN_MEDIA_IS_IMPORTED, MEDIA_FILESIZE_NOTIFICATION_THRESHOLD, MEDIA_NOTIFICATION_EMAIL_ADDRESSES
	 */
	function send_email_notification()
	{
		if(NOTIFY_WHEN_MEDIA_IS_IMPORTED && $this->get_value('media_size_in_bytes') >= MEDIA_FILESIZE_NOTIFICATION_THRESHOLD)
		{
			if(defined('MEDIA_NOTIFICATION_EMAIL_ADDRESSES'))
			{
				$message = 'Media File Imported'."\n\n";
				$message .= 'Name:'."\n".$this->get_value('name')."\n\n";
				$site = new entity($this->get_value( 'site_id' ));
				$message .= 'Site:'."\n".$site->get_value('name')."\n\n";
				$user = new entity($this->admin_page->user_id);
				$message .= 'Imported by:'."\n".$user->get_value('name')."\n\n";
				$message .= 'URL:'."\n".$this->get_value('url')."\n\n";
				$message .= 'Metadata:'."\n".$this->get_value('url').'.txt'."\n\n";
				$message .= 'Preview:'."\n";
				$message .= securest_available_protocol() . '://'.REASON_WEB_ADMIN_PATH.'?site_id='.$this->get_value( 'site_id' ).'&type_id='.id_of('av_file').'&id='.$this->get_value('id').'&cur_module=Preview';
				mail(MEDIA_NOTIFICATION_EMAIL_ADDRESSES,'[Reason] Media file imported on '.REASON_HOST,$message);
			}
			else
			{
				trigger_error('NOTIFY_WHEN_MEDIA_IS_IMPORTED set to true, but MEDIA_NOTIFICATION_EMAIL_ADDRESSES not provided. MEDIA_NOTIFICATION_EMAIL_ADDRESSES must be added as a constant in the settings file in order to receive media import notices');
			}
		}
	}
	
	/**
	 * Modifies the URL to swap protocols from http to rtsp or vice versa
	 */
	function do_protocol_switch()
	{
		$parsed = parse_url($this->get_value('url'));
		$protocol = $this->get_protocol();
		if($parsed['scheme'] != $protocol)
		{
			$new_url = alter_protocol($this->get_value('url'),$parsed['scheme'],$protocol);
			$this->set_value('url', $new_url);
		}
	}
	
	/**
	 * Processes the form.  Overloaded so that the meta info can get updated every time the form is saved.
	 * Could be made smarter so it only runs if the entity has changed
	 */
	function process()
	{
		parent::process();
		
		if($this->get_value('reason_managed_media') && $this->manages_media)
		{
			$user = new entity( $this->admin_page->user_id );
			$stream = new $this->file_transfer_utility_class( $user->get_value('name') );
			$stream->set_meta_info($this->get_value('id'),$this->get_meta_info());
		}
	}
}
?>