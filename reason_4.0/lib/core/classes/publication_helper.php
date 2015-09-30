<?php
/**
 * @package reason
 * @subpackage classes
 */

/* 



include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/admin_actions.php');
reason_include_once( 'classes/group_helper.php' );
	
//need to decide whether this will include "get" methods for simple variables; e.g. number of posts, comments moderated, etc.
//pros:  Gives more control of data via publication type; for example, if something isn't issued but supposedly has no pagination, could change. (??)
//cons:  Why duplicate when it's already in the entity?  And shouldn't we want to have the data RIGHT in the first place?
	
	
	class publication_helper
	{
		var $publication; 		//publication entity.
		var $site_id; 			//id of the site using the publication
		var $issues = array();
		var $news_sections = array();
		var $post_group;
		var $comment_group;
		var $post_group_helper;
		var $comment_group_helper;
		var $publication_type;
		var $num_per_page;

		
		function publication_helper($publication_entity, $site_id)
		{
			//possibly want "set_by_entity" and "set_by_id" functions instead
			$this->publication = $publication_entity;
			$this->site_id= $site_id;
		}
		
		function get_publication_type()
		{
			if(empty($this->publication_type))
			{
				$this->publication_type = $this->publication->get_value('publication_type');
			}
			
			return $this->publication_type;
		}
	
/*		function get_no_items_text()
		{
			switch($this->get_publication_type())
			{
				case "Blog": 
					return "This blog does not have any posts.";
					break;
				case "Issued Newsletter":
					return "This issue does not have any articles.";
					break;
				case "Newsletter":
					return "This newsletter does not have any articles.";
					break;
				default:
					return "This publication does not have any news items.";
			}
		} */
	
	
		//this should actually pay some attention to pagination, I think ... if
		//it's an issued newsletter that has no sections, could choose not to paginate, in which case ....
		//well, maybe it doesn't matter and this shouldn't be here.  Decide later.
/*		function get_num_per_page()
		{
			if(empty($this->num_per_page))
			{
				//has a default value, so we don't need to check to make sure it's filled.  
				//no.  actually it looks like we neeed it.
				//maybe this method should just be ditched.
				$this->publication->get_value('posts_per_page');
			}
			
			return $this->num_per_page;
			
		} */
/*	
	
	
		function has_issues()
		{
			if($this->publication->get_value('publication_type') == "Issued Newsletter")
			{
				$issues = $this->get_issues();
				if(!empty($issues))
					return true;
			}
			return false;
		}
		
		function get_issues()
		{
			if(empty($this->issues))
			{
				if($this->publication->get_value('publication_type') == "Issued Newsletter")
				{
					if(empty($this->issues))
					{
						$es = new entity_selector( $this->site_id );
						$es->description = 'Selecting issues for this publication';
						$es->add_type( id_of('issue_type') );
						$es->add_left_relationship( $this->publication->id(), relationship_id_of('issue_to_blog') );
						$temp = $es->run();
						$this->issues = current($temp);
					}
				}
			}
			return $this->issues;
		}
		
		//better name?
		function get_issues_organized_by_date()
		{
			$issues_by_date = array();
			$issues = $this->get_issues();
			if(!empty($issues))
			{
				foreach($issues as $issue_entity)
				{
					$issues_by_date[$issue_entity->get_value('datetime')] = $issue_entity;
				}
			}
			return $issues_by_date;
		}
		
		function get_most_recent_issue()
		{
			$issues_by_date = $this->get_issues_organized_by_date();
			rsort($issues_by_date);
			return current($issues_by_date);
		}

		function has_news_sections()
		{
			$sections = $this->get_news_sections();
			if(!empty($sections))
				return true;
			else
				return false;
		}
		
		function get_news_sections()
		{
			if(empty($this->news_sections))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Selecting news sections for this publication';
				$es->add_type( id_of('news_section_type'));
				$es->add_left_relationship( $this->publication->id(), relationship_id_of('news_section_to_blog') );
				$this->news_sections=current($es->run());
			}
			return $this->news_sections;
		}
		
		function get_post_group()
		{
			if(empty($this->post_group))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Getting groups for this publication';
				$es->add_type( id_of('group_type') );
				$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_authorized_posting_group') );
				$groups = $es->run_one();
				if(!empty($groups))
				{
					$this->post_group = current($groups);
				}
				else
				{
					trigger_error('No posting group assigned to publication id '.$this->publication->id());
					return false;
				}
			}
			
			return $this->post_group;
		}
		
		function get_post_group_helper()
		{
			if(empty($this->post_group_helper))
			{
				$group = $this->get_post_group();
				$this->post_group_helper = new group_helper();
				$this->post_group_helper->set_group_by_entity($group);
			}
			return $this->post_group_helper;
		}
	
		function get_comment_group()
		{
			if(empty($this->comment_group))
			{
				$es = new entity_selector( $this->site_id );
				$es->description = 'Getting groups for this blog';
				$es->add_type( id_of('group_type') );
				$es->add_right_relationship( $this->publication->id(), relationship_id_of('publication_to_authorized_commenting_group') );
				$es->set_num(1);
				$groups = $es->run_one();	
				if(!empty($groups))
				{
					$this->comment_group = current($groups);
				}
				else
				{
					trigger_error('No commenting group assigned to publication id '.$this->publication->id());
					return false;
				}
			}
			return $this->comment_group;
		}

		function get_comment_group_helper()
		{
			if(empty($this->comment_group_helper))
			{
				$group = $this->get_comment_group();
				$this->comment_group_helper = new group_helper();
				$this->comment_group_helper->set_group_by_entity($group);
			}
			return $this->comment_group_helper;
		}
		
		function get_comment_moderation_state()
		{
			if($this->publication->get_value('hold_comments_for_review') == 'yes')
			{
				return true;
			}
			else
				return false;
		}

}
?>
