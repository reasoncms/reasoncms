<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */

/**
 * Include the parent class
 */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );
	
/**
*  Generates the markup to display a list of news items or blog posts.  
*  Helper class to the publication minisite module.  
*
*  @author Meg Gibbs
*
*  @todo Make this more easily extensible.  What that may mean will become more clear when we actually start writing extensions,
*        but this should certainly involve making most of the text variables instead of hard-coded text so that terminology can be changed
*        without overloading functions.
*  @todo Better HTML -- ids, classes for divs.
*
*  Luther edits include updating <ul class="posts"><li class="post"> to <div class="posts"><article class="post">
*/
class PublicationListMarkupGenerator extends PublicationMarkupGenerator
{
	//yep, we're overloading a private variable from the abstract class  
	//Any children of this should extemd get_variables_needed instead of overloading this array.  
	var $variables_needed = array(  'list_item_markup_strings',		//array item_id => markup for list item
									'featured_item_markup_strings',	//array item_id => markup for featured item							
									'sections', 					//array section_id => section
									'current_section',				
									'items_by_section', 
									'links_to_sections',
									'no_section_key', 
									'current_issue',
									'issues_by_date',
									'links_to_issues',
									'view_all_items_in_section_link',
									'group_by_section',
 								    'back_link',
									'publication',
									'date_format',
									'search_string',
									'text_only',
									'issue_blurbs',
									'current_filters',
									);

	function PublicationListMarkupGenerator ()
	{
	}

	function run()
	{	
		$this->markup_string .= $this->get_pre_list_markup();
		$this->markup_string .= $this->get_list_markup();
		$this->markup_string .= $this->get_post_list_markup();
	}

	function get_pre_list_markup()
	{
		//if this is an issued publication, show what issue we're looking at
		if(!empty($this->passed_vars['current_issue']))
			$this->markup_string .= $this->get_current_issue_markup($this->passed_vars['current_issue']);
				
		//if there are other issues, display a "jump to other issues" dropdown
		if(!empty($this->passed_vars['issues_by_date']))
			$this->markup_string .= $this->get_issue_links_markup();
			
		if(!empty($this->passed_vars['search_string']))
			$this->markup_string .= $this->get_search_header_markup();
		
		if(!empty($this->passed_vars['issue_blurbs']))
			$this->markup_string .= $this->get_issue_blurbs_markup();
			
		//if we're just listing items from one section ....
		if(!empty($this->passed_vars['current_section']))
		{
			//show what section we're looking at
			$this->markup_string .= $this->get_current_section_markup($this->passed_vars['current_section']);
						
			//if we're just looking at the items in this section from one issue, provide a link to see items in this section from all issues
			if(!empty($this->passed_vars['view_all_items_in_section_link']) && !empty($this->passed_vars['current_issue']) )
				$this->markup_string .= $this->get_all_items_in_section_link_markup();
		}
			
		//if we're listing filtered items ....
		if(!empty($this->passed_vars['current_filters']))
		{
			$this->markup_string .= $this->get_filter_message_markup();
		}
		
		//show any featured items
		$this->markup_string .= $this->get_featured_items_markup();
	}
	
	function get_post_list_markup()
	{
		$markup_string = '';
		
		if(!empty($this->passed_vars['current_section']))
		{
			$markup_string .= '<div class="postList">'."\n";
			//provide links to other sections in the publication
			if(!empty($this->passed_vars['links_to_sections']))
				$this->markup_string .= $this->get_section_links_markup();
			$markup_string .= '<div class="back">'."\n";
			$main_list_name = $this->passed_vars['publication']->get_value('name');
		 	if(!empty($this->passed_vars['current_issue']))
				$main_list_name .= ': '.$this->passed_vars['current_issue']->get_value('name');
			$markup_string .= '<a href="'.$this->passed_vars['back_link'].'">Return to '.$main_list_name.'</a>'."\n";
		 	$markup_string .= '</div>'."\n"; //close back
		 	$markup_string .= '</div>'."\n"; // close postList
		}
		return $markup_string;
	}

/////
//  List item methods
/////
	
