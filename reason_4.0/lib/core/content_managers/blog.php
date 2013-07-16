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
			$this->change_element_type( 'keywords','hidden' );
			// use WYSISWG editor for description 
			$this->change_element_type( 'description' , html_editor_name($this->admin_page->site_id) , html_editor_params($this->admin_page->site_id, $this->admin_page->user_id) );

			$this->add_element('allow_comments', 'checkbox');
			$this->add_element('allow_front_end_posting', 'checkbox');

			$this->add_required('hold_comments_for_review');
			$this->add_required('hold_posts_for_review');
			$this->add_required('posts_per_page');
			$this->add_required('blog_feed_string');
			
			if($this->is_element('notify_upon_post'))
			{
				$this->set_display_name( 'notify_upon_post', 'New Post Notification' );
			}
			else
			{
				trigger_error('The field "notify_upon_request" needs to be added to the blog table. Please run the upgrade script: '.REASON_HTTP_BASE_PATH.'scripts/upgrade/4.0b3_to_4.0b4/upgrade_db.php to add the proper field.');
			}
			if($this->is_element('notify_upon_comment'))
			{
				$this->set_display_name( 'notify_upon_comment', 'New Comment Notification' );
			}
			else
			{
				trigger_error('The field "notify_upon_comment" needs to be added to the blog table. Please run the upgrade script: '.REASON_HTTP_BASE_PATH.'scripts/upgrade/4.0b3_to_4.0b4/upgrade_db.php to add the proper field.');
			}
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
			if(!$this->get_value('has_issues'))
			{
				$this->set_value('has_issues', 'no');
			}
			if(!$this->get_value('has_sections'))
			{
				$this->set_value('has_sections', 'no');
			}	
		
			// hide things that do not appear fully implemented
			//$this->change_element_type( 'hold_posts_for_review', 'hidden' );
			if($this->is_element('commenting_state'))$this->change_element_type( 'commenting_state', 'hidden' );
			if($this->is_element('pagination_state')) $this->change_element_type( 'pagination_state', 'hidden' );
			if($this->is_element('enable_comment_notification')) $this->change_element_type( 'enable_comment_notification', 'hidden' );
			if($this->is_element('enable_front_end_posting')) $this->change_element_type('enable_front_end_posting', 'hidden');
			
			$this->set_display_name('has_issues','Issue-based?');
			$this->add_comments('has_issues', form_comment('Choose "no" for standard chronological display of posts.<br />Choose "yes" to group posts together into issues, similar to in a print-based magazine (note that you will need to set up at least one issue before any posts will appear).'));
			
			$this->set_display_name('has_sections','Broken into sections?');
			$this->add_comments('has_sections', form_comment('Choose "no" for standard chronological display of posts.<br />Choose "yes" to group posts together into sections (note that you will need to create sections for your publication)'));
			
			
			if (!$this->get_value( 'publication_type' )) $this->set_value( 'publication_type', 'blog' );
			$this->add_required('publication_type');
			$this->change_element_type( 'date_format', 'select_no_sort', array('options' => array('F j, Y \a\t g:i a' => date('F j, Y \a\t g:i a'),
																								  'n/d/y \a\t g:i a' => date('n/d/y \a\t g:i a'),
																								  'l, F j, Y' => date('l, F j, Y'),
																								  'F j, Y' => date('F j, Y'),
																								  'n/d/y' => date('n/d/y'), 
																								  'n.d.y' => date('n.d.y'),
																								  'j F Y' => date('j F Y'),
																								  'j F Y \a\t  g:i a' => date('j F Y \a\t  g:i a'),
																								  'j F Y \a\t  g:i a' => date('j F Y \a\t  H:i'), )));
			$this->add_element('comment_comment','comment',array('text'=>'<h4>Commenting</h4>'));
			$this->add_element('posting_comment','comment',array('text'=>'<h4>Posting on the public site</h4>'));
			$this->add_element('issue_section_comment','comment',array('text'=>'<h4>Issues and sections</h4>'));
			
			// social sharing
			if ($this->is_element('enable_social_sharing'))
			{
				$this->add_element('social_sharing_comment','comment',array('text'=>'<h4>Social Sharing</h4>'));
			}
			else
			{
				trigger_error('You need to run Reason 4.3 to 4.4 upgrade scripts to add social sharing to Reason publications');
			}
			
			$this->set_order(array('name', 'unique_name', 'publication_type', 'posts_per_page', 'blog_feed_string', 'description', 'date_format', 'social_sharing_comment', 
								   'enable_social_sharing', 'posting_comment', 'allow_front_end_posting', 'notify_upon_post', 'hold_posts_for_review', 'comment_comment', 
								   'allow_comments', 'notify_upon_comment', 'hold_comments_for_review', 'issue_section_comment', 'has_issues','has_sections'));
		}

		function alter_display_names()
		{
			$this->set_display_name('name','Publication Name');
			$this->set_display_name('blog_feed_string','RSS feed URL');		
		}
	
		function alter_comments()
		{
			$this->set_comments('hold_comments_for_review',form_comment('Choose "yes" to moderate comments; choose "no" to allow comments to be unmoderated. In either case, you will be able to delete comments after they are made.'));
			$this->set_comments('hold_posts_for_review',form_comment('Choose "yes" to moderate posts; choose "no" to allow posts to be unmoderated. In either case, you will be able to delete posts after they are made.'));
			$this->add_comments('blog_feed_string',form_comment('The URL snippet that this blog / publication will use for its RSS feed.'));
			$this->set_comments('date_format',form_comment('Posts on this publication will use the date format that you select to show the date (and/or) time of publication.'));
			$this->add_comments('notify_upon_post',form_comment('Who should be notified when a post is added to this publication? Enter usernames or email addresses, separated by commas. Leave this field blank if you don\'t want any notification to be sent.'));
			$this->add_comments('notify_upon_comment',form_comment('Who should be notified when a comment is added to this publication? Enter usernames or email addresses, separated by commas. Leave this field blank if you don\'t want any notification to be sent.'));
			$this->add_comments('allow_front_end_posting',form_comment('Check to enable simple posting on the publication itself (you can always post from inside Reason)'));
			

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

