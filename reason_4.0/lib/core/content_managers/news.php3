<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register the content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'news_handler';
	
	/**
	 * A content manager for posts
	 */
	class news_handler extends ContentManager 
	{
		var $publications; //pub_id=>pub_entity
		var $issues = array();	//[$publicationID][$issueID]=issue_entity;
		var $news_sections = array();   //[$publicationID][$sectionID]=section_entity;

		function init_head_items() {
			$this->head_items->add_javascript(WEB_JAVASCRIPT_PATH.'content_managers/news_content_manager.js');
		}

/////
// ALTER_DATA & HELPER METHODS
////
		function alter_data() { // {{{	
			if ( $this->is_new_entity() ) 
				$this -> add_required ('status');
				
			$this -> add_required ('datetime');
			//$this -> add_required ('keywords');
			$this -> add_required ('release_title');
			$this -> add_required ('news_type');
			$this -> add_required ('status');
			$this -> add_required ('show_hide');
			
			$this -> set_display_name ('release_title', 'Title');
			$this -> set_display_name ('datetime', 'Date');
			$this -> set_display_name ('show_hide', 'Show or Hide?');
			if($this->_is_element('enable_comment_notification')) $this -> set_display_name ('enable_comment_notification', 'Email me when new comments are added to this news item:');
		
			$this -> set_comments ('name', form_comment('A short name that describes the news item. This is for internal use.'));
			$this -> set_comments ('release_title', form_comment('The actual title of the item -- this is the one that shows up on the public site.'));
			$this -> set_comments ('description', form_comment('A brief summary of the news item; this is what appears on lists of news items'));
			$this -> set_comments ('content', form_comment('The content of the news item.'));
			$this -> set_comments ('show_hide', form_comment('Hidden items will not show up in the news listings.'));

			if ( !$this -> get_value('datetime') )
				$this -> set_value( 'datetime', time() );
			if (!$this -> get_value('show_hide')) 
				$this -> set_value('show_hide', 'show');
			if (!$this -> get_value('status')) 
				$this -> set_value('status', 'published');
				
			$this->change_element_type( 'show_hide', 'hidden');
			$this->change_element_type('names', 'hidden');
			$this->change_element_type('subtitle', 'hidden');
			$this->change_element_type('author_description', 'hidden');	
# Make this un-hidden again when we have actually IMPLEMENTED comment notification
			if($this->_is_element('enable_comment_notification')) $this->change_element_type('enable_comment_notification', 'hidden');
			
			
			//make more sophisticated changes to the content manager
			$this->alter_commenting_state_field();
			
			$this->lokify();
			
			
			$this->make_publication_related_fields();
			$this->set_values_for_publication_related_fields();

			//put the publication-related elements into a sensible order
			foreach($this->publications as $pub_id=>$pub_entity)
			{
				if(array_key_exists($pub_id, $this->_elements))
				{
					$publication_elements[]=$pub_id.'-hr';
					$publication_elements[]=$pub_id;
				}
				if(array_key_exists($pub_id.'-issues', $this->_elements))
					$publication_elements[]=$pub_id.'-issues';
				if(array_key_exists($pub_id.'more_than_one_issue_comment', $this->_elements))
					$publication_elements[]=$pub_id.'more_than_one_issue_comment';
				if(array_key_exists($pub_id.'-sections', $this->_elements))
					$publication_elements[]=$pub_id.'-sections';
				if(array_key_exists($pub_id.'more_than_one_section_comment', $this->_elements))
					$publication_elements[]=$pub_id.'more_than_one_section_comment';
			}		
			$publication_elements[] = 'pubs_last_hr';
	
			// does the site have categories? if so, lets make it easy to associate a post with categories.
			$cat_es = new entity_selector($this->get_value('site_id'));
			$cat_es->description = 'Finding the categories on this site';
			$cat_es->add_type(id_of('category_type'));
			$categories = $cat_es->run_one();
			if (!empty($categories))
			{	
				$this->add_relationship_element('choose_categories', id_of('category_type'), relationship_id_of('news_to_category'), 'right', 'checkbox');
			}

			// add some custom UI to associate embed handlers
			$embedHandlers = $this->get_site_entities('news_post_embed_handler_type');
			if (!empty($embedHandlers)) {
				$this->add_relationship_element('embed_handler', id_of('news_post_embed_handler_type'), relationship_id_of('news_post_to_embed_handler'), 'right', 'select');
				$this->set_display_name('embed_handler', 'Template');
				$this->set_element_properties( 'embed_handler', array('empty_value_label' => 'Default' ) );
				// $this -> set_comments ('embed_handler', form_comment('If a template doesn\'t seem to be working as expected, please contact a Reason administrator. New publications may need to have templates enabled.'));
			}

			// add some custom UI to make the "is a story a link" functionality a little easier on the content folks
			$this->add_element('is_link_story', 'radio_no_sort', array(
				"display_name" => "Type of Post",
				"options" => array(
					0 => "Standard <span class='smallText'>(Full content is part of post)</span>",
					1 => "External Link <span class='smallText'>(Full content is somewhere else on the web)</span>",
				)
			));
			$this->set_value("is_link_story", $this->get_value("linkpost_url") == "" ? 0 : 1);
			$this->set_display_name("linkpost_url", "Link URL");

			$orgs = $this->get_site_entities('organization_type');
			if (!empty($orgs)) {
				$this->add_relationship_element('link_org', id_of('organization_type'), relationship_id_of('news_post_to_url_organization'), 'right', 'select');
				$this->set_display_name('link_org', 'Link Organization');
			}
			
			$order = array ('name', 
							'release_title',
							'subtitle', 
							'news_type', 
							'author', 
							'author_description', 
							'location', 
							'datetime', 
							'description',
							'is_link_story',
							'embed_handler',
							'content',
							'linkpost_url',
							'link_org',
							'choose_categories',
							'keywords', 
							'names', 
							'contact_name', 
							'contact_email', 
							'contact_title', 
							'contact_phone', 
							'release_number', 
							'status', 
							'show_on_front_page', 
							'publish_start_date', 
							'publish_end_date', 
							'news_to_sport', // delete?
							'news_to_image', // delete?
							'unique_name', 
							'commenting_state',
							'enable_comment_notification',
							'pubs_heading',);
							
			$this -> set_order (array_merge($order, $publication_elements));		
			$this->make_site_specific_changes();			
		} // }}}

		function get_site_entities($entity_type) {
			$entityId = id_of($entity_type, true, false);
			if ($entityId == 0) {
				return null;
			} else {

				$es = new entity_selector($this->get_value('site_id'));
				$es->description = 'Finding the entities of type ' . $entity_type . ' on this site';
				$es->add_type(id_of($entity_type));
				$es->set_order('entity.name ASC');
				$entities = $es->run_one();
				return $entities;
			}
		}
		
		function make_site_specific_changes()
		{
			// the following stuff should be hidden on sites
			if (!$this -> get_value('news_type')) $this -> set_value('news_type', 'Press Release');
			$this -> change_element_type ('news_type', 'hidden');
			$this->change_element_type( 'contact_name', 'hidden');
			$this->change_element_type( 'contact_email', 'hidden');
			$this->change_element_type( 'contact_title', 'hidden');
			$this->change_element_type( 'contact_phone', 'hidden');
			$this->change_element_type( 'show_on_front_page', 'hidden');
			$this->change_element_type( 'publish_start_date', 'hidden');
			$this->change_element_type( 'publish_end_date', 'hidden');
			$this->change_element_type( 'release_number', 'hidden');
			$this->change_element_type( 'release_number', 'hidden');
			$this->change_element_type( 'location', 'hidden');
			$this->change_element_type( 'show_hide', 'hidden');
			$this -> set_comments ('status', form_comment('"Published" items will appear on your site; "pending" items will be hidden.'));
		}
		
		function lokify()
		{
			$editor_name = html_editor_name($this->admin_page->site_id);
			$wysiwyg_settings = html_editor_params($this->admin_page->site_id, $this->admin_page->user_id);
			$wysiwyg_settings_desc = $wysiwyg_settings;
			/* if(strpos($editor_name,'loki') === 0)
			{
				$wysiwyg_settings_desc['widgets'] = array('strong','em','lists','link','assets');
			} */
			
			$this -> change_element_type ('description', $editor_name , $wysiwyg_settings_desc );
			$this -> set_comments ('description', form_comment('A brief summary of the news item; this is what appears on lists of news items'));
			$this->change_element_type( 'content' , $editor_name , $wysiwyg_settings );
			$this -> set_comments ('content', form_comment('The content of the news item. Please do not include #### at the end of the content'));
		}

		function make_publication_related_fields()
		{
			//find all the publications associated with this site
			$es = new entity_selector($this->get_value('site_id'));
			$es->description = 'Finding the publications on this site';
			$es->add_type(id_of('publication_type'));
			$es->set_order('entity.name ASC');
			$this->publications = current($es->run());
			
			if (empty($this->publications)) return false;
			
			$this->add_element('pubs_heading','comment',array('text'=>'<h3>Publications</h3>'));
			$this->add_element('pubs_last_hr','hr');
			
			foreach($this->publications as $pub_id=>$pub)
			{
				$this->add_element($pub_id.'-hr', 'hr');
				$this->add_element($pub_id, 'checkboxfirst');
				$this->set_display_name($pub_id, $pub->get_value('name'));
				$this->init_issues($pub_id);
				$this->init_news_sections($pub_id);
				
				if(!empty($this->issues[$pub_id]))
				{
					$issue_names = array();
					foreach($this->issues[$pub_id] as $issue_id=>$issue)
					{
						$issue_names[$issue_id] = $issue->get_value('name');
					}
					$this->add_element($pub_id.'-issues', 'select_no_sort', array('options' => $issue_names, 'display_name'=>'Issue'));
				}

				if(!empty($this->sections[$pub_id] ))
				{
					$section_names = array();
					foreach($this->sections[$pub_id] as $section_id=>$section)
					{
						$section_names[$section_id] = $section->get_value('name');
					}
					$this->add_element($pub_id.'-sections','select_no_sort',array( 'options' => $section_names, 'display_name'=>'Section'));
				}
			}
		}
		
		function init_issues($pub_id)
		{
			if($this->publications[$pub_id]->get_value('has_issues') == 'yes')
			{
				$es = new entity_selector( $this->get_value('site_id') );
				$es->description = 'Selecting issues for this publication';
				$es->add_type( id_of('issue_type') );
				$es->add_left_relationship( $pub_id, relationship_id_of('issue_to_publication') );
				$es->set_order('dated.datetime DESC');
				$this->issues[$pub_id] = $es->run_one();
			}
		}
		
		function init_news_sections($pub_id)
		{
			if($this->publications[$pub_id]->get_value('has_sections') == 'yes')
			{
				$es = new entity_selector( $this->get_value('site_id') );
				$es->description = 'Selecting news sections for this publication';
				$es->add_type( id_of('news_section_type'));
				$es->add_left_relationship( $pub_id, relationship_id_of('news_section_to_publication') );
				$es->set_order('entity.name ASC');
				$this->sections[$pub_id] = current($es->run());
			}
		}
		
		
		function set_values_for_publication_related_fields()
		{
			$left_relationships = $this->entity->get_left_relationships();
			
			#	What if there's not any publications for the site?
			if(!empty($this->publications) && count($this->publications) == 1)
			{
				$this->set_value_for_only_publication();
			}
			else
			{
				if(!empty($left_relationships['news_to_publication']))
				{
					foreach($left_relationships['news_to_publication'] as $publication)
					{
						if(array_key_exists($publication->id(), $this->publications))
						{
							$this->set_value($publication->id(), 'true');
						}
					}
				}
			}
#			we're doing this outside the publication loop in case we've somehow managed to set these vals without setting a publication val.  
#			can that happen?  is this unnecessarily inefficient?
			foreach($this->publications as $pub_id=>$pub_entity)
			{
				//set issue value, if it exists - don't need to worry about setting value if there's only one, since we're using a select
				if(!empty($this->issues[$pub_id]) && !empty($left_relationships['news_to_issue']))
				{
					$issue_options = array();
					foreach($left_relationships['news_to_issue'] as $related_issue)
					{
						if(array_key_exists($related_issue->id(), $this->issues[$pub_id]))
						{
							
								$issue_options[] = $related_issue->id();
						}
					}
					if(count($issue_options) > 1)
					{
						$issue_names = array();
						foreach($issue_options as $issue_id)
						{
							$issue_names[] = $this->issues[$pub_id][$issue_id]->get_value('name');
						}
						$this->change_element_type($pub_id.'-issues', 'hidden');
						$this->add_element($pub_id.'more_than_one_issue_comment', 'comment', array('text'=>'<strong>Issues:</strong> '.implode(', ',$issue_names).'<br />To change this post\'s issues, use the "Assign this story to an issue" link at the top of the page.'));			
					}
					else
					{
						$this->set_value($pub_id.'-issues', current($issue_options));
					}
				}
				//if we've selected a publication that only has one section, select that section
				if($this->get_value($pub_id) && !empty($this->sections[$pub_id]) && count($this->sections[$pub_id]) == 1)
				{
					$first_section = reset($this->sections[$pub_id]);
					$this->set_value($pub_id.'-sections', $first_section->id()); 
				} 
				//otherewise set the section value if a relationship exists
				elseif(!empty($this->sections[$pub_id]) && !empty($left_relationships['news_to_news_section']))
				{
					$section_options = array(); 
					foreach($left_relationships['news_to_news_section'] as $related_section)
					{
						if(array_key_exists($related_section->id(), $this->sections[$pub_id]))
						{
							$section_options[] = $related_section->id();
						}
					}
					if(count($section_options) > 1)
					{
						$section_names = array();
						foreach($section_options as $section_id)
						{
							$section_names[] = $this->sections[$pub_id][$section_id]->get_value('name');
						}
						$this->change_element_type($pub_id.'-sections', 'hidden');
						$this->add_element($pub_id.'more_than_one_section_comment', 'comment', array('text'=>'<strong>Sections:</strong> '.implode(', ',$section_names).'<br />To change this post\'s sections, use the "Assign to a News Section" link at the top of the page.'));
					}
					else
					{
						$this->set_value($pub_id.'-sections', current($section_options));
					}
				}
			}
		}
	
		function set_value_for_only_publication()
		{
			$first_pub = reset($this->publications);
			$this->set_value($first_pub->id(), 'true');
		}
		
		function alter_commenting_state_field()
		{
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_relation('entity.unique_name = "publication_type"');
			$es->add_right_relationship($this -> get_value('site_id'), relationship_id_of('site_to_type'));
			$es->set_num(1);
			$types = $es->run_one();
			if(empty($types))
			{
				$this->change_element_type('commenting_state','hidden');
			}
			else
			{
				$this -> add_required ('commenting_state');
				$this -> set_display_name ('commenting_state', 'Allow comments');
				$this->change_element_type('commenting_state', 'select_no_sort', array('options'=>array('on'=>'Yes', 'off'=>'No'), 'add_empty_value_to_top' => false));
				$this->add_comments('commenting_state', form_comment('This setting will only apply if this news/post item is used on a publication that supports comments, such as a blog.'));
			}
			if ( !$this -> get_value('commenting_state') )
			{
				$this -> set_value( 'commenting_state', 'on' );
			}
		}

//////
//  RUN_ERROR_CHECKS && HELPER METHODS
//////

		function run_error_checks()
		{
			parent::run_error_checks();
			$this->run_publication_issue_and_section_checks();
			$this->run_publication_association_error_check();
			$this->run_is_story_a_link_error_check();
		}

		// if user said story is not a link, we need to clear out linkpost_url - we use that to preset the "is it a link" radio
		// next time they come in.
		//
		// basically if a news/post has a value in linkpost_url it's a link. otherwise it ain't.
		//
		// we could also clear out content/description if the story IS a link but that's not necessary and having some history there
		// is potentially useful, at some small risk of confusion. Realistically it's not like news/posts are likely to switch back
		// and forth here so not worth spending too much time thinking about it!
		function run_is_story_a_link_error_check()
		{
			if (!isset($_REQUEST["is_link_story"]) || intval($_REQUEST["is_link_story"]) == 0) {
				$this->set_value("linkpost_url", "");
			}
		}
		
		/**
		 * Makes sure the news item is associated with at least one publication
		 *
		 * @todo it seems this only works if there is exactly one publication on the site - is that desired???
		 */
		function run_publication_association_error_check() 
		{
			foreach($this->publications as $pub_id=>$pub_entity)
			{
				if($this->get_value($pub_id)) return true;
			}
			if(!empty($this->publications))
			{
				//this is a bit of a hack - couldn't figure out what to point to for "jump to error"
				$first_pub= reset($this->publications);
				// this causes error - site may not have a publication ...
				$this->set_error($first_pub->id(), 'This news item needs to be associated with at least one publication.' );
			}
		}
		
		/**
		 * Makes sure that if a publication is selected that has issues and/or sections, that proper associations are present
		 */
		function run_publication_issue_and_section_checks()
		{
			foreach($this->publications as $pub_id=>$pub_entity)
			{
				if($this->get_value($pub_id))
				{
					//make sure they have a relationship with at least one issue -- remember that if they have a relationship with more than one issue, the issue element will be hidden
#					//we can't have an empty issue_val, can we?  since it's a select?  										
					$issue_val = $this->get_value($pub_id.'-issues');
					if(!empty($this->issues[$pub_id]) && $this->_elements[$pub_id.'-issues']->type != 'hidden' && empty($issue_val) )
					{
						$this->set_error($pub_id.'-issues', 'This news item needs to be associated with at least one issue of "'.$pub_entity->get_value('name').'".' );					
					}
					//make sure they have a relationship with at least one section -- remember that if they have a relationship with more than one section, the section element will be hidden
					$section_val = $this->get_value($pub_id.'-sections');
					if(!empty($this->sections[$pub_id]) && empty($section_val) && $this->_elements[$pub_id.'-sections']->type != 'hidden')
					{
						$this->set_error($pub_id.'-sections', 'This news item needs to be associated with at least one section of "'.$pub_entity->get_value('name').'".' );					
					}
				}
			}
		}


	
/////
// PROCESS & HELPER METHODS
////
	
		function process()
		{
			$this->handle_new_associations();
			$this->handle_deletions();
			parent::process();
		}
		
		
		function handle_new_associations()
		{
			foreach($this->publications as $pub_id=>$pub_entity)
			{
				if($this->get_value($pub_id) && !$this->entity->has_left_relation_with_entity($pub_entity, 'news_to_publication'))
				{
					$this->make_new_association('news_to_publication', $pub_id);
				}
				//this should accomplish what we want, but what we more precisely want is something more to the effect of if(issue value != id of issue we have relationship with)
				if($this->get_value($pub_id) && $this->get_value($pub_id.'-issues') && !$this->entity->has_left_relation_with_entity(new entity($this->get_value($pub_id.'-issues')), 'news_to_issue'))
				{
					//if we see the issue field at all, there should be 0 or 1 issues associated, and we know we are either making the first association or REPLACING the old one.
					$this->delete_all_issue_associations_for_this_publication($pub_id);
					$this->make_new_association('news_to_issue', $this->get_value($pub_id.'-issues'));
				}
				if($this->get_value($pub_id) && $this->get_value($pub_id.'-sections') && !$this->entity->has_left_relation_with_entity(new entity($this->get_value($pub_id.'-sections')), 'news_to_news_section'))
				{
					$this->delete_all_section_associations_for_this_publication($pub_id);
					$this->make_new_association('news_to_news_section', $this->get_value($pub_id.'-sections'));
				}
				if($this->get_value($pub_id))
				{
					$this->extra_associations($pub_entity);
				}
			}
		}
		
		function extra_associations(&$pub_entity)
		{
		}
	
		function handle_deletions()
		{
			foreach($this->publications as $pub_id=>$pub_entity)
			{
				//figure out if we're supposed to delete an association with a publication.  If we are, also delete associations with issues & sections.	
#				if(!$this->get_value($pub_id) && $this->entity->has_left_relation_with_entity(new entity($pub_id), 'news_to_publication'))
				if(!$this->get_value($pub_id))
				{
					//delete association with this publication, if it exists
					if($this->entity->has_left_relation_with_entity($pub_entity, 'news_to_publication'))
						$this->delete_instance_of_relationship($this->entity->id(), $pub_id, 'news_to_publication');
					//delete all associations with this publication's issues
					$this->delete_all_issue_associations_for_this_publication($pub_id);
					//delete all associations with this publication's news sections
					$this->delete_all_section_associations_for_this_publication($pub_id);
					$this->extra_deletions($pub_entity);
				}				
				//issue & section associations for associated publications cannot be deleted, only replaced, so this is taken care of in handle_new_associations
			}
		}
		
		// hook for extra deletion actions for local extensions
		function extra_deletions(&$pub_entity)
		{
		}

		function delete_instance_of_relationship($entity_a_id, $entity_b_id, $reln_unique_name)
		{
			delete_relationships( array( 'entity_a' => $entity_a_id, 'entity_b' => $entity_b_id,'type' => relationship_id_of( $reln_unique_name )));
		}
		
#		now that this function only contains the create_relationship command, it should probably be deleted ... but we'll leave it in for now, in case we want to change it.		
		function make_new_association($reln_unique_name, $entity_b_id)
		{		
			//create a relationship with the new entity 
			create_relationship($this->entity->id(), $entity_b_id, relationship_id_of($reln_unique_name));
		}
		
		function delete_all_issue_associations_for_this_publication($publication_id)
		{
			if(!empty($this->issues[$publication_id]))
			{
				foreach($this->issues[$publication_id] as $issue_id=>$issue_entity)
				{
					if($this->entity->has_left_relation_with_entity($issue_entity, 'news_to_issue'))
					{
						$this->delete_instance_of_relationship($this->entity->id(), $issue_id, 'news_to_issue');
					}
				}
			}
		}
		
		function delete_all_section_associations_for_this_publication($publication_id)
		{
			if(!empty($this->sections[$publication_id]))
			{
				foreach($this->sections[$publication_id] as $section_id=>$section_entity)
				{
					if($this->entity->has_left_relation_with_entity($section_entity, 'news_to_news_section'))
					{
						$this->delete_instance_of_relationship($this->entity->id(), $section_id, 'news_to_news_section');
					}
				}
			}
		}
	}
?>