	function get_list_markup()
	{
		$markup_string = '';
		if(!empty($this->passed_vars['list_item_markup_strings']))
		{
			$do_section_headings = false;
			if(!empty($this->passed_vars['sections']) && $this->passed_vars['group_by_section'])
			{
				$do_section_headings = true;
				$markup_string .= '<ul class="sections">'."\n";
			}
			
			$ordered_sections = $this->get_section_ids_in_order();		
			foreach($ordered_sections as $section_id)
			{
				$list = $this->passed_vars['items_by_section'][$section_id];
				if($do_section_headings && array_key_exists($section_id, $this->passed_vars['sections']))
				{		
					$markup_string .= '<li class="section">'."\n";
					$markup_string .= $this->get_section_heading_markup($section_id);
					$markup_string .= $this->get_list_markup_for_these_items(array_keys($list));
					$markup_string .= $this->get_section_footer_markup($section_id);
					$markup_string .='</li>'."\n";
				}
				elseif(array_key_exists($section_id, $this->passed_vars['items_by_section']))
				{
					$markup_string .= $this->get_list_markup_for_these_items(array_keys($list))."\n";
				}
			}
			if($do_section_headings)
				$markup_string .= '</ul>'."\n"; 
		}
		return $markup_string;
	}
	
	
	/**
	* Given a array of item ids, returns the markup for those items in the form of an unordered list.
	* Helper function to {@link get_list_markup}.
	* @param array $item_ids Array of ids of news item entities.
	* @return string Markup for the given items.
	*/	
	function get_list_markup_for_these_items ($item_ids)
	{
		$markup_string = '';
		if(!empty($this->passed_vars['list_item_markup_strings']) && !empty($item_ids))
		{
			/* this might seem somewhat backward but it's a reasonably efficient way 
			to ensure that the ul in only output if there is in fact at least one list item to show */
			$list_body = '';
			foreach($item_ids as $item_id)
			{
				if(!empty($this->passed_vars['list_item_markup_strings'][$item_id]) && !array_key_exists($item_id, $this->passed_vars['featured_item_markup_strings']))
					$list_body .= '<article class="post">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</article>'."\n";
			}
			if(!empty($list_body))
			{
				$markup_string .= '<div class="posts">'."\n";
				$markup_string .= $list_body;
				$markup_string .= '</div>'."\n";
			}
		}
		return $markup_string;
	}
	
