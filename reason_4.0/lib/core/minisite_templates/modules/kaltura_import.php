<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include the base class and register the module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	include_once(DISCO_INC . 'disco.php');
	include_once( TYR_INC . 'email.php');
	reason_include_once('classes/plasmature/upload.php');
	reason_include_once('classes/kaltura_shim.php');
	include_once(DISCO_INC . 'boxes/stacked.php');
	reason_include_once('classes/default_access.php');


	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'kalturaUploadModule';

	/**
	 * A minisite module that provides a demonstration version of whatever editor is assigned to the site
	 */
	class kalturaUploadModule extends DefaultMinisiteModule
	{
		// todo: javascript/jQuery to resemble content manager
		var $acceptable_params = array(
			//'max_per_person' => 0, //implement this later
			'intro_blurb' => '',
			'entries_gallery_page' => '',
			'contact_username' => '',
			'thanks_blurb' => '',
			'close_datetime' => 0,
			'closed_blurb' => '',
			'default_values' => array(),
			'default_group_uname' => '',
		);
		
		var $cleanup_rules = array(
			'thanks' => 'turn_into_int',
		);
		
		protected $_media_work_id;
		protected $_entry;
		protected $_filepath;
		protected $_filename;
		protected $_user_id;
		
		function init( $args = array() )
		{
			$this->get_head_items()->add_javascript(REASON_HTTP_BASE_PATH.'modules/kaltura_import/kaltura_import.js');
			$this->get_head_items()->add_stylesheet(REASON_HTTP_BASE_PATH.'modules/kaltura_import/kaltura_import.css');
			force_secure_if_available();
			parent::init($args);
		}
		
		function run()
		{
			echo '<div id="kalturaUploadModule">'."\n";
			if(!empty($this->request['thanks']) && 1 == $this->request['thanks'])
			{
				echo $this->_get_thanks_message();
			}
			elseif(!empty($this->params['close_datetime']) && datetime_to_unix($this->params['close_datetime']) < time())
			{
				echo $this->_get_closed_message();
			}
			else
			{
				$d = $this->_get_form();
				$d->run();
			}
			echo '</div>'."\n";
		}
		
		protected function _get_thanks_message()
		{
			$msg = '';
			if(!empty($this->params['thanks_blurb']))
				$msg = get_text_blurb_content($this->params['thanks_blurb']);
			if(empty($msg))
				$msg = '<h3>Thank you for uploading your media.</h3>';
			$msg .= '<p><a href="?">Submit another video/audio file</a></p>'."\n";
			return $msg;
		}
		
		protected function _get_closed_message()
		{
			$msg = '';
			if(!empty($this->params['closed_blurb']))
				$msg = get_text_blurb_content($this->params['closed_blurb']);
			if(empty($msg))
				$msg = 'Sorry; this upload form has closed.';
			return $msg;
		}
		
		protected function _get_intro()
		{
			$msg = '';
			if(!empty($this->params['intro_blurb']))
				$msg = get_text_blurb_content($this->params['intro_blurb']);
			return $msg;
		}
		
		protected function _get_form()
		{
			$d = new Disco();
			$d->set_form_class("StackedBox");
			
			$d->add_element('name','text');
			$d->set_display_name('name','Creator\'s Name');
			$d->add_required('name');
			
			$d->add_element('email','text');
			$d->set_display_name('email','Creator\'s Email Address');
			$d->add_required('email');
			
			$d->add_element('class_year','text');
			$d->set_display_name('class_year','Creators\'s Class Year (if Applicable)');
			
			$d->add_element('media_title','text');
			$d->set_display_name('media_title','Title');
			$d->add_required('media_title');
			
			$d->add_element('description','textarea');
			$d->set_display_name('description','Media Description');
			$d->add_required('description');
			
			$d->add_element('av_type', 'radio_no_sort', array('options'=>array('Audio'=>'Audio <span class="smallText formComment">(e.g. sound-only music, speech, etc.)</span>','Video'=>'Video <span class="smallText formComment">(e.g. movies, videos, etc.)</span>')));
			$d->set_display_name('av_type', 'Media Type');
			$d->add_required('av_type');
			
			//$authenticator = array("reason_username_has_access_to_site", $this->get_value("site_id"));
			$params = array(//'authenticator' => $authenticator,
				'acceptable_extensions' => KalturaShim::get_recognized_extensions(),
				'max_file_size' => $this->_get_actual_max_upload_size(),
				'head_items' => &$this->head_items);
			$d->add_element( 'upload_file', 'ReasonUpload', $params);
			$d->set_display_name('upload_file','Media to Upload');
			//$d->add_required('upload_file');
			$d->add_comments('upload_file',form_comment('If the file is on your computer, browse to it here.') );
			$d->add_comments('upload_file',form_comment('File must have one of the following extensions: .'.implode(', .', KalturaShim::get_recognized_extensions()) ) );
			$d->add_comments('upload_file',form_comment('<div class="maxUploadSizeNotice">Maximum file size for uploading is 100MB. </div>'));
			
			$d->add_element('url', 'text');
			$d->set_display_name('url', 'Media url');
			$d->add_comments('url', form_comment('If you are uploading a file larger than 100MB, enter the url of the file you are uploading here.'));
			$d->add_comments('url', form_comment('Example: http://people.carleton.edu/~huderlem/reason_import_only/video.mp4'));
			
			$d->add_element('permission','checkboxfirst');
			$d->set_display_name('permission', 'I give Carleton College the right to reproduce, display, and use this media in any manner.');
			$d->add_required('permission');
			
			$d->add_element('rights','checkboxfirst');
			$d->set_display_name('rights', 'I am the creator of this media and have full rights to its use.');
			$d->add_required('rights');
			
			$d->set_actions( array('save' => 'Submit your Media') );
			
			$d->form_enctype = 'multipart/form-data';
			
			$this->_populate_author($d);
			
			$d->add_callback(array($this,'get_intro'),'pre_show_form');
			
			$d->add_callback(array($this,'error_check_form'),'run_error_checks');
			
			$d->add_callback(array($this,'process_form'),'process');
			
			$d->add_callback(array($this,'get_thank_you_url'),'where_to');
			
			return $d;
		}
		
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
		
		public function get_intro($disco)
		{
			return $this->_get_intro();
		}
		
		protected function _get_user_info()
		{
			static $info = NULL;
			if(NULL !== $info)
				return $info;
			
			$info = array();
			if ($user = reason_check_authentication()) 
			{
				$dir = new directory_service();
				$dir->search_by_attribute('ds_username', array($user), array('ds_username','ds_fullname','carlcohortyear'));
				$info = $dir->get_first_record();
			}
			return $info;
		}
		
		protected function _populate_author($disco)
		{
			$person_info = $this->_get_user_info();
			if (!empty($person_info))
			{
				if(isset($person_info['ds_fullname'][0]))
				{
					$disco->set_value('name', $person_info['ds_fullname'][0]);
				}
				if(!empty($person_info['carlcohortyear'][0]) && $person_info['carlcohortyear'][0] >= carl_date('Y') && 	$person_info['carlcohortyear'][0] < carl_date('Y') + 4 )
				{
					$disco->set_value('class_year', $person_info['carlcohortyear'][0]);
				}
				if(!empty($person_info['mail'][0])) 
				{
					$disco->set_value('email', $person_info['mail'][0]);
				}
			} 		
		}
		
		public function error_check_form($disco)
		{
			if(!$disco->has_error('email'))
			{
				$email = $disco->get_value('email');
				$num_results = preg_match( '/^([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+$/i', $email );
				if (filter_var($email, FILTER_VALIDATE_EMAIL) === false or $num_results <= 0)
				{
						$disco->set_error('email', 'The email address is invalid, please try again.');
				}
			}
			
			if ($disco->get_value('upload_file') && $disco->get_value('url'))
			{
				$disco->set_error('upload_file', 'You may upload videos with the upload link or the Media url field, not both.');
				return;
			}
			else if (!$disco->get_value('upload_file') && !$disco->get_value('url'))
			{
				$disco->set_error('upload_file', 'You must either choose a file to upload or enter the url for the file you wish to upload.');
				return;
			}
			else if(!$disco->has_error('upload_file') && !$disco->get_value('url'))
			{
				$upload_file = $disco->get_value('upload_file');
				if(!empty($upload_file['original_path']))
				{
					$file_to_check = $image['original_path'];
				}
				elseif(!empty($upload_file['path']))
				{
					$file_to_check = $upload_file['path'];
				}
				else
				{
					$disco->set_error('upload_file','The file you uploaded was not recognized. Please try again.');
					return;
				}
			}
			
			if ($disco->has_errors())
				return;
			$this->upload_media($disco);
		}
		
		public function upload_media($disco)
		{
			$username = reason_check_authentication();
			if(empty($username))
			{
				$this->_user_id = make_sure_username_is_user('anonymous', $this->site_id);
			}
			else
			{
				$this->_user_id = make_sure_username_is_user($username, $this->site_id);//|||
			}
			
			// Begin the upload to Kaltura
			$kaltura_shim = new KalturaShim();
			if ($disco->get_element('upload_file')->tmp_full_path)
			{	
				$tmp_path_arr = explode("/", $disco->get_element('upload_file')->tmp_full_path);
				$this->_filename = end($tmp_path_arr);
				$this->_filepath = $this->get_kaltura_import_dir().end($tmp_path_arr);
				rename($disco->get_element('upload_file')->tmp_full_path, $this->_filepath);
			}
			else 
			{
				$this->_filename = $disco->get_value('url');
				$this->_filepath = $disco->get_value('url');
			}
			
			
			$categories = array($this->cur_page->get_value('name'));
			if(!empty($this->params['entries_gallery_page']))
			{
				if(reason_unique_name_exists($this->params['entries_gallery_page']))
				{
					$page_id = id_of($this->params['entries_gallery_page']);
					$page = new entity($page_id);
					$categories = array($page->get_value('name'));
				}				
			}
			
			if ($disco->get_value('av_type') == 'Video')
			{
				$this->_entry = $kaltura_shim->upload_video($this->_filepath, $disco->get_value('media_title'), $disco->get_value('description'), ''/*explode(" ", $this->get_value('keywords'))*/, $categories, $username);
			}
			else
			{	
				$this->_entry = $kaltura_shim->upload_audio($this->_filepath, $disco->get_value('media_title'), $disco->get_value('description'), ''/*explode(" ", $this->get_value('keywords'))*/, $categories, $username, $this->_filename);
			}
			
			if (!$this->_entry)
			{
				$disco->set_error('upload_file', 'There was an error during the upload process.'.$this->_filepath);
			}
		}
		
		public function process_form($disco)
		{
			$id = $this->_create_media_entity($disco);
			if ($id) {
				$this->_email_confirmation($disco,$id);
				$this->_send_contact_email($disco, $id);
			}
		}
		
		protected function _create_media_entity($disco)
		{		
			// put together the values for the entity
			$name = htmlspecialchars($disco->get_value('media_title'));
			
			$values = $this->params['default_values'];
			$values['name'] = $name;
			$values['new'] = "0";
			$values['author'] = htmlspecialchars($disco->get_value('name'));
			if($disco->get_value('class_year')) 
			{
				switch(strlen($disco->get_value('class_year')))
				{
					case 4:
						$values['author'] .= ' \''.htmlspecialchars(mb_substr($disco->get_value('class_year'), -2));
						break;
					case 3:
						$values['author'] .= htmlspecialchars($disco->get_value('class_year'));
						break;
					case 2:
						$values['author'] .= ' \''.htmlspecialchars($disco->get_value('class_year'));
				}
			}
			$values['description'] = htmlspecialchars($disco->get_value('description'));
			$values['keywords'] = $name.', '.$values['author'];
			$values['entry_id'] = $this->_entry->id;
			$values['av_type'] = $disco->get_value('av_type');
			$values['transcoding_status'] = 'converting';
			$values['integration_library'] = 'kaltura';
						
			$values['tmp_file_name'] = $this->_filename;
			
			// create the entity
			$this->_media_work_id = $id = reason_create_entity( $this->site_id, id_of('av'), $this->_user_id, $name, $values);
			//$this->set_form_id($id);
		
			$page_id = $this->page_id;
			if(!empty($this->params['entries_gallery_page']))
			{
				if(reason_unique_name_exists($this->params['entries_gallery_page']))
					$page_id = id_of($this->params['entries_gallery_page']);
				else
					trigger_error('Unable to find uniquely named page '.$this->params['entries_gallery_page'].'. Attaching photos to form page.');
			}
			create_relationship( $page_id, $id, relationship_id_of('minisite_page_to_av'));
			
			// access restriction
			$da = reason_get_default_access();
			if ($this->params['default_group_uname'] && reason_unique_name_exists($this->params['default_group_uname']))
			{
				$group_id = id_of($this->params['default_group_uname']);
				create_relationship( $id, $group_id, relationship_id_of('av_restricted_to_group') );
			}
			elseif($group_id = $da->get($this->site_id, 'av', 'av_restricted_to_group'))
			{
				create_relationship( $id, $group_id, relationship_id_of('av_restricted_to_group') );
			}
			return $id;
		}
		
		protected function _email_confirmation($disco, $image_id)
		{
			$tos = reason_check_authentication();
			if(empty($tos))
				$tos = $disco->get_value('email');
		
			$froms = 'auto-form-process@carleton.edu';
			$replytos = '';
			$subject = 'Media Submission Confirmation';
			$txtbody = 'Your media, "' . $disco->get_value('media_title') . '", was successfully submitted.' . "\n";
			$htmlbody = '<p>Your media, <strong>'.htmlspecialchars($disco->get_value('media_title')).'</strong>, was successfully submitted.</p>';
			$footer = "Please do not reply to this automatically generated notification message.";
			$footer_divider = "--------------------------------------------------------------------------------------";	
			$txtbody .= "\n\n" . $footer_divider . "\n" . $footer . "\n" . $footer_divider;
			$htmlbody .= "<hr/>" . $footer . "<hr/>";	
			$mailer = new Email($tos, $froms, $replytos, $subject, $txtbody, $htmlbody);
			$mailer->send();
		}
		
		protected function _send_contact_email($disco, $media_id)
		{
			if(/*THIS_IS_A_DEVELOPMENT_REASON_INSTANCE || */empty($this->params['contact_username']) )
				return;
			$tos = $this->params['contact_username'];
			$froms = 'auto-form-process@carleton.edu';
			$replytos = '';
			$subject = 'A media submission has been made';
			$txt_body = '';
			$html_body = '';
			$submitter_name = $disco->get_value('name');
			
			$user_info = $this->_get_user_info();
			if(!empty($user_info['ds_email']))
				$submitter_email = $user_info['ds_email'];
			else
				$submitter_email = $disco->get_value('email');
		
			$media_title = $disco->get_value('media_title');
			$media_description = $disco->get_value('description');
			$footer_first = "Please do not reply to this automatically generated notification message.";
			$footer_second = "Please store this email for your records. It will help you link submissions to identities.";
			$footer_divider = "--------------------------------------------------------------------------------------";	
		
			$txt_body .= "Creator Name: " . $submitter_name . "\n";
			$txt_body .= "Creator Email: " . $submitter_email . "\n";
			$txt_body .= "Media Title: " . $media_title . "\n";
			$txt_body .= "Media Description: " . $media_description . "\n";
			$txt_body .= "Media ID: " . $media_id . "\n";
			$txt_body .= "\n\n" . $footer_divider . "\n";
			$txt_body .= $footer_first . "\n" . $footer_second . "\n";
			$txt_body .= $footer_divider;
		
			$html_body .= "<p>Submitter Name: " . htmlspecialchars($submitter_name) . "</p>";
			$html_body .= "<p>Submitter Email: " . htmlspecialchars($submitter_email) . "</p>";
			$html_body .= "<p>Media Title: " . htmlspecialchars($media_title) . "</p>";
			$html_body .= "<p>Media Description: " . htmlspecialchars($media_description) . "</p>";
			$html_body .= "<p>Media ID: " . htmlspecialchars($media_id) . "</p>";
			$html_body .= "<hr/>" . htmlspecialchars($footer_first) . "<br/>" . htmlspecialchars($footer_second) . "<hr/>";
		
			$mailer = new Email($tos, $froms, $replytos, $subject, $txt_body, $html_body);
			$mailer->send();
		
			/*
			// This was in the ACE photo upload module... keeping it out of this for simplicity's sake.
			$csv_file = fopen(REASON_CSV_DIR . 'ACE_photo_contest_public_2011.csv', 'a');
			$csv_data = array($submitter_name, $submitter_email, $media_title, $photo_description);
			fputcsv($csv_file, $csv_data);
			fclose($csv_file);
			*/
		}
		
		public function get_thank_you_url($disco)
		{
			$parts = array(
				'thanks' => 1,
			);
			return carl_make_redirect($parts);
		}
		
		function get_kaltura_import_dir()
		{
			return KalturaShim::get_temp_import_dir();
		}
	}
?>