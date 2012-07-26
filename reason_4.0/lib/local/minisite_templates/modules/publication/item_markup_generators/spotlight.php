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
class SpotlightItemMarkupGenerator extends PublicationMarkupGenerator
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
		$this->markup_string .= '<div id="spotlightcontent">'."\n";
		//$this->markup_string .= $this->get_title_section();
		if (get_theme($this->passed_vars['site_id'])->get_value('name') != 'luther2010' && $this->should_show_images_section())
		{
			$this->markup_string .= '<div class="images">'.$this->get_images_section().'</div>'."\n";
		}
		if($this->should_show_content_section())
		{
			$this->markup_string .= '<div class="text">'.$this->get_content_section().'</div>'."\n";
		}
		$this->markup_string .= '</div>'."\n";
		$this->markup_string .= '</div>'."\n";
		$this->markup_string .= '</div>'."\n";
	}
	
	/**
	 * Answers question: should the "Comment Added" section be displayed?
	 *
	 * Basically this should answer true if a comment was just posted (e.g. the request has a value for comment_posted_id)
	 *
	 * @return boolean
	 */
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
                $markup_string .= '<div class="figure">'."\n";
                $image = $this->passed_vars['item_images'];
                if (!empty($image))
                {
                $id = reset($image)->get_value('id');
                $imgtype = reset($image)->get_value('image_type');
                $full_image_name = WEB_PHOTOSTOCK.$id.'.'.$imgtype;
                        //$markup_string .= '<div class="image">';
                        ob_start();
                        //reason_get_image_url(reset($image), 'standard');
                        //print_r(array_values( $image));
                        echo '<img src="'.$full_image_name.'"/>';
                        //show_image( reset($image), true,true,false );
                        $markup_string .= ob_get_contents();
                        ob_end_clean();
                        $markup_string .= '</div>';
                }
                return $markup_string;
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
