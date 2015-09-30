<?php
require_once(SETTINGS_INC.'media_integration/vimeo_settings.php');
reason_include_once( 'classes/media/vimeo/media_work_displayer.php' );
reason_include_once('classes/media/interfaces/displayer_chrome_interface.php');

/**
 * The default media work display chrome.  It only displays the video element. The user can't
 * manually switch video size or anything using this default setup.
 *
 * @author Marcus Huderle
 */
class VimeoDefaultDisplayerChrome implements DisplayerChromeInterface
{
	protected $displayer;
	protected $media_work;
		
	public function set_media_work($media_work)
	{
		$this->media_work = $media_work;
		$this->displayer = new VimeoMediaWorkDisplayer();
		$this->displayer->set_media_work($media_work);
	}
	
	/**
	 * No head items are added because this is the barebones displayer chrome.
	 */
	public function set_head_items($head_items)
	{}
	
	public function set_module($module) {}
	
	/**
	 * Returns the html markup to simply display the media.
	 * @return string
	 */
	public function get_html_markup()
	{
		return $this->displayer->get_display_markup();
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