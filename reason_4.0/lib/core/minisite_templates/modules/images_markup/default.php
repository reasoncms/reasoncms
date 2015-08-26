<?php
/**
 * Events markup class -- the default list markup
 * @package reason
 * @subpackage events_markup
 */
 /**
  * Include dependencies & register the class
  */
reason_include_once('minisite_templates/modules/images_markup/interface.php');
$GLOBALS['images_markup']['minisite_templates/modules/images_markup/default.php'] = 'defaultImagesListMarkup';
reason_include_once( 'function_libraries/images.php' );
reason_include_once( 'classes/sized_image.php' );
//reason_include_once('classes/media/factory.php');

/**
 * Class that generates a list markup for the events module
 */
class defaultImagesListMarkup implements imagesListMarkup
{
	function get_markup($items, $params = array())
	{
		$ret = '<ul>'."\n";
		foreach( $items AS $item )
		{	
			if($item->get_value('content'))
			{
				$caption = $item->get_value('content');
			}
			else
			{
				$caption = $item->get_value('description');
			}
		
			if($params['width'] || $params['height'])
			{
				$rsi = new reasonSizedImage;
				$rsi->set_id($item->id());
				if($params['height'])
					$rsi->set_height($params['height']);
				if($params['width'])
					$rsi->set_width($params['width']);
				if($params['crop'])
					$rsi->set_crop_style($params['crop']);
				$image_url = $rsi->get_url();
				$width = $rsi->get_image_width();
				$height = $rsi->get_image_height();
			}
			else
			{
				$image_url = reason_get_image_url($item).'?cb='.urlencode($item->get_value('last_modified'));
				$width = $item->get_value('width');
				$height = $item->get_value('height');
			}
		
			$ret .= '<li>';
		
			if ( empty($params['textonly']) )
			{
				$ret .= '<img src="'.$image_url.'" width="'.$width.'" height="'.$height.'" alt="'.htmlspecialchars(strip_tags($item->get_value('description')), ENT_QUOTES).'" />';
				if($params['show_captions'])
				{
					$ret .= '<div class="caption">'.$caption.'</div>'."\n";
				}
				if($params['show_authors'] && $item->get_value('author'))
				{
					$ret .= '<div class="author">Photo: '.$item->get_value('author').'</div>'."\n";
				}
			}
			else
			{
			
				$ret .= '<a href="'.$image_url.'" title="View image">'.$caption.'</a>'."\n";
			}
			$ret .= '</li>'."\n";
		}
		$ret .= '</ul>'."\n";
		return $ret;
	}
}
