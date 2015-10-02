<?php
/**
 * Interface for image list markup
 * @package reason
 * @subpackage images_markup
 */
/**
 * Interface for image list markup
 */
interface imagesListMarkup
{
	/**
	 * Get the list markup
	 *
	 * @return string markup
	 */
	public function get_markup($items, $params);
}
