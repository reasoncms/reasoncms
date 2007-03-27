<?php
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'BlogManager';

	class BlogManager extends ContentManager
	{
		function alter_data()
		{
			//$this->change_element_type('commenting_state', 'hidden');
			$this->change_element_type('enable_front_end_posting', 'hidden');
			$this->change_element_type( 'keywords','hidden' );

			$this->add_element('allow_comments', 'checkbox');
			$this->add_element('allow_front_end_posting', 'checkbox');

			$this->add_required('hold_comments_for_review');
			$this->add_required('posts_per_page');
			$this->add_required('blog_feed_string');
			
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

		}

	
		function alter_display_names()
		{
			$this->set_display_name('name','Publication Name');
			$this->set_display_name('blog_feed_string','RSS feed URL');		
		}
	
		function alter_comments()
		{
			$this->set_comments('hold_comments_for_review',form_comment('Choose "yes" to moderate comments; choose "no" to allow comments to be unmoderated. In either case, you will be able to delete comments after they are made.'));
			$this->add_comments('blog_feed_string',form_comment('The URL snippet that this blog / publication will use for its RSS feed.'));

			if( $this->is_new_entity() || user_is_a( $this->admin_page->user_id, id_of( 'admin_role' ) ) )
			{
				$this->add_comments('blog_feed_string',form_comment('Only lowercase letters and underscores are allowed in this field.'));
				if(!user_is_a( $this->admin_page->user_id, id_of( 'admin_role' )))
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
				if( !ereg( "^[0-9a-z_]*$" , $this->get_value('blog_feed_string') ) )
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
			if(!$this->get_value('allow_front_end_posting')||!$this->get_value('allow_comments'))
			{		
				//if they are, check to make sure that we've borrowed or own the nobody group.
				if(!(site_borrows_entity( $this->get_value('site_id'), id_of('nobody_group')) || site_owns_entity( $this->get_value('site_id'), id_of('nobody_group'))))
				{
					//if not, borrow it.
					create_relationship($this->get_value('site_id'), id_of('nobody_group'), get_borrow_relationship_id(id_of('group_type')));
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
			
			if($this->get_value('has_issues') == 'yes' && !$this->site_has_type(id_of('issue_type')))
			{
				$this->add_type_to_site(id_of('issue_type'));
			}
			if($this->get_value('has_sections') == 'yes' && !$this->site_has_type(id_of('news_section_type')))
			{
				$this->add_type_to_site(id_of('news_section_type'));
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

