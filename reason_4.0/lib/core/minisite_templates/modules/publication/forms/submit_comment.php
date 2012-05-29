<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include Disco
 */
include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php');
include_once( DISCO_INC . 'plasmature/types/recaptcha.php' );
reason_include_once( 'function_libraries/user_functions.php' );

/**
 * Register the form with Reason
 */
$GLOBALS[ '_publication_comment_forms' ][ basename( __FILE__, '.php' ) ] = 'commentForm';

/**
 * Comment submission form
 */
class commentForm extends Disco
{
	var $elements = array(
		'author' => array(
								'type'=>'text',
								'display_name' => 'Name',
								'size' => 30,
								),
		'comment_content' => array(
								'type'=>'textarea',
								'display_name' => 'Comment',
								),
		'tarbaby_pre' => array(
								'type'=>'comment',
								'text'=>'The following fields are not to be filled out. <a href="#discoSubmitRow">Skip to Submit Button</a>.',
							),
		'tarbaby' => array(
								'type'=>'text',
								'display_name'=>'Not Comment',
								'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
							),
		'not_url' => array(
								'type'=>'text',
								'display_name'=>'not URL',
								'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
							),
		'antlion' => array(
								'type'=>'text',
								'display_name'=>'Avoid',
								'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
							),
		'comment_posted_id' => array(
								'type'=>'hidden',
							),
						  ); 
	
	var $required = array(
		'author',
		'comment_content',
	);
	var $forbidden = array(
		'tarbaby',
		'not_url',
		'antlion',
	);
	
	var $actions = array('Submit'=>'Submit Comment');
	
	var $site_id;
	var $site_info;
	var $news_item;
	var $comment_id;
	var $username;
	var $hold_comments_for_review;
	var $publication;
	
	function commentForm($site_id, $news_item, $hold_comments_for_review, $publication)
	{
		$this->site_id = $site_id;
		$this->site_info = new entity($this->site_id);
		$this->hold_comments_for_review = $hold_comments_for_review;
		$this->news_item = $news_item;
		$this->publication = $publication;
	}
	
	function set_username($username)
	{
		$this->username = $username;
	}
	
	function disabled_for_maintenance()
	{
		return (reason_maintenance_mode() && !reason_check_privs('db_maintenance'));
	}
	
	function on_every_time()
	{
		if ($this->disabled_for_maintenance())
		{
			echo '<p>Commenting is temporarily disabled because the website is in maintenance mode. Please try again later.</p>';
			$this->show_form = false;
			return false;
		}
		if($this->hold_comments_for_review)
		{
			$this->actions['Submit'] = 'Submit Comment (Moderated)';
		}
		$this->do_wysiwygs();
		$this->set_value('comment_posted_id', '');
		$this->do_captcha();
	}
	
	/**
	 * If recaptcha is setup for this instance of Reason and the user is not logged in, lets add the captcha.
	 */
	function do_captcha()
	{
		$rk_public = constant("RECAPTCHA_PUBLIC_KEY");
		$rk_private = constant("RECAPTCHA_PRIVATE_KEY");
		if (!reason_check_authentication() && !empty($rk_public) && !empty($rk_private))
		{
			$this->add_element('recaptcha', 'recaptcha');
			$this->add_required('recaptcha');
			$this->set_display_name('recaptcha', 'Challenge Question');
		}
	}
	
	function do_wysiwygs()
	{
		$editor_name = html_editor_name($this->site_info->id());
		$params = html_editor_params($this->site_info->id());
		if(strpos($editor_name,'loki') === 0)
		{
			if(!empty($this->username) && $user_id = get_user_id( $this->username ) )
			{
				if($editor_name == 'loki') //loki 1
				{
					$params['widgets'] = array('strong','em','link');
				}
				else
				{
					$params['widgets'] = array('strong','em','link','blockquote');
				}
				if( function_exists('reason_user_has_privs') )
				{
					$params['user_is_admin'] = reason_user_has_privs( $user_id, 'edit_html' );
				}
			}
			else
			{
				if($editor_name == 'loki') //loki 1
				{
					$params['widgets'] = array('strong','em');
				}
				else
				{
					$params['widgets'] = array('strong','em','blockquote');
				}
				if (isset($params['paths']))
				{
					unset($params['paths']['site_feed']);
					unset($params['paths']['finder_feed']);
					unset($params['paths']['default_site_regexp']);
					unset($params['paths']['default_type_regexp']);
				}
			}
		}
		$this->change_element_type('comment_content',$editor_name,$params);
	}
	
