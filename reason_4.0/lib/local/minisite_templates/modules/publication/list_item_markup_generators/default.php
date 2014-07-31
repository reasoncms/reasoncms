<?php
/**
 *  @package reason
 *  @subpackage minisite_modules
 */
 
 /**
  * Include the base class
  */
reason_include_once( 'minisite_templates/modules/publication/markup_generator.php' );


/**
*  Generates the markup to display a summary of an individual news item or blog post (as it would be displayed in a list).   
*
*  Helper class to the publication minisite module.
*
* Luther edits include...
* 1. Removing redundant permalink.
* 2. Changing language for the "read more" link.
* 3. Editing the location and display of the "comment" link.
* 4. Update to some HTML5 markup
* 5. Edit default image size
*
*  @author Meg Gibbs
*  @author Nathan Dirks
*
*/

class PublicationListItemMarkupGenerator extends PublicationMarkupGenerator
{
	//variables needed to be passed from the publication module
	var $variables_needed = array( 					'use_dates_in_list', 
									'date_format', 
									'item',
									'item_comment_count', 
									'link_to_full_item', 
									'permalink',
									//'section_links', you can turn this on if show_section_name is true
									'teaser_image',
									'current_issue',
								  	'commenting_status',
									);
	
	var $show_section_name = false;

	function PublicationListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{		
		
		$this->markup_string .= $this->get_pre_markup();
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_comment_markup();
		$this->markup_string .= $this->get_title_markup();
		//$this->markup_string .= $this->get_date_markup();
		//$this->markup_string .= $this->get_comment_markup();
		$this->markup_string .= $this->get_description_markup();
		$this->markup_string .= $this->get_section_markup();
		$this->markup_string .= $this->get_link_to_full_item_markup();
		//$this->markup_string .= $this->get_links_markup();  // We don't need this, so we'll comment it out.  Leaving it here for reference.
	}
	
/////
// show_list_item methods
/////
	
	function get_pre_markup()
	{
		return $this->get_teaser_image_markup();
	}
	
	function get_teaser_image_markup()
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<figure class="teaserImage">';

			if(is_array($image))
				$image = reset($image);
			
			$rsi = new reasonSizedImage();
			$rsi->set_id($image->id());
			$rsi->set_width(400);
			$rsi->set_height(250);
			$rsi->set_crop_style('fill');

			ob_start();	
			show_image( $rsi,true,false,false );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			//$markup_string .= '<img src="/reason/sized_images/540620/b25879bda30d8a9542e03ab9670e730e.gif?cb=1397165736">';
			$markup_string .= '</figure>';
		}
		return $markup_string;
	}
	
	function get_title_markup()
	{
		$markup_string = '';
		$item = $this->passed_vars['item'];
		$link_to_full_item = isset($this->passed_vars['link_to_full_item']) ? $this->passed_vars['link_to_full_item'] : '';
				
		$markup_string .=  '<h4 class="title';
		if($item->get_value('content'))
			$markup_string .= ' postHasContent';
		else
			$markup_string .= ' postHasNoContent';
		$markup_string .= '">';
		if(isset($link_to_full_item) &&  !empty($link_to_full_item))
			$markup_string .=  '<a href="' .$link_to_full_item. '">'.$item->get_value('release_title').'</a>';
		else
			$markup_string .= $item->get_value('release_title');
		$markup_string .=  '</h4>'."\n";
		return $markup_string;
	}
	
	function get_date_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value( 'datetime') && empty($this->passed_vars['current_issue']) && $this->passed_vars['use_dates_in_list'])
		{
			$datetime = prettify_mysql_datetime( $item->get_value( 'datetime' ), $this->passed_vars['date_format'] );
			return  '<time class="date">'.$datetime.'</time>'."\n";
		}
	}
	
	function get_section_markup()
	{
		$section_markup_string = '';
		if($this->show_section_name() && !empty($this->passed_vars['section_links']))
		{
			$section_links = array();
			foreach($this->passed_vars['section_links'] as $id => $info)
			{
				$section_links[$id] = '<a href = "'.$info['url'].'">'.$info['section_name'].'</a>';
			}
			$section_markup_string = '<div class="sectionMembership"><strong>Section:</strong> '.implode(', ', $section_links).'</div>';
		}
		return $section_markup_string; 
	}
	
	function show_section_name()
	{
		return $this->show_section_name;
	}

	function get_description_markup()
	{
		$item = $this->passed_vars['item'];
		if($item->get_value('description'))
			return '<div class="description">'.$item->get_value('description').'</div>'."\n";
	}

	// A new function that returns the "Leave a comment" link
	function get_comment_markup() { 
		$markup_string = '';
		$markup_string .= $this->get_comment_link_markup();
		return $markup_string;
	}

	// A new function that returns the "Continue reading..." link
	function get_link_to_full_item_markup() {
		$markup_string = '';
		if(!empty($this->passed_vars['link_to_full_item']))
			$markup_string .= $this->get_more_link_markup();
		return $markup_string;
	}

	// We don't use this function anymore
	function get_links_markup()
	{
	}

	function get_more_link_markup()
	{
		$item = $this->passed_vars['item'];

		// Here we change the link to full article text
		$markup_string = '';
		if(!carl_empty_html($item->get_value('content')) && isset($this->passed_vars['link_to_full_item']) &&  !empty($this->passed_vars['link_to_full_item']))
		{
			$markup_string .=  '<p class="continueReading">';
			$markup_string .=  '<a href="' . $this->passed_vars['link_to_full_item'] .'">';
			$markup_string .=  'Full article';
			$markup_string .=  '</a>';
			$markup_string .=  '</p>'."\n";
		}
		return $markup_string;
	}

	function get_permalink_markup()
	{
	}

	function get_comment_link_markup()
	{
		$item = $this->passed_vars['item'];
		$comment_count = isset($this->passed_vars['item_comment_count']) ? $this->passed_vars['item_comment_count'] : 0;
		$link_to_item = $this->passed_vars['link_to_full_item'];

		// Here we change the way comment counts are rendered.
		if($comment_count >= 1)
		{
			$markup_string = '<p class="comments">';
			if($comment_count == 1)  // If one comment, we say "1 Comment"
			{
				$view_comments_text = ''.$comment_count.' Comment';
			}
			else  // If 0 or more than one comment, say "{number} Comments"
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

}
?>