<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */

/**
 * Include the parent class
 */

reason_include_once( 'minisite_templates/modules/publication/publication_list_markup_generators/default.php' );
	
/*
*  This extends our custom default publication list markup generator for the Magazine theme.
*/

class MagazinePublicationListMarkupGenerator extends PublicationListMarkupGenerator
{

	function get_variables_needed()
	{
		$this->variables_needed[] = 'filter_interface_markup';
		$this->variables_needed[] = 'search_interface_markup';
		return parent::get_variables_needed();
	}

	function run()
	{

		//$this->markup_string .= $this->get_issue_selector_markup();
		$this->markup_string .= $this->category_filter_title_markup();
		$this->markup_string .= $this->get_all_posts_markup();
		$this->markup_string .= $this->get_search_and_filter_interface_markup();
	}

	// DISPLAY THE CATEGORY PAGE TITLE IF ON A CATEGORY PAGE
	function category_filter_title_markup()
	{
		//if we're listing filtered items ....
		if(!empty($this->passed_vars['current_filters']))
		{
			$this->markup_string .= $this->get_filter_message_markup();
		}
	}

	// ALL POSTS WRAPPER
	function get_all_posts_markup()
	{
		$markup_string .= '<div class="allPosts">'."\n";
		$markup_string .= $this->get_featured_items_markup();
		$markup_string .= $this->get_list_markup();
		$markup_string .= '</div>'."\n";
		return $markup_string;
	}

