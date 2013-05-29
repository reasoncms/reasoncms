<?php
/**
 * Receive and parse uploaded XML - save the data to a cache and reference its ID.
 *
 * When reading the XML, we basically create a large stacked job set.
 *
 * An import ideally follows this model - the graceful handling of failure is a dream at this point.
 *
 * - read some xml or something to produce a job set. It should survive failed attempts gracefully and continue where it left off (not possible with our xml parser).
 * - configure any user options unique to the import (this would presumably alter the job set)	
 * - process the job set. It should survive failed attempts gracefully (and ideally write more and more each attempt).
 *
 * There are some wordpress features that are not supported in Reason - here is a list of items to note:
 *
 * - Reason supports post comments but not page comments - page comments will not be imported.
 * - Reason does not support threaded comments - comment hierarchies will be flattened.
 * - Reason does not support images / e-mail / website links for commenters.
 * - Reason does not support category parents - they are flattened.
 * - Reason does not support "archives" or date search for posts.
 * - Wordpress tags are converted to keywords on individual posts.
 *
 * Some things we do take care of that you might not think about:
 *
 * - Items of type "page" with SEO friendly old URLs are put into Reason's URL history.
 * - A post is placed directly into parent categories if it is not already a part of them.
 *
 * A couple more things ...
 * - As far as I can tell the wordpress extended RSS file does not really give enough info to construct category redirects.
 *   These redirects are left as an exercise to the user. :)
 *
 * @todo add server rewrites for pages that do not have friendly URLs
 * @todo lets have warnings like - page comment not imported
 * @todo figure out how to handle very large files.
 * @todo blog_feed_string for publication should be customizable probably, along with lots of other publication fields.
 * @todo we should support "tags" minimally by adding them as entity keywords on import.
 * @todo relative image links break on import - see post http://www.league.natewhite.com/?story_id=244436
 */
