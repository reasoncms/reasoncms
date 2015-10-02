<?php

reason_include_once('minisite_templates/modules/gallery_markup/interfaces/item.php');
$GLOBALS['gallery_markup']['minisite_templates/modules/gallery_markup/default/item.php'] = 'defaultGalleryItemMarkup';
/**
 * Markup class for showing the single item
 */
class defaultGalleryItemMarkup implements galleryItemMarkup
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
		
		$ret .= $this->bundle->owner_markup( $item ); 
		$ret .= $this->bundle->categories_markup( $item );
		$ret .= $this->bundle->original_size_link_markup( $item );
		return $ret;
	}
	public function generates_item_name()
	{
		return false;
	}
}