	function get_search_header_markup()
	{
		if(!empty($this->passed_vars['search_string']))
		{
			return '<h3 class="searchTitle">Results for <span class="searchPhrase">"'.$this->passed_vars['search_string'].'"</span></h3>'."\n";
		}
		return '';
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
	
//////
// Featured item methods
//////
	
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
	
	
	/**
	* Determines which news items should be displayed as featured items, if any.  
	* @return array Array of markup strings for featured items that should be shown, format $id => $markup_string.
	*/	
	function get_featured_items_to_show()
	{
		if(empty($this->passed_vars['current_section']))
			return $this->passed_vars['featured_item_markup_strings'];
		else
		{
			$featured_items_in_this_section = array();

			foreach($this->passed_vars['featured_item_markup_strings'] as $id => $markup)
			{
				if(!empty($this->passed_vars['items_by_section'][$this->passed_vars['current_section']->id()]))
					$section_array = $this->passed_vars['items_by_section'][$this->passed_vars['current_section']->id()];
				elseif(!empty($this->passed_vars['no_section_key']) && isset($this->passed_vars['items_by_section'][$this->passed_vars['no_section_key']]) )
					$section_array = $this->passed_vars['items_by_section'][$this->passed_vars['no_section_key']];
				else
					$section_array = array();

				if(array_key_exists($id, $section_array))
				{
					$featured_items_in_this_section[$id] = $markup;
				}	
			}
			return $featured_items_in_this_section;
		}
	}

	
/////
//  Issue methods
/////
	function get_current_issue_markup($issue)
	{
		$markup_string = '';
		$markup_string .= '<div class="issueName"><h3>'.$this->_get_issue_label($issue).'</h3></div>'."\n";
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
			$markup_string .= '<div class="allIssuesLink"><a href="'.$link.'">List all issues</a></div>';
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
		$date = prettify_mysql_datetime( $issue->get_value( 'datetime' ), $this->passed_vars['date_format'] );
		return $name.' <span class="date">('.$date.')</span>';
	}
	
	
/////
//  Section methods
/////
	function get_current_section_markup($section)
	{
		$markup_string = '';
		$name = $section->get_value('name');
		$markup_string .= '<div class="curSection"><h3>'.$name.'</h3></div>'."\n";
		return $markup_string;
	}
	
	function get_section_links_markup()
	{	
		$markup_string = '';
		if(!empty($this->passed_vars['sections']))
		{
			$markup_string .= '<div class="sectionMenu">'."\n";
			$markup_string .= '<h4 class="sectionMenuHead">Sections</h4>'."\n";
			$markup_string .= '<ul class="sections">'."\n";
			foreach($this->passed_vars['sections'] as $section_id => $section)
			{
				if(!empty($this->passed_vars['current_section']) && $this->passed_vars['current_section']->id() == $section_id)
				{
					$markup_string .= '<li class="current"><strong>'.$section->get_value('name').'</strong></li>';
				}
				else
				{
					$markup_string .=  '<li><a href="'.$this->passed_vars['links_to_sections'][$section_id].'">'.$section->get_value('name').'</a></li>';
				}
			}
			$markup_string .= '</ul>'."\n";	
			$markup_string .= '</div>'."\n";
		}
		return $markup_string;
	}
	
	function get_all_items_in_section_link_markup()
	{
		$url = $this->passed_vars['view_all_items_in_section_link'];
		$link_text = 'View items in this section from all issues';	
		return '<div class="allIssues"><a href="'.$url.'">'.$link_text.'.</a></div>'."\n";
	}
	

	function get_section_ids_in_order()
	{
		$section_ids = array_keys($this->passed_vars['items_by_section']);		
		$ordered_sections = array(); 	 //this will be an array keyed by whatever we're sorting by, which maps to the section ids

		$keys_that_are_not_section_ids = array();
		foreach($section_ids as $section_id)
		{
			if(array_key_exists($section_id, $this->passed_vars['sections']))
			{
				$section = $this->passed_vars['sections'][$section_id];
				$ordered_sections[$section->get_value('name')] = $section_id;
			}
			else
				$keys_that_are_not_section_ids[] = $section_id;
		}
		
		//ksort($ordered_sections);
		$ordered_sections = array_merge($ordered_sections, $keys_that_are_not_section_ids);
		
		return $ordered_sections; 
	}
	
	
	function get_section_heading_markup($section_id)
	{
		$markup_string = '';
		if(!empty($this->passed_vars['items_by_section'][$section_id]))
		{
			$markup_string .= '<div class="sectionInfo">';
			$section_entity = $this->passed_vars['sections'][$section_id];
			$url = $this->passed_vars['links_to_sections'][$section_id];
			
			if(!empty($url))
				$markup_string .= '<h3><a href="'.$url.'">'.$section_entity->get_value('name').'</a></h3>'."\n";
			else
				$markup_string .= '<h3>'.$section_entity->get_value('name').'</h3>'."\n";
			
			$description = $section_entity->get_value('description');
			if(!empty($description))
			{
				$markup_string .= '<p class="sectionDesc">'.$description.'</p>'."\n";
			}
			$markup_string .= '</div>';
		}
		return $markup_string;		
	}
	
	function get_section_footer_markup($section_id)
	{
		$markup_string = '';
		if(empty($this->passed_vars['current_section']) && !empty($this->passed_vars['items_by_section'][$section_id]))
		{
			$url = $this->passed_vars['links_to_sections'][$section_id];
			if(!empty($url))
			{
				$section_name = $this->passed_vars['sections'][$section_id]->get_value('name');
				$markup_string .= '<div class="sectionFoot"><span class="viewEntireSection">View all items in <a href="'.$url.'">'.$section_name.'</a>.</span></div>'."\n";
			}
		}
		return $markup_string;		
	}
	
	function get_filter_message_markup()
	{
		$markup_string = '';
		if($msg = $this->_get_filter_message())
		{
			$markup_string .= '<div class="filterMessage">'."\n";
			$markup_string .= '<h3>'.$msg.' <span class="clear">(<a href="./">All posts</a>)</span></h3>'."\n";
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
					$parts[] = '&#8220;'.$e->get_value('name').'&#8221;';
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
			$msg = 'Posts tagged with '.implode($glue, $parts);
		}
		return $msg;
	}
}
?>