	function run_error_checks()
	{
		foreach($this->forbidden as $field)
		{
			if($this->get_value($field))
			{
				$this->set_error($field,'This field must be left empty for your comment to work');
			}
		}
		$content = $this->get_value('comment_content');
		$content = str_replace('&nbsp;', ' ', $content);
		if (carl_empty_html(trim(tidy($content))))
		{
			$this->set_error('comment_content', 'You must write a comment in order to post a comment!');
		}
				
		$fields_to_tidy = array('comment_content');
		foreach($fields_to_tidy as $field)
		{
			if($this->get_value($field))
			{
				$tidied = trim(tidy($this->get_value($field)));
				if(empty($tidied) && in_array($field,$this->required))
				{
					if(!empty($this->elements[$field]['display_name']))
					{
						$display_name = $this->elements[$field]['display_name'];
					}
					else
					{
						$display_name = prettify_string($field);
					}
					$this->set_error($field,'Please fill in the '.$display_name.' field');
				}
				else 	
				{
					$tidy_errors = tidy_err($this->get_value($field));
					if(!empty($tidy_errors))
					{
						$msg = 'The html in the '.$field.' field is misformed.  Here is what the html checker has to say:<ul>';
						foreach($tidy_errors as $tidy_error)
						{
							$msg .= '<li>'.$tidy_error.'</li>';
						}
						$msg .= '</ul>';
						$this->set_error($field,$msg);
					}
				}
			}
		}
	}
	
	function post_show_form()
	{
		if ($this->has_errors())
		{
			echo '<script type="text/javascript">$(document).ready(function () {window.location.hash = "#discoErrorNotice"})</script>';
		}
	}
	
	function process()
	{
		if(!empty($this->username))
		{
			$user_id = make_sure_username_is_user($this->username, $this->site_id);
		}
		else
		{
			$user_id = $this->site_info->id();
		}

		if($this->hold_comments_for_review)
		{
			$show_hide = 'hide';
		}
		else
		{
			$show_hide = 'show';
		}
		$flat_values = array (
			'state' => 'Live',
			'author' => trim(get_safer_html(strip_tags($this->get_value('author')))),
			'content' => trim(get_safer_html(strip_tags(tidy($this->get_value('comment_content')), '<p><em><strong><a><ol><ul><li><blockquote><acronym><abbr><br><cite><code><pre>'))),
			'datetime' => date('Y-m-d H:i:s'),
			'show_hide' => $show_hide,
			'new'=>'0',
		);

		$this->comment_id = reason_create_entity( 
			$this->site_id, 
			id_of('comment_type'), 
			$user_id, 
			trim(substr(strip_tags($flat_values['content']),0,40)),
			$flat_values,
			$testmode = false
		);
		
		create_relationship(
			$this->news_item->_id,
			$this->comment_id,
			relationship_id_of('news_to_comment')
		);
		$this->do_notifications();
	}
	
	function do_notifications()
	{
		if($this->publication->get_value('notify_upon_comment'))
		{
			$subject = 'New comment on '.strip_tags($this->publication->get_value('name'));
			$message = 'A comment has beeen added to the post '.strip_tags($this->news_item->get_value('name'));
			$message .= ' on '.strip_tags($this->publication->get_value('name'));
			$message .= ' (site: '.strip_tags($this->site_info->get_value('name')).'.)';
			$message .= "\n\n";
			if($this->hold_comments_for_review)
			{
				$message .= 'This comment is currently held for review.'."\n\n";
				$message .= 'Review comment:'."\n";
			}
			else
			{
				$message .= 'View this comment in context:'."\n";
				$message .= get_current_url().'#comment'.$this->comment_id."\n\n";
				$message .= 'Manage this comment:'."\n";
			}
			$message .= securest_available_protocol().'://'.REASON_WEB_ADMIN_PATH.'?site_id='.$this->site_info->id().'&type_id='.id_of('comment_type').'&id='.$this->comment_id."\n\n";
			
			include_once(TYR_INC.'email.php');
			$e = new Email($this->publication->get_value('notify_upon_comment'), WEBMASTER_EMAIL_ADDRESS, WEBMASTER_EMAIL_ADDRESS, $subject, $message);
			$e->send();
		}
	}
	
	function where_to()
	{
		return '?story_id='.$this->news_item->id().'&comment_posted_id='.$this->comment_id;
	}
}
?>
