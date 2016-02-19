<?php
/**
 * Content manager for publications/blogs
 * @package reason
 * @subpackage content_managers
 */
 
  /**
   * Store the class name so that the admin page can use this content manager
   */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'BlogManager';
	
	/**
	 * Content manager for publications
	 *
	 * Customizes the form used to manage publications (e.g. blogs, newsletters, etc.) so that helpful comments, nice labels, etc. are used.
	 *
	 * Also handles error checking to make sure publications do not share a feed URL, and ensures the current site has all the necessary types for a given publication to function properly (e.g. issues, sections, comments, etc.)
	 * 
	 * @todo Add js-based show/hide stuff to simplify form (e.g. only show comment-related fields when commenting is enabled)
	 */
	class BlogManager extends ContentManager
	{
		function alter_data()
		{
			// Hide fields that are not used
			$this->change_element_type( 'keywords','hidden' );
			if($this->is_element('commenting_state'))$this->change_element_type( 'commenting_state', 'hidden' );
			if($this->is_element('pagination_state')) $this->change_element_type( 'pagination_state', 'hidden' );
			if($this->is_element('enable_comment_notification')) $this->change_element_type( 'enable_comment_notification', 'hidden' );
			if($this->is_element('enable_front_end_posting')) $this->change_element_type('enable_front_end_posting', 'hidden');
			
			// Name
			$this->set_display_name('name','Publication Name');
			
			// Publication type
			$this->add_required('publication_type');
			if (!$this->get_value( 'publication_type' )) $this->set_value( 'publication_type', 'blog' );
			
			// Posts per page
			$this->add_required('posts_per_page');
			
			// Date format
			$this->set_comments('date_format',form_comment('Posts on this publication will use the date format that you select to show the date (and/or) time of publication.'));
			$this->change_element_type( 'date_format', 'select_no_sort', array('options' => array('F j, Y \a\t g:i a' => date('F j, Y \a\t g:i a'),
																								  'n/d/y \a\t g:i a' => date('n/d/y \a\t g:i a'),
																								  'l, F j, Y' => date('l, F j, Y'),
																								  'F j, Y' => date('F j, Y'),
																								  'n/d/y' => date('n/d/y'), 
																								  'n.d.y' => date('n.d.y'),
																								  'j F Y' => date('j F Y'),
																								  'j F Y \a\t  g:i a' => date('j F Y \a\t  g:i a'),
																								  'j F Y \a\t  g:i a' => date('j F Y \a\t  H:i'), )));
			
			// RSS
			$this->add_element('rss_comment','comment',array('text'=>'<h4>RSS</h4>'));
			$this->add_comments('rss_comment',form_comment('RSS is a way for people to suscribe to your publication and be informed when there are new posts.'));
			$this->set_display_name('blog_feed_string','RSS feed URL');
			$this->add_required('blog_feed_string');
			$this->add_comments('blog_feed_string',form_comment('The URL snippet that this blog / publication will use for its RSS feed.'));
			$this->set_display_name('blog_feed_include_content','RSS Format');
			$this->change_element_type('blog_feed_include_content', 'radio',array('options' => array('no' => 'Descriptions only (Suscribers must click to see full content)','yes' => 'Publish full content in RSS' )));
			if(defined('PUBLICATION_HIDE_FEED_DESCRIPTION_CHECKBOX') && PUBLICATION_HIDE_FEED_DESCRIPTION_CHECKBOX )
			{
				$this->change_element_type('blog_feed_include_content', 'cloaked');
			}
			if(!$this->get_value('blog_feed_include_content'))
			{
				if(!$this->is_new_entity() || !defined('PUBLICATION_FEED_DEFAULT_TO_CONTENT') || !PUBLICATION_FEED_DEFAULT_TO_CONTENT)
					$this->set_value('blog_feed_include_content', 'no');
				else
					$this->set_value('blog_feed_include_content', 'yes');
			}	
			if( $this->is_new_entity() || reason_user_has_privs( $this->admin_page->user_id, 'edit_fragile_slugs' ) )
			{
				$this->add_comments('blog_feed_string',form_comment('Only lowercase letters and underscores are allowed in this field.'));
				if(!reason_user_has_privs( $this->admin_page->user_id, 'edit_fragile_slugs' ))
				{
					$this->add_comments('blog_feed_string',form_comment('After this publication has been "finished" the first time, this URL snippet will not be editable. So choose wisely.'));
				}
				else
				{
					$this->add_comments('blog_feed_string',form_comment('You may edit this field because you are a Reason admin.  However, if you change the URL, the feed will break in any newsreaders that are currently subscribed to this publication.'));
				}
			}
			else
			{
				$this->change_element_type( 'blog_feed_string','solidtext' );
				$this->add_comments('blog_feed_string',form_comment('This field is now fixed so that newsreaders can rely on a stable URL.'));
			}
			
			// Description
			$this->change_element_type( 'description' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );
			
			// Social sharing
			$this->add_element('social_sharing_comment','comment',array('text'=>'<h4>Social Sharing</h4>'));
			$this->add_comments('social_sharing_comment',form_comment('Would you like to provide links to share your content on social networks?'));
			$this->set_display_name('enable_social_sharing','&nbsp;');		
			$this->change_element_type('enable_social_sharing', 'radio',array('options' => array('no' => 'Don\'t provide buttons to share posts','yes' => 'Provide buttons to share posts on social networks' )));	
			if(!$this->get_value('enable_social_sharing'))
			{
				if(!$this->is_new_entity() || !defined('PUBLICATION_SOCIAL_SHARING_DEFAULT') || !PUBLICATION_SOCIAL_SHARING_DEFAULT)
					$this->set_value('enable_social_sharing', 'no');
				else
					$this->set_value('enable_social_sharing', 'yes');
			}	
			// Front-end posting
			$this->add_element('posting_comment','comment',array('text'=>'<h4>Posting on the public site</h4>'));
			$this->add_required('hold_posts_for_review');
			$this->change_element_type('hold_posts_for_review', 'radio',array('options' => array('no' => 'Publish posts automatically','yes' => 'Hold posts for review' )));	
			$this->set_display_name('hold_posts_for_review','New Post Moderation');
			$this->add_element('allow_front_end_posting', 'checkboxfirst');
			$this->add_comments('allow_front_end_posting',form_comment('Check to enable simple posting on the publication'));
			$this->set_display_name( 'notify_upon_post', 'New Post Notification' );
			$this->add_comments('notify_upon_post',form_comment('Who should be notified when a post is added to this publication? Enter usernames or email addresses, separated by commas. Leave this field blank if you don\'t want any notification to be sent.'));

			// Commenting
			$this->add_element('comment_comment','comment',array('text'=>'<h4>Commenting</h4>'));
			$this->add_element('allow_comments', 'checkboxfirst');
			$this->add_required('hold_comments_for_review');
			$this->change_element_type('hold_comments_for_review', 'radio',array('options' => array('no' => 'Publish comments automatically','yes' => 'Hold comments for review' )));	
			$this->set_display_name('hold_comments_for_review','Comment Moderation');
			$this->set_display_name( 'notify_upon_comment', 'New Comment Notification' );
			$this->add_comments('notify_upon_comment',form_comment('Who should be notified when a comment is added to this publication? Enter usernames or email addresses, separated by commas. Leave this field blank if you don\'t want any notification to be sent.'));
			
			// Posting & commenting defaults
			if(site_borrows_entity( $this->get_value('site_id'), id_of('nobody_group')) || site_owns_entity( $this->get_value('site_id'), id_of('nobody_group')))
			{
				$nobody_group = new entity(id_of('nobody_group'));
				if(!$this->entity->has_left_relation_with_entity($nobody_group, 'publication_to_authorized_posting_group'))
					$this->set_value('allow_front_end_posting', 'true');
				if(!$this->entity->has_left_relation_with_entity($nobody_group, 'publication_to_authorized_commenting_group'))
					$this->set_value('allow_comments', 'true');
			}
			else
			{
				$this->set_value('allow_comments', 'true');
				$this->set_value('allow_front_end_posting', 'true');
			}
			if(!$this->get_value('hold_comments_for_review'))
			{
				$this->set_value('hold_comments_for_review', 'no');
			}
			if(!$this->get_value('hold_posts_for_review'))
			{
				$this->set_value('hold_posts_for_review', 'no');
			}
			
			
			// Issues & Sections
			$this->add_element('issue_section_comment','comment',array('text'=>'<h4>Issues and sections</h4>'));
			
			$this->set_display_name('has_issues','Issues');
			$this->change_element_type('has_issues', 'radio',array('options' => array('no' => 'No Issues (Display posts as they are published)','yes' => 'Group posts into issues, like a printed magazine (note that you will need to set up at least one issue before any posts will appear)' )));		
			if(!$this->get_value('has_issues'))
			{
				$this->set_value('has_issues', 'no');
			}
			
			$this->set_display_name('has_sections','Sections');
			$this->change_element_type('has_sections', 'radio',array('options' => array('no' =>'No sections (Display posts by date)','yes' => 'Display posts under section headings (note that you will need to create sections for your publication)' )));		
			if(!$this->get_value('has_sections'))
			{
				$this->set_value('has_sections', 'no');
			}
			
			// Reminders
			$this->add_element('reminder_comment','comment',array('text'=>'<h4>Reminders</h4>'));
			$this->set_display_name('reminder_days','Days Before Reminder');
			$this->add_comments('reminder_days',form_comment('How many days of inactivity before Reason sends an email reminder? Leave blank or enter 0 to disable this feature.'));
			$this->set_display_name('reminder_emails','Send Reminders To');
			$this->add_comments('reminder_emails',form_comment('The emails or usernames of people who should be reminded'));
			$this->add_comments('reminder_comment',form_comment(' Reason can send an email reminder if it has been a while since the last post.'));
			if (!defined('PUBLICATION_REMINDER_CRON_SET_UP')||!PUBLICATION_REMINDER_CRON_SET_UP)
			{
				$this->change_element_type( 'reminder_days','hidden');
				$this->change_element_type( 'reminder_emails','hidden');
				$this->change_element_type( 'reminder_comment','hidden');
			}

			//Sharing among reason sites
			if(!$this->element_is_hidden('no_share'))
			{
				$this->add_element('share_comment','comment',array('text'=>'<h4>Sharing Among Reason Sites</h4>'));
			}
			
			$this->set_order(array('name', 'publication_type', 'posts_per_page', 'date_format', 'description', 'rss_comment', 'blog_feed_string', 'blog_feed_include_content','social_sharing_comment', 
								   'enable_social_sharing','posting_comment', 'allow_front_end_posting', 'notify_upon_post', 'hold_posts_for_review', 'comment_comment', 
								   'allow_comments', 'notify_upon_comment', 'hold_comments_for_review', 'issue_section_comment', 'has_issues','has_sections','reminder_comment','reminder_days', 'reminder_emails','share_comment','no_share','unique_name',));
		}
		function init_head_items()
		{
			$this->head_items->add_javascript(JQUERY_URL, true); // uses jquery - jquery should be at top
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'content_managers/publication.js');
			parent::init_head_items();
		}	
		function run_error_checks() // {{{
		{
			if( !$this->has_error( 'blog_feed_string' ) )
			{
				if( !preg_match( "|^[0-9a-z_]*$|" , $this->get_value('blog_feed_string') ) )
				{
					$this->set_error( 'blog_feed_string', 'The RSS feed URL may only contain lowercase letters, numbers, and underscores.  Please edit to remove other characters.' );
				}
				else
				{
					$es = new entity_selector($this->get_value('site_id'));
					$es->add_type( id_of('publication_type') );
					$es->add_relation ('entity.id != '.$this->get_value( 'id' ));
					$es->add_relation ('blog.blog_feed_string = "'.$this->get_value('blog_feed_string').'"' );
					$es->set_num(1);
					$same_feed_string = $es->run_one();
					if(!empty($same_feed_string))
					{
						$other_blog = current($same_feed_string);
						$this->set_error( 'blog_feed_string', 'Another publication ('.$other_blog->get_value('name').') shares the same RSS feed URL ("'.$other_blog->get_value('blog_feed_string').'"). Please choose a different RSS feed URL.' );
					}
				}
			}
			
			if($this->get_value('reminder_days'))
			{
				if(!$this->get_value('reminder_emails'))
				{
					$this->set_error('reminder_emails', 'If Reminder Days is set, Reminder Emails must be filled out');
				}
			}
			if($bad_addresses = $this->invalid_addresses($this->get_value('reminder_emails')))
			{
				$error = 'Reason was not able to validate these reminder addresses: '.htmlspecialchars(implode(', ',$bad_addresses));
				$this->set_error('reminder_emails', $validity);	
			}
		}

		function invalid_addresses($addresses)
		{
			$return_value = '';
			if ( !is_array($addresses) )
			{
				$addresses = explode(',', $addresses);
			}
			$bad_addresses = array();
			foreach ( $addresses as $address )
			{
				$address = trim($address);
				if ( !empty($address) )
				{
					$dir = new directory_service();
					$result = $dir->search_by_attribute('ds_username', $address, array('ds_email'));
					$dir_value = $dir->get_first_value('ds_email');
					if(empty($dir_value))
					{
						$num_results = preg_match( '/^([-.]|\w)+@([-.]|\w)+\.([-.]|\w)+$/i', $address );
						if ($num_results <= 0)
						{
							$bad_addresses[] = $address;
						}
					}
				}	
			}
			return $bad_addresses;
		}
			
		function process() // {{{
		{
			$nobody_group = new entity(id_of('nobody_group'));
			
			//check to see if posting or commenting have been enabled while still being associated with the nobody group
			if($this->get_value('allow_front_end_posting') && $this->entity->has_left_relation_with_entity($nobody_group, 'publication_to_authorized_posting_group'))
			{
				$this->delete_associations_of_type('publication_to_authorized_posting_group');			
			}
			if($this->get_value('allow_comments') && $this->entity->has_left_relation_with_entity($nobody_group, 'publication_to_authorized_commenting_group'))
			{
				$this->delete_associations_of_type('publication_to_authorized_commenting_group');
			}
		
			//check to see if posting or commenting have been disabled
			if(!$this->get_value('allow_front_end_posting') || !$this->get_value('allow_comments'))
			{		
				//if they are, check to make sure that we've borrowed or own the nobody group.
				if(!(site_borrows_entity( $this->get_value('site_id'), id_of('nobody_group')) || site_owns_entity( $this->get_value('site_id'), id_of('nobody_group'))))
				{
					//if not, borrow it.
					create_relationship($this->get_value('site_id'), id_of('nobody_group'), get_borrows_relationship_id(id_of('group_type')));
				}			
				
				//check to see if we've got the appropriate relationship(s) with the nobody group.	If we don't, create the relationship. 
				if(!$this->get_value('allow_front_end_posting') && !$this->entity->has_left_relation_with_entity($nobody_group, 'publication_to_authorized_posting_group'))
				{
					$this->associate_with_nobody_group('publication_to_authorized_posting_group');
				}
				if(!$this->get_value('allow_comments') && !$this->entity->has_left_relation_with_entity($nobody_group, 'publication_to_authorized_commenting_group'))
				{
					$this->associate_with_nobody_group('publication_to_authorized_commenting_group');
				}
			}
			// make sure the group type is available to the site if commenting or front-end posting are available
			if($this->get_value('allow_front_end_posting')||$this->get_value('allow_comments'))
			{
				if(!$this->site_has_type(id_of('group_type')))
				{
					$this->add_type_to_site(id_of('group_type'));
				}
			}
			if($this->get_value('allow_comments'))
			{
				if(!$this->site_has_type(id_of('comment_type')))
				{
					$this->add_type_to_site(id_of('comment_type'));
				}
			}
			
			if($this->get_value('has_issues') == 'yes' && !$this->site_has_type(id_of('issue_type')))
			{
				$this->add_type_to_site(id_of('issue_type'));
			}
			if($this->get_value('has_sections') == 'yes' && !$this->site_has_type(id_of('news_section_type')))
			{
				$this->add_type_to_site(id_of('news_section_type'));
			}
			if(!$this->site_has_type(id_of('news')))
			{
				// publicatons don't make much sense without news
				$this->add_type_to_site(id_of('news'));
			}
			
			parent::process();
		} // }}}
		
		function site_has_type($type_id)
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($this->get_value('site_id'),relationship_id_of('site_to_type'));
			$es->add_relation('entity.id = "'.$type_id.'"');
			$es->set_num(1);
			$issue_type = $es->run_one();
			if(empty($issue_type))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		function add_type_to_site($type_id)
		{
			$rel_id = create_relationship( $this->get_value('site_id'), $type_id, relationship_id_of('site_to_type'));
			if(empty($rel_id))
				trigger_error('Unable to create relationship to add issue type to site');
		}

		function associate_with_nobody_group($reln_unique_name)
		{
			//disassociate any current groups
			if($this->entity->has_left_relation_of_type( $reln_unique_name))
			{
				$this->delete_associations_of_type($reln_unique_name);
			}
			
			//create a relationship with the nobody group
			create_relationship($this->entity->id(), id_of('nobody_group'), relationship_id_of($reln_unique_name));
		}
		
		function delete_associations_of_type($reln_unique_name)
		{
			delete_relationships( array( 'entity_a' => $this->entity->id(), 'type' => relationship_id_of( $reln_unique_name )));
		}
		

	}
?>

