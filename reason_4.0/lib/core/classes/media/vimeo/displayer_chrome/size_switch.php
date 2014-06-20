<?php
require_once(SETTINGS_INC.'media_integration/media_settings.php');
require_once(SETTINGS_INC.'media_integration/vimeo_settings.php');
reason_include_once( 'classes/media/vimeo/media_work_displayer.php' );
reason_include_once('classes/media/interfaces/displayer_chrome_interface.php');
reason_include_once('classes/media/vimeo/media_work_size_selector.php');

/**
 * This chrome adds a size switcher under the main media video display.
 * 
 * @author Marcus Huderle
 */
class VimeoSizeSwitchDisplayerChrome implements DisplayerChromeInterface
{
	protected $displayer;
	protected $media_work;
	protected $size_selector;
		
	public function set_media_work($media_work)
	{
		$this->media_work = $media_work;
		$this->displayer = new VimeoMediaWorkDisplayer();
		$this->displayer->set_media_work($media_work);
	}
	
	public function set_head_items($head_items)
	{
		$this->size_selector = new VimeoMediaWorkSizeSelector();
		$this->size_selector->set_head_items($head_items);
	}
	
	public function set_module($module) {}
	
	/**
	* Returns the html markup to display the media with size selectors.
	* @return string
	*/
	public function get_html_markup()
	{
		$markup = '';
		$markup .= $this->displayer->get_display_markup();
		if ($this->media_work->get_value('av_type') == 'Video')
		{
			$markup .= $this->size_selector->get_size_selector_html($this->media_work, MEDIA_WORK_SMALL_HEIGHT);
		}
		
		return $markup;
	}
	
	public function set_media_width($width)
	{
		$this->displayer->set_width($width);
	}
	
	public function set_media_height($height)
	{
		$this->displayer->set_height($height);
	}
	
	public function set_google_analytics($on)
	{
		$this->displayer->set_analytics($on);
	}
}
?>