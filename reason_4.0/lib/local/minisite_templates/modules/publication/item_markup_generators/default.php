<?php
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

/**
*  Generates the full markup for an individual news item or blog post.  
*  Helper class to the publication minisite module.  
*
*  @package reason
*  @subpackage minisite_modules
*  @author Meg Gibbs, Matt Ryan, Nate White
*
*/
class PublicationItemMarkupGenerator extends PublicationMarkupGenerator
{
	var $item;
	var $variables_needed = array('item',
								  'comment_moderation_state',
								  'back_link',
								  'back_to_section_link',
								  'request',
								  'site',
								  'current_issue',
								  'current_section',
								  'publication',
								  'item_events',
								  'item_images',
								  'item_assets',
								  'item_categories',
								  'item_comments',
								  'comment_form_markup',
								  'commenting_status',
								  'permalink',
								  'site_id',
								);


	function additional_init_actions()
	{
		$this->item = $this->passed_vars['item'];
	}

	function run()
	{
		$this->markup_string = '';
		$this->markup_string .= '<div class="fullPost">';
		//$this->markup_string .= '<div class="primaryContent">'."\n";
		if($this->should_show_comment_added_section())
		{
			$this->markup_string .= '<div class="commentAdded">'.$this->get_comment_added_section().'</div>'."\n";
		}
		$this->markup_string .= $this->get_title_section();
		if($this->should_show_date_section())
		{
			$this->markup_string .= '<div class="date">'.$this->get_date_section().'</div>'."\n";
		}
		if($this->should_show_author_section())
		{
			$this->markup_string .= '<div class="author">'.$this->get_author_section().'</div>'."\n";
		}
		if (get_theme($this->passed_vars['site_id'])->get_value('name') != 'luther2010' && $this->should_show_images_section())
		// luther2010 images are rendered in the luther_publication_image_sidebar module
		{
			$this->markup_string .= '<div class="images">'.$this->get_images_section().'</div>'."\n";
		}
		if($this->should_show_content_section())
		{
			$this->markup_string .= '<div class="text">'.$this->get_content_section().'</div>'."\n";
		}
		if($this->should_show_comments_section() || $this->should_show_comment_adder_section())
		{
			$this->markup_string .= $this->get_back_links_markup();
		}
		if($this->should_show_comments_section())
		{
			$this->markup_string .= '<div class="comments">'.$this->get_comments_section().'</div>'."\n";
		}
		if($this->should_show_comment_adder_section())
		{
			$this->markup_string .= '<div class="addCommentForm">'.$this->get_comment_adder_section().'</div>'."\n";
		}
		//$this->markup_string .= '</div>'."\n";
		if($this->should_show_related_events_section() || $this->should_show_images_section() || $this->should_show_assets_section() || $this->should_show_categories_section())
		{
	//		$this->markup_string .= '<div class="relatedItems">'."\n";
			if($this->should_show_related_events_section())
			{
				$this->markup_string .= '<div class="relatedEvents">'.$this->get_related_events_section().'</div>'."\n";
			}
			//if($this->should_show_images_section())
			//{
			//	$this->markup_string .= '<div class="images">'.$this->get_images_section().'</div>'."\n";
			//}
			if($this->should_show_assets_section())
			{
				$this->markup_string .= '<div class="assets">'.$this->get_assets_section().'</div>'."\n";
			}
			if($this->should_show_categories_section())
			{
				$this->markup_string .= '<div class="categories">'.$this->get_categories_section().'</div>'."\n";
			}
			$this->markup_string .= '</div>'."\n";
		}
		//$this->markup_string .= '</div>';
	}
	
	/**
	 * Answers question: should the "Comment Added" section be displayed?
	 *
	 * Basically this should answer true if a comment was just posted (e.g. the request has a value for comment_posted_id)
	 *
	 * @return boolean
	 */
	function should_show_comment_added_section()
	{
		if(!empty($this->passed_vars['request']['comment_posted_id']))
			return true;
		else
			return false;
	}
	
