<?php
require_once(SETTINGS_INC.'media_integration/media_settings.php');
require_once(SETTINGS_INC.'media_integration/zencoder_settings.php');
reason_include_once( 'classes/media/zencoder/media_work_displayer.php' );
reason_include_once('classes/media/interfaces/displayer_chrome_interface.php');
reason_include_once('classes/media/zencoder/media_work_size_selector.php');

/**
 * This chrome adds a size switcher under the main media content if it's a video.
 */
class ZencoderSizeSwitchDisplayerChrome implements DisplayerChromeInterface
{
	protected $displayer;
	protected $media_work;
	protected $size_selector;
		
	public function set_media_work($media_work)
	{
		$this->media_work = $media_work;
		$this->displayer = new ZencoderMediaWorkDisplayer();
		$this->displayer->set_media_work($media_work);
	}
	
	/**
	 * No head items are added because this is the barebones displayer chrome.
	 */
	public function set_head_items($head_items)
	{
		$this->size_selector = new ZencoderMediaWorkSizeSelector();
		$this->size_selector->set_head_items($head_items);
	}
	
	public function set_module($module) {}
	
	/**
	 * Returns the html markup to display the video with size selectors underneath it.
	 * @return string
	 */
	public function get_html_markup()
	{
		$display_markup = '';
		$selector_markup = '';
		if ($this->media_work->get_value('av_type') == 'Video')
		{
			$r_height = -1;
			if (in_array('displayer_height', $_REQUEST))
			{
				$r_height = intval($_REQUEST['displayer_height']);
			}
			if ($r_height == MEDIA_WORK_SMALL_HEIGHT || $r_height == MEDIA_WORK_MEDIUM_HEIGHT || $r_height == MEDIA_WORK_LARGE_HEIGHT) 
			{
				$this->displayer->set_height($r_height);
				$selector_markup .= $this->size_selector->get_size_selector_html($this->media_work, $r_height);
			}
			else
			{
				$this->displayer->set_height('small');
				$selector_markup .= $this->size_selector->get_size_selector_html($this->media_work, MEDIA_WORK_SMALL_HEIGHT);
			}
		}
		$display_markup = $this->displayer->get_display_markup();
		return $display_markup . $selector_markup;
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