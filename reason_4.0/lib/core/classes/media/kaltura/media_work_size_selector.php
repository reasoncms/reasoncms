<?php

require_once(SETTINGS_INC.'media_integration/kaltura_settings.php');

/**
 * Class used for generating size selectors for the video displayer.
 */ 
class KalturaMediaWorkSizeSelector
{
	/**
	 * Returns the html markup used to display the size selector elements.
	 * @param $media_work
	 * @param $initial_height the initial height the media work is displayed at
	 * @return string html
	 */
	public function get_size_selector_html($media_work, $initial_height)
	{
		$small_url = carl_make_link(array('displayer_height'=>MEDIA_WORK_SMALL_HEIGHT));
		$medium_url = carl_make_link(array('displayer_height'=>MEDIA_WORK_MEDIUM_HEIGHT));
		$large_url = carl_make_link(array('displayer_height'=>MEDIA_WORK_LARGE_HEIGHT));

		if (MEDIA_WORK_SMALL_HEIGHT == $initial_height || strtolower($initial_height) == 'small')
			$small_link = '<strong><a>Small <em>('.MEDIA_WORK_SMALL_HEIGHT.'p)</em></a></strong>'."\n";
		else
			$small_link = '<a href="'.$small_url.'" >Small <em>('.MEDIA_WORK_SMALL_HEIGHT.'p)</em></a>'."\n";
	
		if ( MEDIA_WORK_MEDIUM_HEIGHT == $initial_height || strtolower($initial_height) == 'medium')
			$medium_link = '<strong><a>Medium <em>('.MEDIA_WORK_MEDIUM_HEIGHT.'p)</em></a></strong>'."\n";
		else
			$medium_link = '<a href="'.$medium_url.'" >Medium <em>('.MEDIA_WORK_MEDIUM_HEIGHT.'p)</em></a>'."\n";
			
		if (MEDIA_WORK_LARGE_HEIGHT == $initial_height || strtolower($initial_height) == 'large')
			$large_link = '<strong><a>Large <em>('.MEDIA_WORK_LARGE_HEIGHT.'p)</em></a></strong>'."\n";
		else
			$large_link = '<a href="'.$large_url.'" >Large <em>('.MEDIA_WORK_LARGE_HEIGHT.'p)</em></a>'."\n";
	
		$html = '';
		$html .= '<div class="size_links">'."\n";
		$html .= '<h4>Video Size:</h4>'."\n";
		$html .= '<ul>'."\n";
		$html .= '<li data-size="'.MEDIA_WORK_SMALL_HEIGHT.'" data-link="'.$small_url.'" class="small_link"><span class="buttonSize">'.$small_link.'</span></li>'."\n";
		$html .= '<li data-size="'.MEDIA_WORK_MEDIUM_HEIGHT.'" data-link="'.$medium_url.'" class="medium_link"><span class="buttonSize">'.$medium_link.'</span></li>'."\n";
		$html .= '<li data-size="'.MEDIA_WORK_LARGE_HEIGHT.'" data-link="'.$large_url.'" class="large_link"><span class="buttonSize">'.$large_link.'</span></li>'."\n";
		$html .= '</ul>'."\n";
		$html .= '</div>'."\n";
		
		return $html;
	}
	
	/**
	 * Loads the size_selector's javascript and css for kaltura.
	 * @param $head_items
	 */
	public function set_head_items($head_items)
	{
		$head_items->add_javascript(JQUERY_URL, true);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/kaltura/size_selector.js');
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'media/kaltura/size_selector.css');
	}
}
?>