	/**
	 * Build the markup for the "Comment Added" section
	 *
	 * This section exists to inform the user that their comment was a) added, or b) held for review
	 *
	 * @return string 
	 */
	function get_comment_added_section()
	{
		$ret = '';
		if($this->passed_vars['publication']->get_value('hold_comments_for_review') == 'yes')
		{
			$ret .= '<h4>Comments are being held for review on this publication.  Please check back later to see if your comment has been posted.</h4>';
		}
		else
		{
			$ret .= '<h4>Your comment has been added.</h4>';
			$ret .= '<a href="#comment'.$this->passed_vars['request']['comment_posted_id'].'">Jump to your comment</a>';
		}
		return $ret;
	}
	function get_title_section()
	{
		$ret = '';
		$ret .= '<h3 class="postTitle">';
		$ret .= $this->item->get_value( 'release_title' );
		if($this->item->get_value( 'status' ) == 'pending')
		{
			$ret .= ' <span class="pending">[Unpublished]</span>';
		}
		$ret .= '</h3>'."\n";
		return $ret;
	}
	function should_show_date_section()
	{
		if( $this->item->get_value( 'datetime' ) )
			return true;
		else
			return false;
	}
	function get_date_section()
	{
		$date_format_string = $this->passed_vars['publication']->get_value('date_format');
		$date_format = (!empty($date_format_string)) ? $date_format_string : 'F j, Y \a\t g:i a';
		return prettify_mysql_datetime($this->item->get_value( 'datetime' ), $date_format);
	}
	function should_show_author_section()
	{
		if( $this->item->get_value( 'author' ) )
			return true;
		else
			return false;
	}
	function get_author_section()
	{
		return 'By '.$this->item->get_value( 'author' );
	}
	function should_show_content_section()
	{
		if($this->item->get_value('content') || $this->item->get_value('description'))
			return true;
		else
			return false;
	}
	function get_content_section()
	{
		if( carl_empty_html( $this->item->get_value( 'content' ) ) )
		{
			return $this->alter_content( $this->item->get_value('description') );
		}
		else
		{
			return $this->alter_content($this->item->get_value( 'content' ) );
		}
	}
	function alter_content($content)
	{
		if(strpos($content,'<h3') !== false || strpos($content,'<h4') !== false || strpos($content,'<h5') !== false)
		{
			$content = tagSearchReplace($content, 'h5', 'h6');
			$content = tagSearchReplace($content, 'h4', 'h5');
			$content = tagSearchReplace($content, 'h3', 'h4');
		}
		return $content;
	}
	
	// Related events section
	function should_show_related_events_section()
	{
		if(!empty($this->passed_vars['item_events']))
			return true;
		else
			return false;
	}
	function get_related_events_section()
	{
		$str = '<h4>Related Events</h4>';
		$str .= '<ul>';
		foreach($this->passed_vars['item_events'] as $event)
		{
			$str .= '<li>';
			if($event->get_value('event_url'))
				$str .= '<a href="'.$event->get_value('event_url').'">'.$event->get_value('name').'</a>';
			else
				$str .= $event->get_value('name');
			$str .= '</li>';
		}
		$str .= '</ul>';
		return $str;
	}
	
	// Images section
	function should_show_images_section()
	{
		if(!empty($this->passed_vars['item_images']))
			return true;
		else
			return false;
	}
	function get_images_section()
	{
		$markup_string = '';

		foreach($this->passed_vars['item_images'] as $image)
		{
			$id = $image->get_value('id');
			$imgtype = $image->get_value('image_type');
	                $full_image_name = WEB_PHOTOSTOCK.$id.'.'.$imgtype;
	                $thumbnail_image_name = WEB_PHOTOSTOCK.$id.'_tn.'.$imgtype;
			$d = max($image->get_value('width'), $image->get_value('height')) / 125.0;

			ob_start();
			echo '<div class="figure" style="width:' . intval($image->get_value('width')/$d) .'px;">';
			echo '<a href="'. $full_image_name . '" class="highslide" onclick="return hs.expand(this)">';
			echo '<img src="' . $thumbnail_image_name . '" border="0" alt="' . $image->get_value('description') . '" title="Click to enlarge" />';
			echo '</a>';
			// show caption if flag is true
			echo $image->get_value('description');
			echo "</div>\n";
			$markup_string .= ob_get_contents();
			ob_end_clean();
		}
		return $markup_string;

	}
	
	// Assets section
	function should_show_assets_section()
	{
		if(!empty($this->passed_vars['item_assets']))
			return true;
		else
			return false;
	}
	function get_assets_section()
	{
		reason_include_once( 'function_libraries/asset_functions.php' );
		$str = '<h4>Related Documents</h4>';
		$str .= make_assets_list_markup( $this->passed_vars['item_assets'], $this->passed_vars['site'] );
		return $str;
	}
	