ini_set("display_errors", "on");
ini_set("memory_limit", "512M");
/**
 * Dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/plasmature/upload.php');
reason_include_once('classes/object_cache.php');
reason_include_once('scripts/import/job.php');
reason_include_once('scripts/import/jobs/basic.php');
reason_include_once('scripts/import/drupal/drupal_cleanup_job.php');
include_once(XML_PARSER_INC . 'xmlparser.php');

class SetupForm extends FormStep
{	
	var $site_root = array();
	var $rewrites_needed = false;
	var $display_name = 'Drupal Import - Initial Setup';
	var $elements = array('drupal_xml' => 'ReasonUpload', 'reason_site' => 'text', 'xml_id' => 'protected', 'xml_file_name' => 'protected');
	var $form_enctype = 'multipart/form-data';
	var $only_import_approved_comments = true;
	
	function pre_show_form()
	{
		echo $this->get_value('drupal_xml');
	}
	
	function on_every_time()
	{
		$site_list = $this->get_site_list();
		$existing_file = $this->get_value('drupal_xml');
		if (!empty($existing_file))
		{
			$source_file_name = $this->get_value('xml_file_name');
			$this->set_comments('drupal_xml', form_comment('<p>A file (' . $source_file_name . ') has been uploaded - only upload a file if you want to change the source file</p>'));
		}
		if ($site_list)
		{
			$this->change_element_type('reason_site', 'select_no_sort', array('options' => $site_list));
		}
		else 
		{
			$this->change_element_type('reason_site', 'solidtext');
			$this->set_value('reason_site', 'There are no sites available');
		}
		
		$this->uid = uniqid('', true);
		$this->set_value('xml_id', $this->uid);
		$this->add_element('blog_page_name');
		$this->set_comments('blog_page_name', form_comment('Leave blank if you want the blog to be created on the home page.'));
		$this->add_element('kill_all', 'checkbox', array('display_name' => 'kill all of it'));
	}
	
	/**
	 * @todo we do not want to reparse unless the file changes ...
	 */
	function run_error_checks()
	{
		$xml = $this->get_value('drupal_xml');
		$kill_all = $this->get_value('kill_all');
		$site_id = $this->get_value('reason_site');
		$blog_page_name = $this->get_value('blog_page_name');
		
		// this is too heavy handed we should allow space characters
		if ($blog_page_name && !check_against_regexp($blog_page_name, array('safechars')))
		{
			$this->set_error('blog_page_name', 'You can only use basic alphanumeric characters for the blog page name');
		}
		if (empty($site_id) || !is_numeric($site_id))
		{
			$this->set_error('reason_site', 'You have to choose a valid site in order to continue.');
		}
		if (empty($xml) && empty($kill_all))
		{
			$this->set_error('drupal_xml', 'You need to upload a wordpress extended RSS file to continue.');
		}
		elseif (!empty($xml))
		{
			// lets parse the xml file to create our job set ... should do only if the job set does not exist probably ...
			$file = $this->get_value('drupal_xml');
			$xml = file_get_contents($file);
			$this->xml_parser = new XMLParser($xml);
			$this->xml_parser->Parse();
			if (empty($this->xml_parser->document))
			{
				$this->set_error('wordpress_xml', 'The file you uploaded could not be parsed and may not be an xml file.');
			}
			$file_element = $this->get_value('wordpress_xml');
			
			$this->set_value('xml_file_name', 'work on this to make it accurate!');
		}
	}
	
	/**
	 *
	 * @todo populate descriptions intelligently
	 */
	function process()
	{
		// lets parse the uploaded file and create a job set - we cache the job set so that if the browser times out we can recover.
		$stack = new ImportJobStack();	
		
		// kill All
		$kill_all = $this->get_value('kill_all');
		if ($kill_all == "true")
		{
			$cleanup_job = new WordpressImportCleanup();
			$cleanup_job->site_id = $this->get_value('reason_site');
			$cleanup_job->user_id = $this->controller->reason_user_id;
			$stack->add_job($cleanup_job);
		}
		else
		{
			// ensure the news type is on the site
			$job = new EnsureTypeIsOnSite();
			$job->site_id = $this->get_value('reason_site');
			$job->type_id = id_of('news');
			$stack->add_job($job);
			
			// ensure publication type is on the site
			$job = new EnsureTypeIsOnSite();
			$job->site_id = $this->get_value('reason_site');
			$job->type_id = id_of('publication_type');
			$stack->add_job($job);
			
			// ensure category type is on the site
			$job = new EnsureTypeIsOnSite();
			$job->site_id = $this->get_value('reason_site');
			$job->type_id = id_of('category_type');
			$stack->add_job($job);

			// create the publication entity
			$job = new EntityCreationJob();
			$job->site_id = $this->get_value('reason_site');
			$job->type_id = id_of('publication_type');
			$job->user_id = $this->controller->reason_user_id;
			$job->entity_info = array('name' => $this->xml_parser->document->channel[0]->title[0]->tagData,
				'description' => $this->xml_parser->document->channel[0]->description[0]->tagData,
				'publication_type' => 'blog',
				'blog_feed_string' => 'blog', // this should probably be customized and certainly is a problem if we import multiples on the same site.
				'hold_posts_for_review' => 'no',
				'has_issues' => 'no',
				'has_sections' => 'no');
			$pub_import_guid = md5($this->xml_parser->document->channel[0]->wp_base_blog_url[0]->tagData . "_" . $this->xml_parser->document->channel[0]->pubdate[0]->tagData);		
			$stack->add_job($job, $pub_import_guid);
			
			$nobody_group_job = new EnsureNobodyGroupIsOnSite();
			$nobody_group_job->site_id = $this->get_value('reason_site');
			$stack->add_job($nobody_group_job);
	
			// relate publication to nobody group for front end posting and commenting		
			$post_group_job = new RelateItemsJob();
			$post_group_job->rel_id = relationship_id_of('publication_to_authorized_posting_group');
			$post_group_job->left_import_guid = $pub_import_guid;
			$post_group_job->right_id = id_of('nobody_group');
			$stack->add_job($post_group_job);
			
			$comment_group_job = new RelateItemsJob();
			$comment_group_job->rel_id = relationship_id_of('publication_to_authorized_commenting_group');
			$comment_group_job->left_import_guid = $pub_import_guid;
			$comment_group_job->right_id = id_of('nobody_group');
			$stack->add_job($comment_group_job);		
			
			$make_pub_page_job = new MakePublicationPageJob();
			$make_pub_page_job->site_id = $this->get_value('reason_site');
			
			// if we want a new page - create a publication page.
			if ($new_pub_page = $this->get_value('blog_page_name'))
			{
				$pub_page_name = trim(strip_tags($new_pub_page));
				$pub_page_url_fragment = strtolower(str_replace(array("-"," "),"_",$pub_page_name));
				
				$create_pub_page_job = new EntityCreationJob();
				$create_pub_page_job->type_id = id_of('minisite_page');
				$create_pub_page_job->site_id = $this->get_value('reason_site');
				$create_pub_page_job->user_id = $this->controller->reason_user_id;
				$create_pub_page_job->entity_info = array('name' => $pub_page_name,
													  'link_name' => $pub_page_name,
													  'url_fragment' => $pub_page_url_fragment,
													  'state' => 'Live',
													  'custom_page' => 'publication',
													  'content' => '',
													  'nav_display' => 'Yes');
				$create_pub_page_guid = md5('create_pub_page_job_guid');
				$stack->add_job($create_pub_page_job, $create_pub_page_guid);
				
				// we need to make a parent relationship with the root page_id
				$page_parent_job = new RelateItemsJob();
				$page_parent_job->rel_id = relationship_id_of('minisite_page_parent');
				$page_parent_job->left_import_guid = $create_pub_page_guid;
				$page_parent_job->right_id = $this->get_site_root($this->get_value('reason_site'));
				$page_parent_job_guid = md5($page_parent_job->right_import_guid . '-' . $page_parent_job->left_import_guid);
				$stack->add_job($page_parent_job, $page_parent_job_guid);
				$make_pub_page_job->page_id_guid = $create_pub_page_guid;
			}
			else $make_pub_page_job->page_id = $this->get_site_root($this->get_value('reason_site'));
			$make_pub_page_job->user_id = $this->controller->reason_user_id;
			$make_pub_page_job->pub_import_guid = $pub_import_guid;
			$pub_page_guid = md5('this_is_the_pub_page_guid');
			$stack->add_job($make_pub_page_job, $pub_page_guid);
			
			// CATEGORIES - do we need to worry about multiple nicenames or parents? Hopefully not...
			//
			// * Lets use the category name as the guid to simplify linking of posts to category name
			//
			if (!empty($this->xml_parser->document->channel[0]->wp_category)) foreach ($this->xml_parser->document->channel[0]->wp_category as $k=>$cat)
			{
				$the_category['wp_cat_name'] = $cat->wp_cat_name[0]->tagData;
				$the_category['wp_category_nicename'] = $cat->wp_category_nicename[0]->tagData;
				$the_category['wp_category_parent'] = $cat->wp_category_parent[0]->tagData;
				
				if (!empty($the_category['wp_category_parent']))
				{
					$parent_cat[$the_category['wp_cat_name']] = $the_category['wp_category_parent'];
				}
				
				
				// STUFF FOR OUR IMPORT
				$cat_import_guid = md5($the_category['wp_cat_name']);
				$cat_import_job = new EntityCreationJob();
				$cat_import_job->type_id = id_of('category_type');
				$cat_import_job->site_id = $this->get_value('reason_site');
				$cat_import_job->user_id = $this->controller->reason_user_id;
				$cat_import_job->entity_info = array('name' => $the_category['wp_cat_name'], 'slug' => $the_category['wp_category_nicename']);
				$stack->add_job($cat_import_job, $cat_import_guid);
			}
			
			if (!empty($this->xml_parser->document->channel[0]->item)) foreach ($this->xml_parser->document->channel[0]->item as $k=>$item)
			{
				$the_item = array();
				// STRAIGHT OUT OF WORDPRESS
				$the_item['title'] = $item->title[0]->tagData;
				$the_item['link'] = $item->link[0]->tagData;
				$the_item['pubDate'] = $item->pubdate[0]->tagData;
				$the_item['dc_creator'] = $item->dc_creator[0]->tagData;	
				// categories - how to handle??
				$the_item['guid'] = $item->guid[0]->tagData; // attribute isPermaLink?? - do we care?
				$the_item['guid_is_permalink'] = $item->guid[0]->tagAttrs['ispermalink'];
				$the_item['description'] = tidy(strip_tags($item->description[0]->tagData));	
				$the_item['content_encoded'] = trim(tidy(get_safer_html($this->wpautop($item->content_encoded[0]->tagData))));
				//$the_item['excerpt_encoded'] = trim(tidy($this->wpautop($item->excerpt_encoded[0]->tagData)));
				$the_item['wp_post_id'] = $item->wp_post_id[0]->tagData;
				$the_item['wp_post_date'] = $item->wp_post_date[0]->tagData;
				$the_item['wp_post_date_gmt'] = $item->wp_post_date_gmt[0]->tagData;
				$the_item['wp_comment_status'] = $item->wp_comment_status[0]->tagData;
				$the_item['wp_ping_status'] = $item->wp_ping_status[0]->tagData;
				$the_item['wp_post_name'] = $item->wp_post_name[0]->tagData;
				$the_item['wp_status'] = $item->wp_status[0]->tagData;
				$the_item['wp_post_parent'] = $item->wp_post_parent[0]->tagData;
				$the_item['wp_menu_order'] = $item->wp_menu_order[0]->tagData;
				$the_item['wp_post_type'] = $item->wp_post_type[0]->tagData;
				$the_item['wp_post_password'] = $item->wp_post_password[0]->tagData;
				$the_item['is_sticky'] = $item->wp_post_password[0]->tagData;
				$the_item['guid'] = $item->guid[0]->tagData;
	
				// SOME STUFF WE WANT FOR THE IMPORT
				$import_guid = md5($the_item['wp_post_id']); // we make an import guid from the post id
			
				// IF WE ARE A PAGE
				if (strtolower($the_item['wp_post_type']) == 'page')
				{
					//$the_item['link'] is the old URL
					//$the_item['guid'] is the non friendly URL
					
					// our new url fragment should be the last segment of the old URL - if it is an SEO friendly URL
					$url = parse_url($the_item['link']);
					if (!empty($url['query']))
					{
						$new_url_fragment = strtolower(str_replace(" ","_",$the_item['title']));
					}
					else
					{
						$new_url_fragment = strtolower(str_replace(" ","_",$the_item['wp_post_name']));
						$new_url_fragment = strtolower(str_replace("-","_",$new_url_fragment));
					}
					
					// should we have a description of some type?
					$page_import_job = new EntityCreationJob();
					$page_import_job->type_id = id_of('minisite_page');
					$page_import_job->site_id = $this->get_value('reason_site');
					$page_import_job->user_id = $this->controller->reason_user_id;
					$page_import_job->entity_info = array('name' => $the_item['title'],
														  'link_name' => $the_item['title'],
														  'url_fragment' => $new_url_fragment,
														  'state' => ($the_item['wp_status'] == 'publish') ? 'Live' : 'Pending',
														  'datetime' => $the_item['wp_post_date'],
														  'author' => $the_item['dc_creator'],
														  'sort_order' => $the_item['wp_menu_order'],
														  'custom_page' => 'default',
														  'content' => tidy($the_item['content_encoded']),
														  'nav_display' => 'Yes');
														  
					$stack->add_job($page_import_job, $import_guid);
					
					//page_to_category - these rels we need to do
					if (!empty($item->category))
					{
						foreach ($item->category as $k => $category)
						{
							
							$page_cat = array();
							
							$cat_name = $category->tagData;
							$page_cat[] = $cat_name;
							while (isset($parent_cat[$cat_name]))
							{
								$cat_name = $parent_cat[$cat_name];
								$page_cat[] = $cat_name;
							}
							
							foreach ($page_cat as $cat_name)
							{
								// add a job to relate the post to the category
								$page_to_cat_job = new RelateItemsJob();
								$page_to_cat_job->rel_id = relationship_id_of('page_to_category');
								$page_to_cat_job->left_import_guid = $import_guid; // the news item
								$page_to_cat_job->right_import_guid = md5($cat_name);
								$page_to_cat_guid = md5($page_to_cat_job->rel_id . '_' . $page_to_cat_job->left_import_guid . '_' . $page_to_cat_job->right_import_guid);
								if (!isset($page_to_cat_keys[$page_to_cat_guid]))
								{
									$stack->add_job($page_to_cat_job, $page_to_cat_guid); // we don't give this one a guid ... doesn't need one
								}
								$page_to_cat_keys[$page_to_cat_guid] = true;
							}
						}
					}
					
					//minisite_page_parent - these rels we need to do
					$page_parent_job = new RelateItemsJob();
					$page_parent_job->rel_id = relationship_id_of('minisite_page_parent');
					$page_parent_job->left_import_guid = $import_guid;
					if (empty($the_item['wp_post_parent'])) $page_parent_job->right_id = $this->get_site_root($this->get_value('reason_site'));
					else $page_parent_job->right_import_guid = md5($the_item['wp_post_parent']);
					$page_parent_job_guid = md5($page_parent_job->right_import_guid . '-' . $page_parent_job->left_import_guid);
					$stack->add_job($page_parent_job, $page_parent_job_guid);
					
					$url_history_job = new URLHistoryJob();
					if ($the_item['guid'] && $the_item['guid_is_permalink'] == "true")
					{
						$url_history_job->wp_permalink = $the_item['guid'];
					}
					$url_history_job->wp_link = $the_item['link'];
					$url_history_job->rel_guid = $page_parent_job_guid;
					$url_history_job->entity_guid = $import_guid;

					$stack->add_job($url_history_job);
					
					$this->rewrites_needed = true;
				}
				
				elseif (strtolower($the_item['wp_post_type']) == 'post')
				// IF WE ARE A POST
				{
					if (!empty($the_item['excerpt_encoded'])) $reason_description = $the_item['excerpt_encoded'];
					elseif (!empty($the_item['description'])) $reason_description = $the_item['description'];
					else
					{
						$words = explode(' ', $the_item['content_encoded'], 50);
						unset($words[count($words)-1]);
						$reason_description = implode(' ', $words).'…';
						$reason_description = trim(tidy($reason_description));
					}
					
					/**
					 * its not clear to me what the various dates represent but in my sample xml file post_date seems to be the only item consistently populated
					 * and so we are going to use it for the dated.datetime value.
					 */
					// CREATE THE IMPORT JOB AND SAVE IF IT IS NOT ALREADY CACHED
					$post_import_job = new EntityCreationJob();
					$post_import_job->type_id = id_of('news');
					$post_import_job->site_id = $this->get_value('reason_site');
					$post_import_job->user_id = $this->controller->reason_user_id;
					$post_import_job->entity_info = array('name' => $the_item['title'],
										  'release_title' => $the_item['title'],
										  'description' => tidy($reason_description),
										  'content' => tidy($the_item['content_encoded']),
										  'status' => ($the_item['wp_status'] == 'publish') ? 'published' : 'pending',
										  'show_hide' => ($the_item['wp_status'] == 'publish') ? 'show' : 'hide',
										  'datetime' => $the_item['wp_post_date'],
										  'commenting_state' => ($the_item['wp_comment_status'] == 'open') ? 'on' : 'off',
										  'author' => $the_item['dc_creator']);
				
					$stack->add_job($post_import_job, $import_guid);
					
					// now we want to add a job that relates the post to the publication - we have to do this using our import_guids
					$relationship_job = new RelateItemsJob();
					$relationship_job->rel_id = relationship_id_of('news_to_publication');
					$relationship_job->left_import_guid = $import_guid;
					$relationship_job->right_import_guid = $pub_import_guid;
					$stack->add_job($relationship_job); // we don't give this one a guid ... doesn't need one
					
					$news_rewrite_alert_job = new NewsRewriteAlertJob();
					$news_rewrite_alert_job->page_id_guid = $pub_page_guid;
					$news_rewrite_alert_job->story_id_guid = $import_guid;
					$news_rewrite_alert_job->original_url = $the_item['link'];
					$stack->add_job($news_rewrite_alert_job);
	
					// lets do category rels and test the stack mechanism at the same time!
					if (!empty($item->category))
					{
						foreach ($item->category as $k => $category)
						{
							$news_cat = array();
							
							$cat_name = $category->tagData;
							$news_cat[] = $cat_name;
							while (isset($parent_cat[$cat_name]))
							{
								$cat_name = $parent_cat[$cat_name];
								$news_cat[] = $cat_name;
							}
							
							foreach ($news_cat as $cat_name)
							{
								// add a job to relate the post to the category
								$news_to_cat_job = new RelateItemsJob();
								$news_to_cat_job->rel_id = relationship_id_of('news_to_category');
								$news_to_cat_job->left_import_guid = $import_guid; // the news item
								$news_to_cat_job->right_import_guid = md5($cat_name);
								$news_to_cat_guid = md5($news_to_cat_job->rel_id . '_' . $news_to_cat_job->left_import_guid . '_' . $news_to_cat_job->right_import_guid);
								if (!isset($news_to_cat_keys[$news_to_cat_guid]))
								{
									$stack->add_job($news_to_cat_job, $news_to_cat_guid); // we don't give this one a guid ... doesn't need one
								}
								$news_to_cat_keys[$news_to_cat_guid] = true;
							}
						}
					}
					
					if (!empty($item->wp_comment))
					{
						// ensure comment type is on the site
						if (!isset($make_sure_comments_are_on_site))
						{
							$job = new EnsureTypeIsOnSite();
							$job->site_id = $this->get_value('reason_site');
							$job->type_id = id_of('comment_type');
							$stack->add_job($job);
							$make_sure_comments_are_on_site = true;
						}
						
						foreach ($item->wp_comment as $k => $comment)
						{
							$the_comment = array();
							
							// STRAIGHT OUT OF WORDPRESS
							$the_comment['id'] = $comment->wp_comment_id[0]->tagData;
							$the_comment['author'] = strip_tags($comment->wp_comment_author[0]->tagData);
							$the_comment['author_email'] = $comment->wp_comment_author_email[0]->tagData;
							$the_comment['author_url'] = $comment->wp_comment_author_url[0]->tagData;
							$the_comment['author_IP'] = $comment->wp_comment_author_ip[0]->tagData;
							$the_comment['date'] = $comment->wp_comment_date[0]->tagData;
							$the_comment['content'] = trim(tidy($this->wpautop($comment->wp_comment_content[0]->tagData)));
							$the_comment['approved'] = $comment->wp_comment_approved[0]->tagData;
							
							$should_import = (!$this->only_import_approved_comments || ($the_comment['approved'] == '1'));
							
							// WE MAKE THIS ONE IF IT HAS CONTENT
							if (!empty($the_comment['content']) && $should_import)
							{
								$words = explode(' ', strip_tags($the_comment['content']), 10);
								unset($words[count($words)-1]);
								$name = trim(implode(' ', $words)) .'…';
								$comment_import_guid = md5('comment_id_' . $the_comment['id']);
								$comment_import_job = new EntityCreationJob();
								$comment_import_job->type_id = id_of('comment_type');
								$comment_import_job->site_id = $this->get_value('reason_site');
								$comment_import_job->user_id = $this->controller->reason_user_id;
								
								$comment_import_job->entity_info = array('name' => $name,
															  			 'content' => $the_comment['content'],
															  			 'author' => strip_tags($the_comment['author']),
															  			 'show_hide' => ($the_comment['approved'] == '1') ? 'show' : 'hide',
															  			 'datetime' => $the_comment['date'],
															  			 'new' => 0);
															  			 
								$stack->add_job($comment_import_job, $comment_import_guid);
								
								// now we want to add a job that relates the comment to the post - we use import guids
								$comment_relationship_job = new RelateItemsJob();
								$comment_relationship_job->rel_id = relationship_id_of('news_to_comment');
								$comment_relationship_job->left_import_guid = $import_guid;
								$comment_relationship_job->right_import_guid = $comment_import_guid;
								$stack->add_job($comment_relationship_job); // we don't give this one a guid ... doesn't need one
							}
						}
					}
				}
				
				// check for comments
				// <wp:comment>
				// <wp:comment_id>18400</wp:comment_id>
				// <wp:comment_author><![CDATA[kiffisumma]]></wp:comment_author>
				// <wp:comment_author_email>kiffisumma@gmail.com</wp:comment_author_email>
				// <wp:comment_author_url></wp:comment_author_url>
				// <wp:comment_author_IP>97.114.7.98</wp:comment_author_IP>
				// <wp:comment_date>2010-11-04 18:16:29</wp:comment_date>
				// <wp:comment_date_gmt>2010-11-04 23:16:29</wp:comment_date_gmt>
				// <wp:comment_content><![CDATA[I am deeply concerned about the efficacy of this community survey. From a selected group of 400 names: many young people have no land lines anymore, many people do not answer their phone but use their answering machine to monitor their calls, many people will not want to spend the time to answer a 15 minute survey, etc. etc. etc. In addition to those concerns, how thoughtful will the answers be 'off the top of their head', as opposed to sitting down with a paper survey and thinking about the answers? I think a far better method would be to include a survey sheet in with the water bill, and allow people to return it with their bill; Seems that would yield a bigger, broader, more meaningful return.]]></wp:comment_content>
				// <wp:comment_approved>1</wp:comment_approved>
				// <wp:comment_type></wp:comment_type>
				// <wp:comment_parent>0</wp:comment_parent>
				// <wp:comment_user_id>0</wp:comment_user_id>
				// </wp:comment>
		
			}	
			
			// okay these are not implemented ... what are these in reason?
			//$tags = array();
			//if (!empty($xml_parser->document->channel[0]->wp_tag)) foreach ($xml_parser->document->channel[0]->wp_tag as $k=>$tag)
		//	{
		//		$the_tag = array();
		//		$the_tag['wp_tag_slug'] = $tag->wp_tag_slug[0]->tagData;
		//		$the_tag['wp_tag_name'] = $tag->wp_tag_name[0]->tagData;
		//		
		//		// STUFF FOR OUR IMPORT
		//		$the_tag['import_guid'] = md5($the_tag['wp_tag_slug'] . '_' . $the_tag['wp_tag_name']);
		//		
		//		// ADD THIS ITEM TO THE LIST
		//		$tags[$the_tag['import_guid']] = $the_tag;
		//	}
		} // kill all not true
		
		if ($this->rewrites_needed)
		{
			$rewrite_job = new SiteRewritesJob();
			$rewrite_job->site_id = $this->get_value('reason_site');
			$stack->add_job($rewrite_job);
		}
		
		// lets save the stack in a cache with our $uid
		$cache = new ReasonObjectCache($this->uid);
		$cache->set($stack);
	}

	function get_site_root($site_id)
	{
		if (!isset($this->site_root[$site_id]))
		{
				$this->site_root[$site_id] = root_finder($site_id);
		}
		return $this->site_root[$site_id];
	}
	
	/**
	 * @todo consider whether or not to apply some criteria to limit sites.
	 */
	function get_site_list()
	{
		if (!isset($this->_site_list))
		{
			$es = new entity_selector();
			$es->add_type(id_of('site'));
			$es->add_relation('entity.unique_name != "master_admin"');
			$es->add_relation('entity.unique_name != "site_login"');	
			$results = $es->run_one();
			if (!empty($results))
			{
				foreach ($results as $result)
				{
					$this->_site_list[$result->id()] = $result->get_value('name');
				}
			}
			else $this->_site_list = false;
		}
		return $this->_site_list;
	}
	
	/**
	 * Replaces double line-breaks with paragraph elements.
	 *
	 * A group of regex replaces used to identify text formatted with newlines and
	 * replace double line-breaks with HTML paragraph tags. The remaining
	 * line-breaks after conversion become <<br />> tags, unless $br is set to '0'
	 * or 'false'.
	 *
	 * @since 0.71
	 *
	 * @param string $pee The text which has to be formatted.
	 * @param int|bool $br Optional. If set, this will convert all remaining line-breaks after paragraphing. Default true.
	 * @return string Text which has been converted into correct paragraph tags.
	 */
	function wpautop($pee, $br = 1) {
	
		if ( trim($pee) === '' )
			return '';
		$pee = $pee . "\n"; // just to make things a little easier, pad the end
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|input|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		if ( strpos($pee, '<object') !== false ) {
			$pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
			$pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
		}
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
		// make paragraphs, including one at the end
		$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
		$pee = '';
		foreach ( $pees as $tinkle )
			$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
		$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
		$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);
		if ($br) {
			$pee = preg_replace_callback('/<(script|style).*?<\/\\1>/s', create_function('$matches', 'return str_replace("\n", "<WPPreserveNewline />", $matches[0]);'), $pee);
			$pee = preg_replace('|(?<!<br />)\s*\n|', "<br />\n", $pee); // optionally make line breaks
			$pee = str_replace('<WPPreserveNewline />', "\n", $pee);
		}
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		if (strpos($pee, '<pre') !== false)
			$pee = preg_replace_callback('!(<pre[^>]*>)(.*?)</pre>!is', 'clean_pre', $pee );
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
	
		return $pee;
	}
}
?>