<?php
/**
 * Image delegate
 */
/**
 * Include dependencies
 */
reason_include_once( 'entity_delegates/abstract.php' );
reason_include_once( 'function_libraries/images.php' );
reason_include_once( 'classes/sized_image.php' );
/**
 * Register delegate
 */
$GLOBALS['entity_delegates']['entity_delegates/image.php'] = 'imageDelegate';

/**
 * Image delegate
 * @todo implement methods that help with ingestion of images
 */
class imageDelegate extends entityDelegate
{
	/**
	 * @var Array of sized images by handle -- array('handle'=>sizedImage)
	 */
	protected $sized_images = array();
	
	/**
	 * Get image thumbnail markup with caption, link, etc.
	 *
	 * The same signature as get_show_image_html()
	 *
	 * @param boolean $die_without_thumbnail Don't show anything if there is no thumbnail image on disk
	 * @param boolean $show_popup_link Show a link to the full size image
	 * @param boolean  $show_description Show the description
	 * @param string $other_text Alternative description/caption to replace the one on the entity
	 * @param boolean $textonly Show just the desctiption if true
	 * @param boolean $show_author Show the author
	 * @param string $link_with_url Use the provided link to wrap image/caption
	 * @return string markup
	 */
	function get_image_markup( $die_without_thumbnail = false, $show_popup_link = true, $show_description = true, $other_text = '' , $textonly = false, $show_author = false, $link_with_url = '' )
	{
		return get_show_image_html( $this->entity, $die_without_thumbnail, $show_popup_link, $show_description, $other_text, $textonly, $show_author, $link_with_url );
	}
	
	/**
	 * Get the URL of the image
	 *
	 * @param string $size 'standard', 'thumbnail', 'original, or common variants
	 * @return string url
	 */
	function get_image_url($size='standard') {
		return reason_get_image_url($this->entity, $size);
	}
	
	function get_image_path($size='standard') {
		return reason_get_image_path($this->entity, $size);
	}
	
	function get_sized_image($handle = 'default')
	{
		if(!isset($this->sized_images[$handle]))
		{
			$this->sized_images[$handle] = new reasonSizedImage();
			$this->sized_images[$handle]->set_id($this->entity->id());
		}
		return $this->sized_images[$handle];
	}
	function get_alt_text()
	{
		return reason_htmlspecialchars(strip_tags($this->entity->get_value('description')));
	}
	function get_image_html($size = 'standard', $check_file = false, $browser_cache_bust = false, $display_ratio = 1)
	{
		if($check_file)
		{
			$path = $this->entity->get_image_path($size);
			if( file_exists($path) && (filesize($path) > 0) )
			{
				$url = $this->entity->get_image_url($size);
			}
			else
			{
				return '';
			}
		}
		else
		{
			$url = $this->entity->get_image_url($size);
		}
		if($browser_cache_bust)
		{
			if(empty($path))
				$path = $this->entity->get_image_path($size);
			$mod_time = filemtime( $path );
			$url .= '?cb='.$mod_time;
		}
		if($size = getimagesize($path))
		{
			$width = $size[0];
			$height = $size[1];
		}
		//return '<img src="'.$url.'" width="'.(round($width * $display_ratio)).'" height="'.(round($height * $display_ratio)).'" alt="'.$this->entity->get_alt_text().'" />';
		return '<img src="'.$url.'" width="'.(round($width * $display_ratio)).'" height="'.(round($height * $display_ratio)).'" alt="'.$this->entity->get_alt_text().'" />';
	}
	function get_display_name()
	{
		$markup = $this->entity->get_image_html('thumbnail', true, true);
		if(empty($markup))
		{
			$markup = $this->entity->get_image_html('standard', true, true);
		}
		if(empty($markup))
		{
			return $this->entity->get_value('name');
		}
		return $this->entity->get_value('name').'<br />'.$markup;
	}
}