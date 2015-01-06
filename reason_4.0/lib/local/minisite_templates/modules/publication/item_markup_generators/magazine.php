<?php

reason_include_once('minisite_templates/modules/publication/item_markup_generators/responsive.php');

class MagazineItemMarkupGenerator extends ResponsiveItemMarkupGenerator
{

	function get_variables_needed()
	{
		$this->variables_needed[] = 'filter_interface_markup';
		$this->variables_needed[] = 'search_interface_markup';
		$this->variables_needed[] = 'current_issue';
		$this->variables_needed[] = 'issues_by_date';
		$this->variables_needed[] = 'links_to_issues';
		return parent::get_variables_needed();
	}

	function run()
	{

		$show_related_section = ($this->should_show_related_events_section() || $this->should_show_images_section() || $this->should_show_assets_section() || $this->should_show_categories_section());
		
		$this->markup_string = '';


		// Put First image at top of article
		// @todo: Make it so only one image can display.
		if($this->should_show_images_section())
		{
			$this->markup_string .= '<div class="topImage">'.$this->get_images_section().'</div>'."\n";
		}

		//$this->markup_string .= $this->get_issue_selector_markup();

		$this->markup_string .= '<div class="fullPost';
		$this->markup_string .= $show_related_section ? ' hasRelated' : ' noRelated';
		$this->markup_string .= '">';
		
		// FIRST CONTENT BLOCK
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
			
			// if($this->should_show_date_section())
			// {
			// 	$this->markup_string .= '<time class="date">'.$this->get_date_section().'</time>'."\n";
			// }
			
			$this->markup_string .= '</div>'."\n";

			if($this->should_show_social_sharing_section())
			{
				$this->markup_string .= '<div class="social top">'.$this->get_social_sharing_section().'</div>'."\n";
			}

			// if($this->should_show_media_section())
			// {
			// 	$this->markup_string .= '<div class="media">'.$this->get_media_section().'</div>'."\n";
			// }

			if($this->should_show_content_section())
			{
				$this->markup_string .= '<div class="text">'.$this->get_content_section().'</div>'."\n";
			}
			
			if($this->should_show_inline_editing_link())
			{
				$this->markup_string .= $this->get_close_inline_editing_section();
			}
		
		$this->markup_string .= '</div>'."\n"; //close first chunk
		
		// RELATED CONTENT BLOCK
		if($show_related_section)
		{
			$this->markup_string .= '<div class="relatedItems">'."\n";
			if($this->should_show_related_events_section())
			{
				$this->markup_string .= '<div class="relatedEvents">'.$this->get_related_events_section().'</div>'."\n";
			}
			if($this->should_show_assets_section())
			{
				$this->markup_string .= '<div class="assets">'.$this->get_assets_section().'</div>'."\n";
			}

			// $this->markup_string .= '<div class="fromIssue">From <a href="#">Fall 2014</a></div>'."\n";


			if($this->should_show_categories_section())
			{
				$this->markup_string .= '<div class="categories">'.$this->get_categories_section().'</div>'."\n";
			}

			$this->markup_string .= $this->get_back_link_markup();

			if($this->should_show_social_sharing_section())
			{
				$this->markup_string .= '<div class="social bottom">'.$this->get_social_sharing_section().'</div>'."\n";
			}

			$this->markup_string .= '</div>'."\n";
		}

		// FINAL CONTENT BLOCK
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

		$this->markup_string .= $this->get_search_and_filter_interface_markup();

		$this->markup_string .= '</div>'."\n"; //close fullPost div

	}

	function get_issue_selector_markup()
	{
		//if there are other issues, display a "jump to other issues" dropdown
		if(!empty($this->passed_vars['issues_by_date']))
		{
			$this->markup_string .= $this->get_issue_links_markup();
		}
	}

