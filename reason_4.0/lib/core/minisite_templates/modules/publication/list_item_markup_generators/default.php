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
*  @author Meg Gibbs
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
									
	//methods to run, in the order that they should be run.  This should be moved up to the higher level. 
	 /// wait a minute.  Is this really smarter than just changing the run function?  might be unnecessarily complicated
/*	var $markup_methods_to_run = array( 'get_pre_markup',
										'get_title_markup',
										'get_date_markup',
										'get_description_markup',
										'get_section_markup',
										'get_links_markup',
									  ); 	*/
	
	var $show_section_name = false;

	function PublicationListItemMarkupGenerator ()
	{
	}
	
	function run ()
	{
#		$item = $this->passed_vars['item'];
/*		foreach($this->markup_methods_to_run as $method_name)
		{
			$this->markup_string .= $this->$method_name();
		} */
		
		$this->markup_string .= $this->get_pre_markup();
		$this->markup_string .= $this->get_title_markup();
		$this->markup_string .= $this->get_date_markup();
		$this->markup_string .= $this->get_description_markup();
		$this->markup_string .= $this->get_section_markup();
		$this->markup_string .= $this->get_links_markup();
	}
	
/////
// show_list_item methods
/////
	
	function get_pre_markup()
	{
		return $this->get_teaser_image_markup();
	}
	
	function get_teaser_image_markup() // {{{
	{
		$markup_string = '';
		$image = $this->passed_vars['teaser_image'];
		if (!empty($image))
		{
			$markup_string .= '<div class="teaserImage">';
			ob_start();	
			show_image( reset($image), true,false,false );
			$markup_string .= ob_get_contents();
			ob_end_clean();
			$markup_string .= '</div>';
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
			return  '<div class="date">'.$datetime.'</div>'."\n";
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
			return '<div class="desc">'.$item->get_value('description').'</div>'."\n";
	}

	function get_links_markup()
	{
		$markup_string =  '<ul class="links">'."\n";
		if(!empty($this->passed_vars['link_to_full_item']))
			$markup_string .= $this->get_more_link_markup();
		if(!empty($this->passed_vars['permalink']))
			$markup_string .= $this->get_permalink_markup();
		$markup_string .= $this->get_comment_link_markup();
		$markup_string .= '</ul>'."\n";
		return $markup_string;
	}

	function get_more_link_markup()
	{
		$item = $this->passed_vars['item'];

		$markup_string = '';
		if(!carl_empty_html($item->get_value('content')) && isset($this->passed_vars['link_to_full_item']) &&  !empty($this->passed_vars['link_to_full_item']))
		{
			$markup_string .=  '<li class="more">';
			$markup_string .=  '<a href="' . $this->passed_vars['link_to_full_item'] .'">';
			$markup_string .=  'Read more of &#8220;';
			$markup_string .=  $item->get_value('release_title') ;
			$markup_string .=  '&#8221;';
			$markup_string .=  '</a>';
			$markup_string .=  '</li>'."\n";
		}
		return $markup_string;
	}

	function get_permalink_markup()
	{
		$item = $this->passed_vars['item'];
		if(isset($this->passed_vars['permalink']) &&  !empty($this->passed_vars['permalink']))
		{
			$markup_string = '';
			$markup_string .=  '<li class="permalink">';
			$markup_string .=  '<a href="' . $this->passed_vars['permalink'] . '">'; 
			$markup_string .=  'Permalink';
			$markup_string .=  '</a>';
			$markup_string .=  '</li>'."\n";
			return $markup_string;
		}
		else
			trigger_error('Could not generate permalink markup; index '.$item->id().' is empty or undefined', WARNING);
	} 

	function get_comment_link_markup()
	{
		$item = $this->passed_vars['item'];
		$comment_count = isset($this->passed_vars['item_comment_count']) ? $this->passed_vars['item_comment_count'] : 0;
		/* we've changed this so that the link to the comments only shows up if there actually are comments.  Previously, it would have a link to take you to the form to add
		  a comment if there weren't any comments yet.  This would be very silly if commenting was disabled for the post or the blog, and annoying if it made you log in only 
		  to tell you that YOU weren't allowed to comment.  So now, if there aren't any comments, you'll just have to go to the full article to be able to comment on it. */
		$link_to_item = $this->passed_vars['link_to_full_item'];
		if($comment_count > 0)
		{
			$markup_string = '<li class="comments">';
			$view_comments_text = 'View comments ('.$comment_count.')';
			$link_to_item = $this->passed_vars['link_to_full_item'];
			$markup_string .= '<a href="'.$link_to_item.'#comments">'.$view_comments_text.'</a>';
			$markup_string .= '</li>'."\n";
		}
		elseif (isset($this->passed_vars['commenting_status']))
		{
			switch($this->passed_vars['commenting_status'])
			{
				case 'login_required':
					$markup_string = '<li class="comments noComments">';
					$markup_string .= '<a href="'.REASON_LOGIN_URL.'?dest_page='.urlencode(carl_make_link( array(), '', '', false, false).htmlspecialchars_decode($link_to_item).'#addComment').'">Leave a comment (Login required)</a>';
					$markup_string .= '</li>'."\n";
					break;
				case 'open_comments':
				case 'user_has_permission':
					$markup_string = '<li class="comments noComments">';
					$markup_string .= '<a href="'.$link_to_item.'#addComment">Leave a comment</a>';
					$markup_string .= '</li>'."\n";
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