	// CUSTOM SEARCH AND FILTER INTERFACE
	function get_search_and_filter_interface_markup()
	{
		$markup = '';
		if(!empty($this->passed_vars['search_interface_markup']) || !empty($this->passed_vars['filter_interface_markup']))
		{
			$markup .= '<div class="searchAndFilterInterface">'."\n";
			// WE DON'T NEED SEARCH FOR NOW, BUT IT'S HERE IF WE WANT IT
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

	function get_featured_items_markup()
	{
		$markup_string = '';
		$featured_items = $this->get_featured_items_to_show();

		if(!empty($featured_items))
		{
			$feature_header_string = '';
			
			if(count($featured_items) > 1)
			{
				if (!empty($feature_header_string)) $feature_header_string .= 's';
			}
			
			$markup_string = '<div id="featuredItems">'."\n";
			if (!empty($feature_header_string)) $markup_string .= '<h3> '.$feature_header_string.' </h3>'."\n";
			
			$markup_string .= '<div class="posts">'."\n";
			foreach($this->passed_vars['featured_item_markup_strings'] as $list_item_string)
			{
				$markup_string .= '<article class="post">'.$list_item_string.'</article>'."\n";
			}
			$markup_string .= '</div>'."\n";
			$markup_string .= '</div>'."\n";
		}
		
		return $markup_string;
	}

	function get_list_markup()
	{
		$markup_string = '';

		if(!empty($this->passed_vars['list_item_markup_strings']))
		{
			$do_section_headings = false;
			if(!empty($this->passed_vars['sections']) && $this->passed_vars['group_by_section'])
			{
				$do_section_headings = true;
				$markup_string .= '<div class="sections">'."\n";
				$markup_string .= '<div class="grid-sizer"></div>'."\n"; // This is necessary for Isotope.js
			}
			
			$ordered_sections = $this->get_section_ids_in_order();		
			foreach($ordered_sections as $section_id)
			{
				$list = $this->passed_vars['items_by_section'][$section_id];
				
				// Change section name into a css-id-friendly string
				$section_entity = $this->passed_vars['sections'][$section_id];
				$name = $section_entity->get_value('name');
				//Lower case everything
				$name = strtolower($name);
				//Make alphanumeric (removes all other characters)
				$name = preg_replace("/[^a-z0-9_\s-]/", "", $name);
				//Clean up multiple dashes or whitespaces
				$name = preg_replace("/[\s-]+/", " ", $name);
				//Convert whitespaces and underscore to dash
				$name = preg_replace("/[\s_]/", "-", $name);

				if($do_section_headings && array_key_exists($section_id, $this->passed_vars['sections']))
				{	
					$markup_string .= '<div class="section" id="'.$name.'">'."\n";
					//$markup_string .= $this->get_section_heading_markup($section_id);
					$markup_string .= $this->get_list_markup_for_these_items(array_keys($list));
					//$markup_string .= $this->get_section_footer_markup($section_id);
					$markup_string .='</div>'."\n";
				}
				elseif(array_key_exists($section_id, $this->passed_vars['items_by_section']))
				{
					$markup_string .= $this->get_list_markup_for_these_items(array_keys($list))."\n";
				}
			}
			if($do_section_headings)
				$markup_string .= '</div>'."\n"; 
		}
		return $markup_string;
	}

	function get_list_markup_for_these_items ($item_ids)
	{

		$markup_string = '';
		if(!empty($this->passed_vars['list_item_markup_strings']) && !empty($item_ids))
		{
			$list_body = '';
			foreach($item_ids as $item_id)
			{
				if(!empty($this->passed_vars['list_item_markup_strings'][$item_id]) && !array_key_exists($item_id, $this->passed_vars['featured_item_markup_strings']))
					$list_body .= '<article class="post"><div class="inner">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</div></article>'."\n";
			}
			if(!empty($list_body))
			{
				$markup_string .= $list_body;
			}
		}
		return $markup_string;
	}

	// ISSUES
	function get_issue_title_markup()
	{
		//if this is an issued publication, show what issue we're looking at
		if(!empty($this->passed_vars['current_issue']))
			$this->markup_string .= $this->get_current_issue_markup($this->passed_vars['current_issue']);
	}

	function get_issue_selector_markup()
	{
		//if there are other issues, display a "jump to other issues" dropdown
		if(!empty($this->passed_vars['issues_by_date']))
			$this->markup_string .= $this->get_issue_links_markup();
	}

	function get_issue_blurb_module_markup()
	{
		if(!empty($this->passed_vars['issue_blurbs']))
			$this->markup_string .= $this->get_issue_blurbs_markup();
	}

	function get_issue_blurbs_markup()
	{
		if(!empty($this->passed_vars['issue_blurbs']))
		{
			$ret = '<div class="issueBlurbs">';
			foreach($this->passed_vars['issue_blurbs'] as $blurb)
			{
				$ret.= '<div class="issueBlurb">'.demote_headings($blurb->get_value('content'), 1).'</div>'."\n";
			}
			$ret .= '</div>'."\n";
			return $ret;
		}
		return '';
	}

	function get_current_issue_markup($issue)
	{
		$markup_string = '';
		$markup_string .= '<div class="issueName"><h3><span>From</span> '.$this->_get_issue_label($issue).'</h3></div>'."\n";
		return $markup_string;
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
				$markup_string .= '<option value="'.$id.'"'.$selected.'>'.strip_tags($this->_get_issue_label($issue)).'</option>'."\n";
			}
			$markup_string .= '</select>'."\n";
			$markup_string .= ($this->passed_vars['text_only'] == 1) ? '<input type="hidden" name="textonly" value="1">' : '';
			$markup_string .= '<input type="submit" value="Go" />'."\n";
			$markup_string .= '</form>'."\n";
			$link = carl_make_link(array('issue_id' => 0));
			//$markup_string .= '<div class="allIssuesLink"><a href="'.$link.'">List all issues</a></div>';
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
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
	

	function get_filter_message_markup()
	{
		$markup_string = '';
		if($msg = $this->_get_filter_message())
		{
			$markup_string .= '<div class="filterMessage">'."\n";
			$markup_string .= '<h3>'.$msg.' <span class="clear"><a href="./">(Back to all posts)</a></span></h3>'."\n";
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;	
	}
	function _get_filter_message()
	{
		$msg = '';
		if(!empty($this->passed_vars['current_filters']))
		{
			$parts = array();
			foreach($this->passed_vars['current_filters'] as $filterkey => $entities)
			{
				foreach($entities as $e)
				{
					$parts[] = $e->get_value('name');
				}
			}
			$glue = ' ';
			$num_parts = count($parts);
			if($num_parts > 1)
			{
				$last_part = 'and '.array_pop($parts);
				$parts[] = $last_part;
				if($num_parts > 2)
				{
					$glue = ', ';
				}
			}
			$msg = 'Category: <strong>'.implode($glue, $parts).'</strong>';
		}
		return $msg;
	}
}
?>