	function get_issue_links_markup()
	{
		$issues_by_date = $this->passed_vars['issues_by_date'];
		//krsort($issues_by_date);
		$links_to_issues = $this->passed_vars['links_to_issues'];

		$markup_string = '';
		
		$cur_issue_id = '';
		if(!empty($this->passed_vars['current_issue']))
		{
			$cur_issue_id = $this->passed_vars['current_issue']->id();
		}
		
		if(count($issues_by_date) > 1 )
		{
			$markup_string .= '<div class="issueMenu">'."\n";
			$markup_string .= '<form action="'.htmlspecialchars(get_current_url(),ENT_QUOTES,'UTF-8').'">'."\n";
			$markup_string .= '<label for="pubIssueMenuElement" class="issueLabel">Issue:</label>'."\n";

			$markup_string .= '<script type="text/javascript">'."\n";
			$markup_string .= '/* <![CDATA[ */'."\n";
			$markup_string .= '
			if (jQuery)
			{
				$(document).ready(function(){
					$(".issueMenu input[type=\'submit\']").hide();
					$(".issueMenu select[name=\'issue_id\']").change(function(){
						$(this).parent("form").submit();
					});
				});
			}';
			$markup_string .= '/* ]]> */'."\n";
			$markup_string .= '</script>';
			
			$markup_string .= '<select name="issue_id" id="pubIssueMenuElement">'."\n";
			if (!$cur_issue_id)
			{
				$markup_string .= '<option value="'.$cur_issue_id.'" selected="selected">Select Issue</option>'."\n";
			}

			foreach($issues_by_date as $id => $issue)
			{
				$selected = ($cur_issue_id == $id) ? ' selected="selected"' : '';
				$markup_string .= '<option value="' . $id . '"'.$selected.'>'. strip_tags($this->_get_issue_label($issue)).'</option>'."\n";
			}

			$markup_string .= '</select>'."\n";
			$markup_string .= ($this->passed_vars['text_only'] == 1) ? '<input type="hidden" name="textonly" value="1">' : '';
			$markup_string .= '<input type="submit" value="Go" />'."\n";
			$markup_string .= '</form>'."\n";
			$link = carl_make_link(array('issue_id' => 0));
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}

	function get_social_sharing_section()
	{
		$ret = '<ul class="socialIcons">';
		
		foreach($this->passed_vars['item_social_sharing'] as $social_sharing)
		{

			// Change Social Media name into a css-class-friendly string
			$name = $social_sharing['text'];
			//Lower case everything
			$name = strtolower($name);
			//Make alphanumeric (removes all other characters)
			$name = preg_replace("/[^a-z0-9_\s-]/", "", $name);
			//Clean up multiple dashes or whitespaces
			$name = preg_replace("/[\s-]+/", " ", $name);
			//Convert whitespaces and underscore to dash
			$name = preg_replace("/[\s_]/", "-", $name);

			$ret .= '<li class="' . $name . '">';
			$ret .= '<a href="'.$social_sharing['href'].'">';
			$ret .= '<span>' . $social_sharing['text'] . '</span>'; 
			$ret .= '</a>';
			$ret .= '</li>';
		}
		
		$ret .= '</ul>';
		return $ret;
	}

	// Here, we get rid of <h4>Images</h4>, <ul> and enlarge thumbanil size.
	function get_images_section()
	{
		foreach($this->passed_vars['item_images'] as $image)
		{
			$str = '<div class="imageChunk">';
			$rsi = new reasonSizedImage();
			$rsi->set_id($image->id());
			$rsi->set_width(1600);
			$rsi->set_height(550);
			//$rsi->set_crop_style('fill');
			ob_start();
			show_image( $rsi, false, false, false, '');
			$str .= ob_get_contents();
			ob_end_clean();
			$str .= '</div>';
		}
		return $str;
	}

	// Here, we remove the whole Next/Previous section nav
	function get_next_prev_section()
	{
	}

	// Here, we get rid of back link markup
	function get_back_links_markup()
	{
	}

	function get_back_to_section_link_markup()
	{
	}
	
	// Here, we change the language of the link_markup sections
	function get_back_link_markup()
	{
		return '<p class="fromIssue">From <a href="'.$this->passed_vars['back_link'].'">'.$this->get_main_list_name().'</a>.</p>';
	}

	function get_author_section()
	{
		return 'By <span class="name">'.$this->item->get_value( 'author' ).'</span>';
	}

	function _get_issue_label($issue)
	{
		$name = $issue->get_value('name');
		if(!empty($this->passed_vars['links_to_issues'][$issue->id()]) )
		{
			$name = '<a href="'.$this->passed_vars['links_to_issues'][$issue->id()].'">'.$name.'</a>';
		}
		if($issue->get_value('show_hide') == 'hide')
				$name = '[Unpublished] '.$name;
		return $name;
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