	// Categories section
	function should_show_categories_section()
	{
		if(!empty($this->passed_vars['item_categories']))
			return true;
		else
			return false;
	}
	function get_categories_section()
	{
		$ret = '<h4>Posted In</h4>';
		$ret .= '<ul>';
		foreach($this->passed_vars['item_categories'] as $category)
		{
			$ret .= '<li><a href="'.$category->get_value('category_url').'">'.$category->get_value('name').'</a></li>';
		}
		$ret .= '</ul>';
		return $ret;
	}
	
	// Comments section
	function should_show_comments_section()
	{
		if(!empty($this->passed_vars['item_comments']))
			return true;
		else
			return false;
	}
	function get_comments_section()
	{
		$ret = '<a name="comments"></a>';
		$ret .= '<h4>Comments</h4>'."\n";
		
		if(!empty($this->passed_vars['item_comments']))
		{
			$ret .=  '<ul>';
			foreach($this->passed_vars['item_comments'] as $comment)
			{
				$ret .=  '<li><a name="comment'.$comment->id().'"></a>';
				// todo: use publication date format
				$ret .=  '<div class="datetime">'.prettify_mysql_datetime($comment->get_value('datetime'), 'F j Y \a\t g:i a').'</div>';
				$ret .= '<div class="author">'.$comment->get_value('author').'</div>';
				$ret .= '<div class="commentContent">'.$comment->get_value('content').'</div>';
				$ret .= '</li>';
			}
			$ret .= '</ul>';
		}
		else
		{
			$ret .= '<p>There are no comments yet for this post.</p>';
		}
		
		return $ret;
	}
	
	// Comment adder section
	function should_show_comment_adder_section()
	{
		if($this->passed_vars['commenting_status'] == 'publication_comments_off')
			return false;
		else
			return true;
	}
	function get_comment_adder_section()
	{
		$ret = '';
		switch($this->passed_vars['commenting_status'])
		{
			case 'publication_comments_off':
				break;
			case 'item_comments_off':
				$ret .= '<h4>Comments for this post are turned off</h4>';
				break;
			case 'login_required':
				$ret .= '<h4>Add a comment</h4>'."\n";
				$ret .= '<p>Please <a href="'.REASON_LOGIN_URL.'"> login </a> to comment.</p>';
				break;
			case 'user_not_permitted':
				$ret .= '<h4>Commenting Restricted</h4>'."\n";
				$ret .= '<p>You do not currently have the rights to post a comment. If you would like to comment, please contact the site maintainer listed on this page.</p>';
				break;
			case 'open_comments':
			case 'user_has_permission':
				$ret .= '<h4>Add a comment</h4>'."\n";
				$ret .= $this->passed_vars['comment_form_markup'];
				break;
			default:
				trigger_error( 'commenting_status not an expected value ('.$this->passed_vars['commenting_status'].')' );
		}
		return $ret;
	}
	
	function get_back_links_markup()
	{
		$markup_string = '';
		$markup_string .= '<div class = "back">';
		$markup_string .= '<div>'.$this->get_back_link_markup().'</div>';
		$markup_string .= '<div>'.$this->get_back_to_section_link_markup().'</div>';
		$markup_string .= '</div>';
		return $markup_string;
	}
	
	//returns the markup for the link back to the main list of the publication/issue
	function get_back_link_markup()
	{
		return '<a href="'.$this->passed_vars['back_link'].'">Return to '.$this->get_main_list_name().'</a>';
	}
	
	//returns the name of the main list (either the publication or the publication with the issue we were looking at)
	function get_main_list_name()
	{
		$main_list_name = $this->passed_vars['publication']->get_value('name');
		$current_issue = $this->passed_vars['current_issue'];
		if(!empty($current_issue))
			$main_list_name .= ': '.$current_issue->get_value('name');
		return $main_list_name;
	}
	
	//returns the markup for the link back to the list of just one section if that's where we came from
	//if we didn't, returns false
	function get_back_to_section_link_markup()
	{
		$current_section = $this->passed_vars['current_section'];
		if(!empty($current_section))
		{
			$section_name = $current_section->get_value('name');
			$section_url = $this->passed_vars['back_to_section_link'];
			$link = '<a href="'.$section_url.'">Return to '.$section_name.' ('.$this->get_main_list_name().')</a>';
			return $link;
		}
		else
			return false;
	}

}
?>
