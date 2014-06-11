<?php
reason_include_once( 'minisite_templates/modules/publication/publication_list_markup_generators/default.php' );

/*
*
*  This extends our custom default publication list markup generator.
*  All it does is add the class "spotlight" to the artlices.
*/

class SpotlightPublicationListMarkupGenerator extends PublicationListMarkupGenerator
{


	function PublicationListMarkupGenerator ()
	{
	}

	function run()
	{	
		$this->markup_string .= $this->get_pre_list_markup();
		$this->markup_string .= $this->get_list_markup();
		$this->markup_string .= $this->get_post_list_markup();
	}

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
					$list_body .= '<article class="post spotlight">'.$this->passed_vars['list_item_markup_strings'][$item_id].'</article>'."\n";
			}
			if(!empty($list_body))
			{
				$markup_string .= '<div class="posts">'."\n";
				$markup_string .= $list_body;
				//print_r($this->passed_vars['list_item_markup_strings']);
				//print_r($this->passed_vars['item']);
				
				$markup_string .= '</div>'."\n";
			}
		}
		return $markup_string;
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
				$markup_string .= '<article class="post spotlight">'.$list_item_string.'</article>'."\n";
			}
			$markup_string .= '</div>'."\n";
			$markup_string .= '</div>'."\n";
		}
		
		return $markup_string;
	}
}
?>
