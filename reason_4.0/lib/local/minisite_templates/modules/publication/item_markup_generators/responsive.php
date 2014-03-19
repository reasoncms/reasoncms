<?php

reason_include_once('minisite_templates/modules/publication/item_markup_generators/default.php');

class ResponsiveItemMarkupGenerator extends PublicationItemMarkupGenerator
{

function get_comment_link_markup()
	{
		$item = $this->passed_vars['item'];
		$comment_count = isset($this->passed_vars['item_comment_count']) ? $this->passed_vars['item_comment_count'] : 0;
		$link_to_item = $this->passed_vars['link_to_full_item'];

		// Here we change the way comment counts are rendered.
		if($comment_count >= 1)
		{
			$markup_string = '<p class="comments">';
			if($comment_count == 1)
			{
				$view_comments_text = ''.$comment_count.' Comment';
			}
			else
			{
				$view_comments_text = ''.$comment_count.' Comments';
			}
			$link_to_item = $this->passed_vars['link_to_full_item'];
			$markup_string .= '<a href="'.$link_to_item.'#comments">'.$view_comments_text.'</a>';
			$markup_string .= '</p>'."\n";
		}
		elseif (isset($this->passed_vars['commenting_status']))
		{
			switch($this->passed_vars['commenting_status'])
			{
				case 'login_required':
					$markup_string = '<p class="comments noComments">';
					$markup_string .= '<a href="'.REASON_LOGIN_URL.'?dest_page='.urlencode(carl_make_link( array(), '', '', false, false).htmlspecialchars_decode($link_to_item).'#addComment').'">Leave a comment (login required)</a>';
					$markup_string .= '</p>'."\n";
					break;
				case 'open_comments':
				case 'user_has_permission':
					$markup_string = '<p class="comments noComments">';
					$markup_string .= '<a href="'.$link_to_item.'#addComment">Leave a comment</a>';
					$markup_string .= '</p>'."\n";
					break;
				default:
					$markup_string = '';
			}
		}
		else $markup_string = '';
		return $markup_string;
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
		if($this->should_show_date_section())
		{
			$this->markup_string .= '<time class="date">'.$this->get_date_section().'</time>'."\n";
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
		$this->markup_string .= '</div>'."\n"; //close first chunk
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
		$this->markup_string .= '<div class="primaryContent secondChunk">'."\n";
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
		$this->markup_string .= '</div>'."\n"; //close second chunk
		$this->markup_string .= '</div>'."\n"; //close fullPost div
	}
}
?>