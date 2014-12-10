<?php

reason_include_once('minisite_templates/modules/publication/item_markup_generators/responsive.php');

class MagazineItemMarkupGenerator extends ResponsiveItemMarkupGenerator
{

/* Luther changes include...
 * 1. Modified order of markup items in run()
 * 2. Custom handling of Comment links
 * 3. Custom "back to publication" links
 * 4. Custom thumbnail sizes
 */

	function get_variables_needed()
	{
		$this->variables_needed[] = 'filter_interface_markup';
		$this->variables_needed[] = 'search_interface_markup';
		return parent::get_variables_needed();
	}

	function run()
	{

		$show_related_section = ($this->should_show_related_events_section() || $this->should_show_images_section() || $this->should_show_assets_section() || $this->should_show_categories_section());
		
		$this->markup_string = '';
		$this->markup_string .= '<div class="fullPost';
		$this->markup_string .= $show_related_section ? ' hasRelated' : ' noRelated';
		$this->markup_string .= '">';
		$this->markup_string .= '<div class="primaryContent firstChunk">'."\n";

		
		if($this->should_show_comment_added_section())
		{
			$this->markup_string .= '<div class="commentAdded">'.$this->get_comment_added_section().'</div>'."\n";
		}
		if($this->should_show_inline_editing_link())
		{
			$this->markup_string .= $this->get_open_inline_editing_section();
		} 
		$this->markup_string .= $this->get_title_section();
		$this->markup_string .= '<div class="postMeta">'."\n";
		if($this->should_show_author_section())
		{
			$this->markup_string .= '<div class="author">'.$this->get_author_section().'</div>'."\n";
		}
		if($this->should_show_date_section())
		{
			$this->markup_string .= '<time class="date">'.$this->get_date_section().'</time>'."\n";
		}
		$this->markup_string .= '</div>'."\n";
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
		$this->markup_string .= '</div>'."\n"; //close first chunk
		if($show_related_section)
		{
			$this->markup_string .= '<div class="relatedItems">'."\n";
			if($this->should_show_images_section())
			{
				$this->markup_string .= '<div class="images">'.$this->get_images_section().'</div>'."\n";
			}
			if($this->should_show_related_events_section())
			{
				$this->markup_string .= '<div class="relatedEvents">'.$this->get_related_events_section().'</div>'."\n";
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
		$this->markup_string .= '<div class="primaryContent secondChunk">'."\n";
		// Not quite ready to add this to the default markup generator
		// Main question: should this go above or below the comments?
		if($this->should_show_next_prev_section())
		{
			$this->markup_string .= '<div class="nextPrev">'.$this->get_next_prev_section().'</div>'."\n";
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
		$this->markup_string .= '</div>'."\n"; //close second chunk
		$this->markup_string .= '</div>'."\n"; //close fullPost div
		$this->markup_string .= $this->get_search_and_filter_interface_markup();
	}

	function get_social_sharing_section()
	{
		//$ret = '<p><strong>Share the love:</strong>';
		foreach($this->passed_vars['item_social_sharing'] as $social_sharing)
		{
			$ret .= '<a href="'.$social_sharing['href'].'">';
			$ret .= '<img src="'. $social_sharing['icon'] . '" alt="'. $social_sharing['text'] . '" />';
			$ret .= '</a>';
		}
	//	$ret .= '</p>';
		return $ret;
	}

	// function get_comment_link_markup()
	// {
	// 	$item = $this->passed_vars['item'];
	// 	$comment_count = isset($this->passed_vars['item_comment_count']) ? $this->passed_vars['item_comment_count'] : 0;
	// 	$link_to_item = $this->passed_vars['link_to_full_item'];

	// 	// Here we change the way comment counts are rendered.
	// 	if($comment_count >= 1)
	// 	{
	// 		$markup_string = '<p class="comments">';
	// 		if($comment_count == 1)
	// 		{
	// 			$view_comments_text = ''.$comment_count.' Comment';
	// 		}
	// 		else
	// 		{
	// 			$view_comments_text = ''.$comment_count.' Comments';
	// 		}
	// 		$link_to_item = $this->passed_vars['link_to_full_item'];
	// 		$markup_string .= '<a href="'.$link_to_item.'#comments">'.$view_comments_text.'</a>';
	// 		$markup_string .= '</p>'."\n";
	// 	}
	// 	elseif (isset($this->passed_vars['commenting_status']))
	// 	{
	// 		switch($this->passed_vars['commenting_status'])
	// 		{
	// 			case 'login_required':
	// 				$markup_string = '<p class="comments noComments">';
	// 				$markup_string .= '<a href="'.REASON_LOGIN_URL.'?dest_page='.urlencode(carl_make_link( array(), '', '', false, false).htmlspecialchars_decode($link_to_item).'#addComment').'">Leave a comment (login required)</a>';
	// 				$markup_string .= '</p>'."\n";
	// 				break;
	// 			case 'open_comments':
	// 			case 'user_has_permission':
	// 				$markup_string = '<p class="comments noComments">';
	// 				$markup_string .= '<a href="'.$link_to_item.'#addComment">Leave a comment</a>';
	// 				$markup_string .= '</p>'."\n";
	// 				break;
	// 			default:
	// 				$markup_string = '';
	// 		}
	// 	}
	// 	else $markup_string = '';
	// 	return $markup_string;
	// }

	// Here, we get rid of <h4>Images</h4>, <ul> and enlarge thumbanil size.
	function get_images_section()
	{
		// foreach($this->passed_vars['item_images'] as $image)
		// {
		// 	$str .= '<div class="imageChunk">';
		// 	$rsi = new reasonSizedImage();
		// 	$rsi->set_id($image->id());
		// 	$rsi->set_width(600);
		// 	//$rsi->set_height(400);
		// 	//$rsi->set_crop_style('fill');
		// 	ob_start();
		// 	show_image( $rsi, false, true, true, '');
		// 	$str .= ob_get_contents();
		// 	ob_end_clean();
		// 	$str .= '</div>';
		// }
		// return $str;
	}

	// Here, we get make the whole Next/Previous section linkable
	function get_next_prev_section()
	{
		// $ret = '';
		// if(!empty($this->passed_vars['previous_post']))
		// {
		// 	$ret .= '<div class="prev';
		// 	if(empty($this->passed_vars['next_post']))
		// 		$ret .= ' only';
		// 	$ret .= '">'."\n";
		// 	$ret .= '<a href="'.$this->passed_vars['previous_post']->get_value('link_url').'">'."\n";
		// 	$ret .= '<h4>Previous Post</h4> '."\n";
		// 	$ret .= '<p>'.$this->passed_vars['previous_post']->get_value('release_title').'</p>'."\n";
		// 	$ret .= '</a>'."\n";
		// 	$ret .= '</div>'."\n";
		// }
		// if(!empty($this->passed_vars['next_post']))
		// {
		// 	$ret .= '<div class="next';
		// 	if(empty($this->passed_vars['previous_post']))
		// 		$ret .= ' only';
		// 	$ret .= '">'."\n";
		// 	$ret .= '<a href="'.$this->passed_vars['next_post']->get_value('link_url').'">'."\n";
		// 	$ret .= '<h4>Next Post</h4> '."\n";
		// 	$ret .= '<p>'.$this->passed_vars['next_post']->get_value('release_title').'</p>'."\n";
		// 	$ret .= '</a>'."\n";
		// 	$ret .= '</div>'."\n";
		// }
		// return $ret;
	}

	// Here, we get rid of <div> around link_markup to avoid outputting it into the HTML if there's no content.
	function get_back_links_markup()
	{
		// $markup_string = '';
		// $markup_string .= '<div class = "back">';
		// $markup_string .= $this->get_back_link_markup();
		// $markup_string .= $this->get_back_to_section_link_markup();
		// $markup_string .= '</div>';
		// return $markup_string;
	}
	
	// Here, we change the language of the link_markup sections
	function get_back_link_markup()
	{
		//return '<p>{ Return to <a class="more" href="'.$this->passed_vars['back_link'].'">'.$this->get_main_list_name().'</a> for more posts. }</p>';
	}

	function get_back_to_section_link_markup()
	{
		// $current_section = $this->passed_vars['current_section'];
		// if(!empty($current_section))
		// {
		// 	$section_name = $current_section->get_value('name');
		// 	$section_url = $this->passed_vars['back_to_section_link'];
		// 	$link = '<p>{ Return to <a class="more"<a href="'.$section_url.'">More posts from '.$section_name.' ('.$this->get_main_list_name().')</a> for more posts. }</p>';
		// 	return $link;
		// }
		// else
		// 	return false;
	}

	function get_search_and_filter_interface_markup()
	{
		$markup = '';
		if(!empty($this->passed_vars['search_interface_markup']) || !empty($this->passed_vars['filter_interface_markup']))
		{
			$markup .= '<div class="searchAndFilterInterface">'."\n";
			// if(!empty($this->passed_vars['search_interface_markup']))
			// {
			// 	$markup .= '<div class="searchInterface">'."\n";
			// 	$markup .= $this->passed_vars['search_interface_markup'];
			// 	$markup .= '</div>'."\n";
			// }
			if(!empty($this->passed_vars['filter_interface_markup']))
			{
				$markup .= '<div class="filterInterface">'."\n";
				$markup .= $this->passed_vars['filter_interface_markup'];
				$markup .= '</div>'."\n";
			}
			$markup .= '</div>'."\n";
		}
		return $markup;
	}	
}
?>