<?php
reason_include_once( 'minisite_templates/modules/publication/list_item_markup_generators/related_item.php' );

/**
 * Extends the basic related list item markup generator to add a "full story" link after the description. 
 *
 * @author Nathan White
 *
 */

class RelatedFullStoryLinkListItemMarkupGenerator extends RelatedListItemMarkupGenerator
{
	function get_description_markup()
	{
		$item = $this->passed_vars['item'];
		$link_to_full_item = $this->passed_vars['link_to_full_item'];
				
		if (isset($link_to_full_item) && !empty($link_to_full_item))
		{
			$link = ' <a href="' .$link_to_full_item. '" title="'.$item->get_value('release_title').'">';
			$link .= 'full story';
			$link .= '</a>';
		}
		else $link = '';
			
		if($item->get_value('description'))
			return '<div class="desc">'.$item->get_value('description').$link.'</div>'."\n";
	}
}
?>
