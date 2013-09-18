<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
/**
 * Include parent class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );

reason_include_once('classes/media/factory.php');

/**
*  Generates the full markup for an individual news item or blog post.
*
*  Helper class to the publication minisite module.  
*
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
								  'item_media',
								  'item_assets',
								  'item_categories',
								  'item_social_sharing',
								  'item_comments',
								  'comment_form_markup',
								  'commenting_status',
								  'permalink',
								  'comment_has_errors',
								  'inline_editing_info',
								  'next_post',
								  'previous_post',
								);


	function additional_init_actions()
	{
		$this->item = $this->passed_vars['item'];
	}
	
	function add_head_items($head_items)
	{
		parent::add_head_items($head_items);
		if($this->should_show_media_section() && $this->get_media_count() > 1)
		{
			$head_items->add_javascript(JQUERY_URL, true);
			$head_items->add_javascript(REASON_HTTP_BASE_PATH.'modules/publications/media_gallery.js');
		}
	}

	function run()
	{
		$show_related_section = ($this->should_show_related_events_section() || $this->should_show_images_section() || $this->should_show_assets_section() || $this->should_show_categories_section());
		
		$this->markup_string = '';
		$this->markup_string .= '<div class="fullPost';
		$this->markup_string .= $show_related_section ? ' hasRelated' : ' noRelated';
		$this->markup_string .= '">';
		$this->markup_string .= '<div class="primaryContent">'."\n";
		if($this->should_show_comment_added_section())
		{
			$this->markup_string .= '<div class="commentAdded">'.$this->get_comment_added_section().'</div>'."\n";
		}
		if($this->should_show_inline_editing_link())
		{
			$this->markup_string .= $this->get_open_inline_editing_section();
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
		if($this->should_show_social_sharing_section())
		{
			$this->markup_string .= '<div class="social top">'.$this->get_social_sharing_section().'</div>'."\n";
		}
		if($this->should_show_media_section())
		{
			$this->markup_string .= '<div class="media">'.$this->get_media_section().'</div>'."\n";
		}
		if($this->should_show_content_section())
		{
			$this->markup_string .= '<div class="text">'.$this->get_content_section().'</div>'."\n";
		}
		if($this->should_show_social_sharing_section())
		{
			$this->markup_string .= '<div class="social bottom">'.$this->get_social_sharing_section().'</div>'."\n";
		}
		if($this->should_show_inline_editing_link())
		{
			$this->markup_string .= $this->get_close_inline_editing_section();
		}
		if($this->should_show_comments_section() || $this->should_show_comment_adder_section())
		{
			$this->markup_string .= $this->get_back_links_markup();
		}
		// Not quite ready to add this to the default markup generator
		// Main question: should this go above or below the comments?
		if($this->should_show_next_prev_section())
		{
			$this->markup_string .= '<div class="nextPrev">'.$this->get_next_prev_section().'</div>'."\n";
		}
		if($this->should_show_comments_section())
		{
			$this->markup_string .= '<div class="comments">'.$this->get_comments_section().'</div>'."\n";
		}
		if($this->should_show_comment_adder_section())
		{
			$this->markup_string .= '<div class="addCommentForm">'.$this->get_comment_adder_section().'</div>'."\n";
		}
		$this->markup_string .= '</div>'."\n";
		if($show_related_section)
		{
			$this->markup_string .= '<div class="relatedItems">'."\n";
			if($this->should_show_related_events_section())
			{
				$this->markup_string .= '<div class="relatedEvents">'.$this->get_related_events_section().'</div>'."\n";
			}
			if($this->should_show_images_section())
			{
				$this->markup_string .= '<div class="images">'.$this->get_images_section().'</div>'."\n";
			}
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
		$this->markup_string .= '</div>';
	}
	
	/**
	 * Answers question: should the "Comment Added" or form error sections be displayed?
	 *
	 * Basically this should answer true if a comment was just posted (e.g. the request has a value for comment_posted_id)
	 * OR if the form had errors. 
	 *
	 * @return boolean
	 */
	function should_show_comment_added_section()
	{
		$has_errors = (!empty($this->passed_vars['comment_has_errors']) && ($this->passed_vars['comment_has_errors'] == 'yes')) ? true : false;
		if(!empty($this->passed_vars['request']['comment_posted_id']))
			return true;
		elseif ($has_errors)
			return true;
		else
			return false;
	}

	
	/**
	 * Build the markup for the "Comment Info" section.
	 *
	 * This section exists to inform the user that their comment was a) added, or b) held for review.
	 * It also informs the user of any errors with their form.
	 *
	 * Perhaps the type-of-info-to-show checking should be moved into the should_show method?
	 *
	 * @return string 
	 */
	function get_comment_added_section()
	{
		$ret = '';
		$has_errors = (!empty($this->passed_vars['comment_has_errors']) && ($this->passed_vars['comment_has_errors'] == 'yes')) ? true : false;
		if ($has_errors)
		{
			$ret .= '<h4>Your comment submission has errors and could not be saved. Please <a href="#discoErrorNotice">fix the errors</a> and resubmit.</h4>';
		}
		elseif($this->passed_vars['publication']->get_value('hold_comments_for_review') == 'yes')
		{
			$ret .= '<h4>Comments are being held for review on this publication. Please check back later to see if your comment has been posted.</h4>';
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
	function should_show_media_section()
	{
		return (!empty($this->passed_vars['item_media']));
	}
	function get_media_count()
	{
		return count($this->passed_vars['item_media']);
	}
	/**
	 * Get the markup for the media section
	 * @todo support classic media somehow
	 */
	function get_media_section()
	{
		$class = $this->get_media_count() > 1 ? 'mediaGallery' : 'basicMedia';
		$str = '<ul class="'.$class.'">';
		foreach($this->passed_vars['item_media'] as $media)
		{
			$str .= '<li>';
			$str .= '<div class="titleBlock">';
			if($placard_info = $this->get_media_placard_info($media))
				$str .= '<img src="'.$placard_info['url'].'" alt="Placeholder image for '.reason_htmlspecialchars($media->get_value('name')).'" class="placard" width="'.$placard_info['width'].'" height="'.$placard_info['height'].'" style="display:none;" />';
			$str .= '<div class="mediaName">'.$media->get_value('name').'</div>';
			$str .= '</div>';
			//$str .= $media->get_value('integration_library').'<br />';
			$displayer_chrome = MediaWorkFactory::displayer_chrome($media, 'default');
			if ($displayer_chrome)
			{
				$str .= '<div class="mediaDisplay">';
				$displayer_chrome->set_media_work($media);
				
				if($height = $this->get_media_display_height());
					$displayer_chrome->set_media_height($height);
				
				if($width = $this->get_media_display_width());
					$displayer_chrome->set_media_width($width);
				
				//$str .= get_class($displayer_chrome);
	
				$str .= $displayer_chrome->get_html_markup();
				$str .= '</div>';
			}
			$str .= '</li>';
		}
		$str .= '</ul>';
		return $str;
	}
	protected function get_media_display_height()
	{
		return NULL;
	}
	protected function get_media_display_width()
	{
		return 480;
	}
	protected function get_media_placard_info($media)
	{
		if($placards = $media->get_left_relationship('av_to_primary_image'))
		{
			$placard = current($placards);
			$placard_url = reason_get_image_url($placard, 'tn');
			list($width, $height) = getimagesize(reason_get_image_path($placard, 'tn'));
		}	
		else
		{
			$placard_url =  REASON_HTTP_BASE_PATH.'modules/publications/media_placeholder_thumbnail.png';
			$width = 125;
			$height = 70;
		}
		return array(
			'url' => $placard_url,
			'width' => $width,
			'height' => $height,
		);
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
		$str = '<h4>Images</h4>';
		$str .= '<ul>';
		foreach($this->passed_vars['item_images'] as $image)
		{
			$str .= '<li>';
			ob_start();
			$textonly = false;
			if(!empty($this->passed_vars['request']['textonly']))
				$textonly = true;
			show_image( $image, false, true, true, '', $textonly );
			$str .= ob_get_contents();
			ob_end_clean();
			$str .= '</li>';
		}
		$str .= '</ul>';
		return $str;
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

	// Categories section
	function should_show_social_sharing_section()
	{
		if(!empty($this->passed_vars['item_social_sharing']))
			return true;
		else
			return false;
	}
	function get_social_sharing_section()
	{
		$ret = '<p><strong>Share post:</strong>';
		foreach($this->passed_vars['item_social_sharing'] as $social_sharing)
		{
			$ret .= ' <a href="'.$social_sharing['href'].'">';
			$ret .= '<img src="'. $social_sharing['icon'] . '" alt="'. $social_sharing['text'] . '" />';
			$ret .= '</a>';
		}
		$ret .= '</p>';
		return $ret;
	}
	
	// Next/Prev section
	function should_show_next_prev_section()
	{
		if(!empty($this->passed_vars['next_post']) || !empty($this->passed_vars['previous_post']))
			return true;
		else
			return false;
	}
	
	function get_next_prev_section()
	{
		$ret = '';
		if(!empty($this->passed_vars['previous_post']))
		{
			$ret .= '<div class="prev';
			if(empty($this->passed_vars['next_post']))
				$ret .= ' only';
			$ret .= '">'."\n";
			$ret .= '<h4>Previous Post</h4> '."\n";
			$ret .= '<p><a href="'.$this->passed_vars['previous_post']->get_value('link_url').'">'.$this->passed_vars['previous_post']->get_value('release_title').'</a></p>'."\n";
			$ret .= '</div>'."\n";
		}
		if(!empty($this->passed_vars['next_post']))
		{
			$ret .= '<div class="next';
			if(empty($this->passed_vars['previous_post']))
				$ret .= ' only';
			$ret .= '">'."\n";
			$ret .= '<h4>Next Post</h4> '."\n";
			$ret .= '<p><a href="'.$this->passed_vars['next_post']->get_value('link_url').'">'.$this->passed_vars['next_post']->get_value('release_title').'</a></p>'."\n";
			$ret .= '</div>'."\n";
		}
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
				$ret .= '<a name="addComment"></a><h4>Add a comment</h4>'."\n";
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
	
	// returns true if inline editing is enabled, but NOT active; false otherwise
	// This tells the markup generator if it should show the "edit" link and the dotted gray border
	// around the corresponding content
	function should_show_inline_editing_link()
	{
		$available = (isset($this->passed_vars['inline_editing_info']['available'])) ? $this->passed_vars['inline_editing_info']['available'] : false;
		$active = (isset($this->passed_vars['inline_editing_info']['active'])) ? $this->passed_vars['inline_editing_info']['active'] : false;
		if ($available && !$active) return true;
		else return false;
	}
	
	// returns the markup for the opening div's of the inline-editing box and link
	function get_open_inline_editing_section()
	{
		return '<div class="editable"><div class="editRegion">';
	}
	
	// return the markup for the "edit" link and the closing div's for the inline-editing box
	// and link
	function get_close_inline_editing_section()
	{
		$markup_string = '';
		$url = $this->passed_vars['inline_editing_info']['url'];
		$link = '<p><a href="'.$url.'" class="editThis">Edit Post</a></p>';	
		$markup_string .= $link .'</div>';
		$markup_string .= '</div>';
		return $markup_string;
	}

}
?>
