<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include Reason libraries and Disco
 */
include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php' );
reason_include_once( 'function_libraries/user_functions.php' );

/**
 * Register form with Reason
 */
$GLOBALS[ '_publication_post_forms' ][ basename( __FILE__, '.php' ) ] = 'BlogPostSubmissionForm';

/**
 * Post submission form
 *
 */
class BlogPostSubmissionForm extends Disco
{
	var $elements = array(
		'dont_post' => array(
			'type'=>'comment',
			'text'=>'<a href ="?">Return to publication without posting</a>',
		),
		'title',
		'author',
		'post_content' => array(
			'type'=>'textarea',
			'display_name' => 'Content',
		),
		'description' => array(
			'type'=>'textarea',
			'display_name' => 'Excerpt/Teaser (displayed on post listings; not required)',
		),
		'categories',
		'tarbaby' => array(
			'type'=>'text',
			'display_name'=>'Not Content',
			'comments'=>'<div class="tarbabyComment">(This is here to trap robots. Don\'t put any text here.)</div>',
		),
	);
	
	var $required = array(
		'title',
		'author',
		'post_content',
	);
	
	var $actions = array('Submit'=>'Post Item');
	var $site_info;
	var $publication;
	var $user_netID;
	var $categories;
	var $new_post_id;
	var $section_id;
	var $issue_id;
	
	function BlogPostSubmissionForm($site_id, $publication, $user_netID, $hold_posts_for_review)
	{
		$this->publication = $publication;
		$this->site_info = new entity($site_id);
		$this->user_netID = $user_netID;
		$this->hold_posts_for_review = $hold_posts_for_review;
	}
	
	function disabled_for_maintenance()
	{
		return (reason_maintenance_mode() && !reason_check_privs('db_maintenance'));
	}

	function on_every_time()
	{
		if ($this->disabled_for_maintenance())
		{
			echo '<p>Posting is temporarily disabled because the website is in maintenance mode. Please try again later.</p>';
			$this->show_form = false;
			return false;
		}

		if($this->hold_posts_for_review)
		{
			$this->actions['Submit'] = 'Submit Post (Moderated)';
		}
		
		// nwhite make a nice link that only clears add item and return text that identifies publication type
		$pub_type = ($pt = $this->publication->get_value('publication_type')) ? strtolower($pt) : 'publication';
		$link = carl_make_link(array('add_item' => ''));
		$this->change_element_type('dont_post', 'comment', array('text' => '<a href="'.$link.'">Return to '.$pub_type.' without posting</a>'));

		if(!empty($this->user_netID))
		{
			$this->set_value('author', $this->user_netID);
		}
		$this->do_wysiwygs();
		$this->do_categories();
		$this->do_issues();
		$this->do_sections();
		$this->set_order($this->get_order_array());
	}

	function do_wysiwygs()
	{
		$editor_name = html_editor_name($this->site_info->id());
		$params = html_editor_params($this->site_info->id());
		if(function_exists('reason_user_has_privs') && strpos($editor_name,'loki') === 0 && $user_id = get_user_id( $this->user_netID ) )
		{
			$params['user_is_admin'] = reason_user_has_privs( $user_id, 'edit_html' );
		}
		$this->change_element_type('post_content',$editor_name,$params);
		$this->change_element_type('description',$editor_name,$params);
	}

	function get_order_array()
	{
		return array('dont_post','issue','section','title','author','post_content','description','categories');
	}

	function do_issues()
	{
		//check to see if the current publication has issues.  If it does, require the user to select an issue.
		if($this->publication->get_value('has_issues') == "yes")
		{
			$issues = $this->get_issues();
			if(!empty($issues))
			{
				$issue_names = array();
				foreach($issues as $issue_id=>$issue)
				{
					$issue_names[$issue_id] = $issue->get_value('name');
				}
				$this->add_element('issue', 'select_no_sort', array("options"=>$issue_names));
				$this->set_display_name('issue', 'Issue');
				$this->add_required('issue');
				
				//set a default value if we've been looking at a particular issue
				if(!empty($this->issue_id))
					$this->set_value('issue', $this->issue_id);
			}
		}
	}
	
	function do_sections()
	{				
		//check to see if the current publication has sections.  If it does, require the sure to select a section.
		$sections = $this->get_sections();
		if(!empty($sections))
		{
			$section_names = array();
			foreach($sections as $section_id=>$section)
			{
				$section_names[$section_id] = $section->get_value('name');
			}
			$this->add_element('section','select',array( 'options' => $section_names, 'display_name'=>'Section'));
			$this->add_required('section');
			
			//set a default value if we've been looking at a particular section
			if(!empty($this->section_id))
			{
				$this->set_value('section', $this->section_id);
			}
		}
	}
	
