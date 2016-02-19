<?php

reason_include_once('minisite_templates/modules/gallery_markup/interfaces/item.php');
$GLOBALS['gallery_markup']['minisite_templates/modules/gallery_markup/social/social_item.php'] = 'socialGalleryItemMarkup';
/**
 * Markup class for showing the single item
 */
class socialGalleryItemMarkup implements galleryItemMarkup
{
	protected $bundle;
	/**
	 * Modify the page's head items, if desired
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items)
	{
		
	}
	
	/**
	 * Set the function bundle for the markup to use
	 * @param object $bundle
	 * @return void
	 */
	public function set_bundle($bundle)
	{
		$this->bundle = $bundle;
	}
	
	/**
	 * Get the item markup
	 * @return string markup
	 */
	public function get_markup($item)
	{
		$ret = '';
		if(empty($this->bundle))
		{
			trigger_error('This class needs a bundle before it can produce markup');
			return $ret;
		}
		$ret .= $this->bundle->sequence_number_markup( $item );
		$nextprev = $this->bundle->next_and_previous_images($item);
		if(!empty($nextprev['prev']))
			$ret .= $this->bundle->previous_item_markup($nextprev['prev']);
		if(!empty($nextprev['next']))
			$ret .= $this->bundle->next_item_markup($nextprev['next']);
		$ret .= '<div class="imageWrapper">';
		$ret .= $this->bundle->image_markup( $item );
		$ret .= '</div>'."\n";
		$ret .= '<div class="imageCaptionWrapper">'."\n";
		if ($item->get_value( 'content' ))
			$ret .= '<div class="fullDescription">' .  $item->get_value( 'content' ) . '</div>'."\n";
		if ($item->get_value( 'author' ))
			$ret .= '<div class="author"><h4>Photo:</h4> ' .  $item->get_value( 'author' ) . '</div>'."\n";
		if ($item->get_value( 'keywords' ))
			$ret .= '<div class="keywords"><h4>Keywords:</h4> ' .  $item->get_value( 'keywords' ) . '</div>'."\n";
		if ($item->get_value( 'datetime' ))
			$ret .= '<div class="dateTime">' .  prettify_mysql_datetime($item->get_value( 'datetime' ), $this->bundle->date_format() ) . '</div>'."\n";
		$ret .= '</div>'."\n";
		
		$ret .= '<div class="socialSharing">';
		
		$ret .= '</div>'."\n";
		
		$ret .= $this->bundle->owner_markup( $item ); 
		$ret .= $this->bundle->categories_markup( $item );
		$ret .= $this->bundle->original_size_link_markup( $item );
		
		$ret .= $this->get_social_sharing_link_markup( $item ); 
		return $ret;
	}
	
	protected function get_social_sharing_link_markup( $item )
	{
		$ret = '';
		reason_include_once('classes/social.php');
		$helper = reason_get_social_integration_helper();
		$integrators = $helper->get_social_integrators_by_interface('SocialSharingLinks');
		if (!empty($integrators))
		{
			$item_social_sharing = array();
			foreach ($integrators as $integrator_type => $integrator)
				{
					$item_social_sharing[$integrator_type]['icon'] = $integrator->get_sharing_link_icon();
					$item_social_sharing[$integrator_type]['text'] = $integrator->get_sharing_link_text();
					$item_social_sharing[$integrator_type]['href'] = $integrator->get_sharing_link_href(get_current_url());
					
				}
			$ret .= '<p class="share">';
			$ret .= '<strong class="shareTitle">Share this image:</strong> ';
			$ret .= '<span class="shareOptions">';
			foreach($item_social_sharing as $type => $integrator_type)
			{
				//$ret .= '<a href="'.$link['href'].'" class="'.htmlspecialchars($type).'" title="Share on 	'.reason_htmlspecialchars($link['text']).'">'.$sg->generateSvgMarkup($type, $link['text'], 40, 40).'</a> ';
				$ret .= '<a href="'.$integrator_type['href'].'"><img src="'.$integrator_type['icon'].'" alt="'.reason_htmlspecialchars($integrator_type['text']).'" width="50" height="50" style="vertical-align:middle;"></a> ';
			}
			$ret .= '</span>'; // shareOptions
			$ret .= '</p>'; // share
		}
		return $ret;
	}
	public function generates_item_name()
	{
		return false;
	}
}