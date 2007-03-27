<?php
	$GLOBALS[ '_module_class_names' ][ 'blog' ] = 'BlogModule';
	include_once( 'reason_header.php' );
	reason_include_once( 'minisite_templates/modules/news2.php' );
	reason_include_once( 'function_libraries/admin_actions.php');
	reason_include_once( 'classes/group_helper.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	
	reason_include_once( 'minisite_templates/modules/blog/display_comments_submodule.php');
	reason_include_once( 'minisite_templates/modules/blog/add_comments_submodule.php');
	reason_include_once( 'minisite_templates/modules/blog/comments_added_submodule.php');
	reason_include_once( 'minisite_templates/modules/blog/blog_post_submission_form.php');
	reason_include_once( 'minisite_templates/modules/blog/return_to_list_submodule.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'BlogModule';

///////////////////////////////
// Things to do:
//		- Get the comments and posting forms to have the correct "last edited by" information
//		- Allow front-end editing of blog posts by both back end users and the suthor of the post
// 	   	- Themes, themes, themes (probably should tackle site themes first)
//		- Automatic site user creation upon submittion of item (done for comments)
//		- Frontend comment adding capabilities (done)
//		- RSS feeds for posts and comments
//		- Hold posts for review (done 8.8.2005 -- mr)
//		- Turn on/off commenting on a post-by-post basis (done 8.5.2005 -- mr)
///////////////////////////////



	////////////////////////
	//BLOG MINISITE MODULE
	////////////////////////
	class BlogModule extends News2Module
	{
		var $blog;			//entity of the current blog
		var $user_netID; 	//current user's net_ID
		var $session;		//reason session
	
		var $style_string = 'blog';
		var $no_items_text = 'This blog does not have any posts yet.';
		var $jump_to_item_if_only_one_result = false;
		var $date_format = 'F j, Y \a\t g:i a';
		
		// Filter settings
		var $use_filters = true;
		var $filter_types = array(	'category'=>array(	'type'=>'category_type',
														'relationship'=>'news_to_category',
													),
								);
		var $search_fields = array('entity.name','chunk.content','meta.keywords','meta.description','chunk.author');
		var $search_field_size = 10;
		
		var $additional_query_string_frags = array ('comment_posted');	
		
		var $submodules = array('news_comments_added'=>array('title'=>'Your comment has been added.',
														'title_tag'=>'h4',
														'wrapper_class'=>'commentAdded',
													),
								'news_content'=>array(	'title'=>'',
														'title_tag'=>'',
														'wrapper_class'=>'text',
														'date_format' => 'F j, Y \a\t g:i a',
													),
								'news_images'=>array(	'title'=>'Images',
														'title_tag'=>'h4',
														'wrapper_class'=>'images',
													),
								'news_assets'=>array(	'title'=>'Related Documents',
														'title_tag'=>'h4',
														'wrapper_class'=>'assets',
													),
								'news_related'=>array(	'title'=>'Related Stories',
														'title_tag'=>'h4',
														'wrapper_class'=>'relatedNews',
													),
								'news_categories_submodule'=>array(	'title'=>'Categories',
														'title_tag'=>'h4',
														'wrapper_class'=>'categories',
													),
								'news_return_to_list'=>array('title'=>'Return to',
														'wrapper_class'=>'returnToList',
													),
								'news_display_comments_submodule'=>array(	'title'=>'Comments',
														'title_tag'=>'h4',
														'wrapper_class'=>'comments',
														'date_format' => 'F j, Y \a\t g:i a',
													),
								'news_add_comments'=>array(	'title'=>'Add a Comment',
														'title_tag'=>'h4',
														'public'=>false,
														'wrapper_class'=>'addCommentForm',
													),
								);
		var $class_vars_pass_to_submodules = array('blog');
		var $make_current_page_link_in_nav_when_on_item = true;
		var $back_link_text = 'Return to ';
		var $feed_url;
		
		/**	
		* Extends the News2Module alter_es() function; adds the relationship of 'news_to_publication' to the entity selector.
		*/
		function alter_es() // {{{
		{
			parent::alter_es();
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Selecting blogs for this page';
			$es->add_type( id_of('publication_type') );
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_publication') );
			$es->set_num( 1 );
			$blogs = $es->run_one();
			if(!empty($blogs))
			{
				$this->blog = current($blogs);
				$this->es->add_left_relationship( $this->blog->id(), relationship_id_of('news_to_publication') );
				if($this->blog->get_value('posts_per_page'))
				{
					$this->num_per_page = $this->blog->get_value('posts_per_page');
				}
				$this->back_link_text = $this->back_link_text.$this->blog->get_value('name');
	
			}
			else
			{
				trigger_error('No blogs associated with blog page');
				// We should find a better way to make sure that no news items are returned if there is no blog associated with the page
				// because this is hacktastic
				$this->es->add_relation( '1 = 2' );
			}
			
		} // }}}
		function has_content() // {{{
		{
			if(empty($this->blog))
			{
				return false;
			}
			else
			{
				return true;
			}
		} // }}}
		
		/**	
		* Returns the text for the "add blog post" link
		* Overloads the Generic3 hook.
		*/	
		function get_add_item_link()
		{
			$this->user_netID = $this->get_authentication();
			
			$groups = $this->get_post_groups();
			$ph = new group_helper();
			$ph->set_group_by_entity(current($groups));
			if($ph->requires_login()) // login required to post
			{
				if(empty($this->user_netID)) // not logged in
				{
					return '';
				}
				else // logged in
				{
					if($ph->has_authorization($this->user_netID)) // has authorization to post
					{
						return $this->make_add_item_link();
					}
					else // does not have authorization to post
					{
						return '';
					}
				}
			}
			else // No login required to post
			{
				return $this->make_add_item_link();
			}
		}
		
		function make_add_item_link()
		{
			$link = array('add_item=true');
			if(!empty($this->textonly))
			{
				$link[] = 'textonly=1';
			}
			return '<div class="addItemLink"><a href ="?'.implode('&amp;',$link).'">Post to '.$this->blog->get_value('name').'</a></div>'."\n";
		}
		
		// make sure the blog being requested actually belongs to the blog;
		// we don't want people mucking with the query strings with the effect that
		// something one person said is attributed to someone else
		function further_checks_on_entity( $entity )
		{
			// This  should return true if the entity looks OK to be shown and false if it does not.
			if(empty($this->items[$entity->id()]))
			{
				if($entity->has_left_relation_with_entity($this->blog, 'news_to_publication'))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		
		/**	
		* Displays the Blog Post Submission Disco form if a user is authorized to post to the blog. 
		* Overloads the Generic3 hook.
		*/	
		function add_item()
		{	
			$groups = $this->get_post_groups();
			$ph = new group_helper();
			$ph->set_group_by_entity(current($groups));
			if($ph->requires_login())
			{
				$this->user_netID = $this->get_authentication();
				if(!empty($this->user_netID))
				{
					if($ph->has_authorization($this->user_netID))
					{
						$this->build_post_form($this->user_netID);
					}
					else
					{
						echo 'You are not authorized to post on this publication.'."\n";
					}
				}
				else
					echo 'Please <a href="'.REASON_LOGIN_URL.'"> login </a> to post.'."\n";
			}
			else
			{
				$this->build_post_form('');
			}		
		}
		
		/**	
		* Helper function to add_item() - returns the post groups of this blog.
		* @return array of groups associated with this blog.
		*/	
		function get_post_groups()
		{
			$es = new entity_selector( $this->site_id );
			$es->description = 'Getting groups for this blog';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $this->blog->id(), relationship_id_of('blog_to_authorized_posting_group') );
			return $es->run_one();
		}
	
		/**	
		* Helper function to add_item() - Returns the current user's netID, or false if the user is not logged in.
		* @return string user's netID
		*/	
		function get_authentication()
		{
			if(empty($this->user_netID))
			{
				if(!empty($_SERVER['REMOTE_USER']))
				{
					$this->user_netID = $_SERVER['REMOTE_USER'];
					return $this->user_netID;
				}
				else
				{
					return $this->get_authentication_from_session();
				}
			}
			else
			{
				return $this->user_netID;
			}
		}
		function get_authentication_from_session()
		{
			$this->session =& get_reason_session();
			if($this->session->exists())
			{
				if(!on_secure_page())
				{
					$url = get_current_url( 'https' );
					header('Location: '.$url);
					exit();
				}
				if( !$this->session->has_started() )
				{
					$this->session->start();
				}
				$this->user_netID = $this->session->get( 'username' );
				return $this->user_netID;
			}
			else
			{
				return false;
			}
		}
		
		/**	
		* Helper function to add_item() - initializes & runs a BlogPostSubmissionForm object 
		* @param string user's netID
		*/	
		function build_post_form($net_id)
		{
			$form = new BlogPostSubmissionForm($this->site_id, $this->blog, $net_id);
			$form->run();
		}
		
		/**	
		* Extends the News2Module get_cleanup_rules() function; adds any query string fragments from the blog module to the cleanup_rules.
		* @return array cleanup_rules
		*/
		function get_cleanup_rules()
		{
			$this->cleanup_rules = parent::get_cleanup_rules();
			foreach($this->additional_query_string_frags  as $fragment)
			{
				$this->cleanup_rules[$fragment . '_id'] = array('function' => 'turn_into_int');
			}
			return $this->cleanup_rules;
		}
		
		/**	
		* Show a brief summary of a blog post
		* Overloads the Generic3 show_list_item function. 
		* @param entity blog post
		*/	
		function show_list_item( $item )
		{
			echo '<li>'."\n";
			$this->show_list_item_pre( $item );
			echo '<h4>';
			echo '<a href="' . $this->construct_link($item) . '">';
			$this->show_list_item_name( $item );
			echo '</a>';
			echo '</h4>'."\n";
			
			if($this->use_dates_in_list && $item->get_value( 'datetime' ) )
				echo '<div class="smallText date">'.prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->date_format ).'</div>'."\n";
	
			$this->show_list_item_desc( $item );
			
			echo '<ul class="links">'."\n";
			if(empty($this->request[ $this->query_string_frag.'_id' ]) || $this->request[ $this->query_string_frag.'_id' ] != $item->id() )
			{
				echo '<li class="more">';
				echo '<a href="' . $this->construct_link($item) . '">';
				echo 'Read more of "';
				$this->show_list_item_name( $item );
				echo '"';
				echo '</a>';
				echo '</li>'."\n";
			}
			echo '<li class="permalink">';
			echo '<a href="' . $this->construct_permalink($item) . '">';
			echo 'Permalink';
			echo '</a>';
			echo '</li>'."\n";
			$comment_count = $this->count_comments($item);
			echo '<li class="comments">';
			if($comment_count > 0)
				$view_comments_text = 'View comments ('.$comment_count.')';
			else
				$view_comments_text = 'No comments yet';
			echo'<a href="'.$this->construct_link($item).'#comments">'.$view_comments_text.'</a>';
			echo '</li>'."\n";
			echo '</ul>'."\n";
	
			echo '</li>'."\n";
		}
		
		/**	
		* Returns the number of comments associated with a blog post.
		* Helper function to show_list_item
		* @param entity blog post
		* @return int number of comments associated with blog post.
		*/	
		function count_comments($item)
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Counting comments for this news item';
			$es->add_type( id_of('comment_type') );
			$es->add_relation('show_hide.show_hide = "show"');
			$es->add_right_relationship( $item->id(), relationship_id_of('news_to_comment') );
			return $es->get_one_count();
		}
		function construct_permalink($item)
		{
			$link_frags = array();
			$link_frags[ $this->query_string_frag.'_id' ] = $item->id();
			$query_frags = array();
			foreach($link_frags as $key=>$value)
			{
				$query_frags[] = $key.'='.$value;
			}
			$link = '?'.implode('&amp;',$query_frags);
			return $link;
		}
		function get_feed_url()
		{
			if(!empty($this->blog))
			{
				if(empty($this->feed_url))
				{
					$blog_type = new entity(id_of('publication_type'));
					if($blog_type->get_value('feed_url_string'))
					{
						$this->feed_url = $this->parent->site_info->get_value('base_url').MINISITE_FEED_DIRECTORY_NAME.'/'.$blog_type->get_value('feed_url_string').'/'.$this->blog->get_value('blog_feed_string');
					}
				}
				if(!empty($this->feed_url))
				{
					return $this->feed_url;
				}
			}
			return false;
		}
		function get_login_logout_link()
		{
			$sess_auth = $this->get_authentication_from_session();
			$auth = $this->get_authentication();
			$ret = '<div class="loginlogout">';
			if(!empty($sess_auth))
			{
				$ret .= '<em>Logged in:</em> <strong class="username">'.$sess_auth.'</strong> <a href="'.REASON_LOGIN_URL.'?logout=true" class="logout">Log Out</a>';
			}
			elseif(!empty($auth))
			{
				$ret .= 'Logged in as '.$auth;
			}
			else
			{
				$ret .= '<a href="'.REASON_LOGIN_URL.'" class="login">Log In</a>';
			}
			$ret .= '</div>';
			return $ret;
		}
		
		function get_comment_moderation_state()
		{
			if($this->blog->get_value('hold_comments_for_review') == 'yes')
			{
				return true;
			}
			else
				return false;
		}
		
		function get_comment_group()
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Getting groups for this blog';
			$es->add_type( id_of('group_type') );
			$es->add_right_relationship( $this->blog->id(), relationship_id_of('blog_to_authorized_commenting_group') );
			$es->set_num(1);
			$groups = $es->run_one();
			if(!empty($groups))
			{
				return current($groups);
			}
			else
			{
				trigger_error('No commenting group assigned to blog id '.$this->blog->id());
				return false;
			}
		}
		function alter_relationship_checker_es($es)
		{
			$es->add_left_relationship( $this->blog->id(), relationship_id_of('news_to_blog') );
			return $es;
		}
	}
		
?>
