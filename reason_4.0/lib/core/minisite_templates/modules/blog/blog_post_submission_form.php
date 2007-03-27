<?
	include_once( DISCO_INC.'disco.php');
	include_once( CARL_UTIL_INC . 'tidy/tidy.php');
	////////////////////////
	//POST SUBMISSION FORM
	///////////////////////
	class BlogPostSubmissionForm extends Disco
	{
		var $elements = array(
			'dont_post' => array(
									'type'=>'comment', 
									'text'=>'<a href ="?">Return to blog without posting</a>',
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
			//'new_categories',
		);
		var $required = array(
			'title',
			'author',
			'post_content',
		);
		var $actions = array('Submit'=>'Post Item');
		var $site_info;
		var $blog;
		var $user_netID;
		var $categories;
		var $new_post_id;
		
		function BlogPostSubmissionForm($site_id, $blog, $user_netID)
		{
			$this->blog = $blog;
			$this->site_info = get_entity_by_id ($site_id);
			$this->user_netID = $user_netID;
		}
		
		function on_every_time()
		{
			if(!empty($this->user_netID))
			{
				$this->set_value('author', $this->user_netID);
			}
			$this->do_editable_alterations();
			$this->do_categories();
		}
		function do_editable_alterations()
		{
			$reason_user_id = get_user_id( $this->user_netID );
			if(empty($reason_user_id)) $reason_user_id = 0;
			$editor_name = html_editor_name($this->site_info['id']);
			$editor_params = html_editor_params($this->site_info['id'], $reason_user_id);
			$this->change_element_type( 'post_content',  $editor_name, $editor_params );
			$this->change_element_type( 'description',  $editor_name, $editor_params );
		}
		function run_error_checks()
		{
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
			$es = new entity_selector($this->site_info['id']);
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
			$description = trim(get_safer_html(tidy($this->get_value('description'))));
			$content = trim(get_safer_html(tidy($this->get_value('post_content'))));
			$title = trim(get_safer_html(strip_tags($this->get_value('title'))));
			$author = trim(get_safer_html(strip_tags($this->get_value('author'))));

			if(empty($description))
			{
				$words = explode(' ', $content, 31);
				unset($words[count($words)-1]);
				$description = implode(' ', $words).'…';
				$description = trim(tidy($description)); // we're tidying it twice so that if we chop off a closing tag tidy will stitch it back up again
			}
			
			if(!empty($this->user_netID))
			{
				$user_id = make_sure_username_is_user($this->user_netID, $this->site_info['id']);
			}
			else
			{
				$user_id = $this->site_info['id'];
			}
					
			//Should the title be the name or release title or what?
			$flat_values = array (
				'status' => 'Published',
				'release_title' => $title,
				'author' => $author,
				'content' => $content,
				'description' => $description,
				'datetime' => date('Y-m-d H:i:s', time()),
				'keywords' => implode(', ', array($title, date('Y'), date('F'))),
				'show_hide' => 'show',
			);
					
			$tables = get_entity_tables_by_type(id_of('news'));
				
				
			#Who should the author id be of?
			$this->new_post_id = create_entity( 
				$this->site_info['id'], 
				id_of('news'), 
				$user_id, 
				$flat_values['release_title'], 
				values_to_tables($tables, $flat_values, $ignore = array()), 
				$testmode = false
			);
			
			create_relationship(
				$this->new_post_id,
				$this->blog->id(),
				relationship_id_of('news_to_blog')
			);
			
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
			/* echo 'Your post has been submitted successfully.  ID = '.$new_post_id;
			echo '<div> <a href ="?add_item="> Return to list </a> </div>';
			echo '<div> <a href ="?add_item=true"> Add another post </a> </div>'; */
		}
		function where_to() // {{{
		{
			return '?';
		} // }}}
	}
?>