	function run_error_checks()
	{
		if($this->get_value('tarbaby'))
		{
			$this->set_error('tarbaby','The Not Content field must be left empty for your post to work');
		}
		$fields_to_tidy = array('post_content','description');
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
	
	function do_categories()
	{
		$es = new entity_selector($this->site_info->id());
		$es->add_type(id_of('category_type'));
		$es->set_order('entity.name ASC');
		$this->categories = $es->run_one();
		if(!empty($this->categories))
		{
			foreach($this->categories as $id=>$category)
			{
				$options[$id] = $category->get_value('name');
			}
			$this->change_element_type('categories', 'checkboxgroup', array('options'=>$options));
		}
		else
		{
			$this->remove_element('categories');
		}
	}
	
	function process()
	{	
		$description = trim(tidy($this->get_value('description')));
		$content = trim(get_safer_html(tidy($this->get_value('post_content'))));
		if(carl_empty_html($description))
		{
			$words = explode(' ', $content, 31);
			unset($words[count($words)-1]);
			$description = implode(' ', $words).'…';
			$description = trim(tidy($description)); // we're tidying it twice so that if we chop off a closing tag tidy will stitch it back up again
		}
		
		if(!empty($this->user_netID))
		{
			$user_id = make_sure_username_is_user($this->user_netID, $this->site_info->id());
		}
		else
		{
			$user_id = $this->site_info->id();
		}
		
		if($this->hold_posts_for_review)
		{
			$status = 'pending';
		}
		else
		{
			$status = 'published';
		}

		$values = array (
			'status' => $status,
			'release_title' => trim(strip_tags($this->get_value('title'))),
			'author' => trim(strip_tags($this->get_value('author'))),
			'content' => $content,
			'description' => $description,
			'datetime' => date('Y-m-d H:i:s', time()),
			'keywords' => implode(', ', array(strip_tags($this->get_value('title')), date('Y'), date('F'))),
			'show_hide' => 'show',
			'new' => 0
		);
				
		$this->new_post_id = reason_create_entity( 
			$this->site_info->id(), 
			id_of('news'), 
			$user_id, 
			$values['release_title'], 
			$values
		);
		
		create_relationship(
			$this->new_post_id,
			$this->publication->id(),
			relationship_id_of('news_to_publication')
		);

		if ($this->successfully_submitted())
		{
			
			if($this->hold_posts_for_review)
			{
				echo '<p>Posts are being held for review on this publication. Please check back later to see if your post has been published.</p>';
				echo '<a href="?">Back to main page</a>';
			
			}
			else
			{
				echo '<p>Your post has been published.</p>';
				echo '<a href="'.carl_construct_redirect(array('story_id'=>$this->new_post_id)).'">View it.</a>';
			}
		}
		
		if($this->get_value('issue'))
		{
			create_relationship($this->new_post_id, $this->get_value('issue'), relationship_id_of('news_to_issue'));
		}
		
		if($this->get_value('section'))
		{
			create_relationship($this->new_post_id, $this->get_value('section'), relationship_id_of('news_to_news_section'));
		}
		
		if($this->get_value('categories'))
		{
			foreach($this->get_value('categories') as $category_id)
			{
				// Check to make sure ids posted actually belong to categories in the site
				if(array_key_exists($category_id, $this->categories))
				{
					create_relationship(
						$this->new_post_id,
						$category_id,
						relationship_id_of('news_to_category')
					);
				}
			}
		}
		
		$this->show_form = false;

		$this->do_notifications();
	}
	
	function do_notifications()
	{
		if($this->publication->get_value('notify_upon_post'))
		{
			$subject = 'New post on '.strip_tags($this->publication->get_value('name'));
			$message = 'A post has been added to '.strip_tags($this->publication->get_value('name'));
			$message .= ' on the site '.strip_tags($this->site_info->get_value('name')).'.';
			$message .= "\n\n";

			if($this->hold_posts_for_review)
			{
				$message .= 'This post is currently held for review. Review this post:'."\n\n";
				$message .= securest_available_protocol().'://'.REASON_WEB_ADMIN_PATH.'?site_id='.$this->site_info->id().'&type_id='.id_of('news').'&id='.$this->new_post_id."\n\n";
			}
			else
			{
				$message .= 'View post:'."\n\n";
				$message .= carl_construct_link(array('story_id'=>$this->new_post_id));
			}

			include_once(TYR_INC.'email.php');
			$e = new Email($this->publication->get_value('notify_upon_post'), WEBMASTER_EMAIL_ADDRESS, WEBMASTER_EMAIL_ADDRESS, $subject, $message);
			$e->send();
		}
	}
	
	function get_issues()
	{
		$issues = array();
		$es = new entity_selector( $this->site_info->id() );
		$es->description = 'Selecting issues for this publication';
		$es->add_type( id_of('issue_type') );
		$es->add_left_relationship( $this->publication->id(), relationship_id_of('issue_to_publication') );
		$es->set_order('dated.datetime DESC');
		return $es->run_one();
	}
	
	function get_sections()
	{
		$es = new entity_selector( $this->site_info->id()  );
		$es->description = 'Selecting news sections for this publication';
		$es->add_type( id_of('news_section_type'));
		$es->add_left_relationship( $this->publication->id(), relationship_id_of('news_section_to_publication') );
		$es->set_order('entity.name ASC');
		return $es->run_one();
	}
	
	function set_issue_id($id)
	{
		$this->issue_id = $id;
	}
	
	function set_section_id($id)
	{
		$this->section_id = $id;
	}
}
?>
