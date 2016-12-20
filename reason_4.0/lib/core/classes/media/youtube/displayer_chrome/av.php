<?php

reason_include_once( 'classes/media/youtube/media_work_displayer.php' );
reason_include_once('classes/media/youtube/media_work_size_selector.php');
reason_include_once('classes/media/youtube/shim.php');
reason_include_once('classes/media/interfaces/displayer_chrome_interface.php');
require_once(SETTINGS_INC.'media_integration/media_settings.php');

/**
 * The display chrome is used by the av module.  It uses the parameters unique to the AV module
 * to correctly display info about the media work.
 *
 * @author Marcus Huderle
 */
class YoutubeAVDisplayerChrome implements DisplayerChromeInterface
{
	protected $displayer;
	protected $shim;
	protected $size_selector;
	protected $media_work;
	protected $av_module;
	protected $request;
		
	public function set_media_work($media_work)
	{
		$this->media_work = $media_work;
		$this->displayer = new YoutubeMediaWorkDisplayer();
		$this->displayer->set_media_work($media_work);
		$this->shim = new YoutubeShim();
	}
	
	public function set_module($av)
	{
		$this->av_module = $av;
		$this->request = $av->request;
	}
	
	public function set_head_items($head_items)
	{
		$this->size_selector = new YoutubeMediaWorkSizeSelector();
		$this->size_selector->set_head_items($head_items);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/youtube/av.js');
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'media/youtube/av.css');
	}
	
	/**
	 * Returns the html markup to simply display the media.
	 * @return string
	 */
	public function get_html_markup()
	{
		$markup = '';
	
		if ($this->media_work->get_value('entry_id'))
		{			
			if ( !empty($this->request['displayer_height']) )
			{
				$height = $this->request['displayer_height'];
			}
			else
			{
				$height = $this->av_module->params['default_video_height'];
			}
			
			$this->displayer->set_height($height);
			$embed_markup = $this->displayer->get_display_markup();
			$markup .= $embed_markup;
			
			$mwh = new media_work_helper($this->media_work);
			if ($mwh->user_has_access_to_media())
			{
				$markup .= $this->size_selector->get_size_selector_html($this->media_work, $height);
				
				$markup .= '<div class="share_download_info">'."\n";
				
				$markup .= '<div class="share">'."\n";
				$markup .= '<h5 class="share_label">Share:</h5>'."\n";
				$markup .= '<ul class="share_list">'."\n";
				$facebook_url = 'http://www.facebook.com/sharer.php?u='.urlencode(get_current_url()).'&t='.urlencode($this->media_work->get_value('name'));
				$markup .= '<li><a href="'.$facebook_url.'" target="_blank">Facebook</a></li>'."\n";
				$twitter_url = 'https://twitter.com/share?url='.urlencode(get_current_url()).'&text='.urlencode($this->media_work->get_value('name'));
				$markup .= '<li><a href="'.$twitter_url.'" target="_blank">Twitter</a></li>'."\n";
				$markup .= '</ul>'."\n";
				$markup .= '</div>'."\n";
				
				if ($this->media_work->get_value('show_embed'))
				{
					$markup .= '<div class="embed">'."\n";
					$markup .= '<h5 class="embed_label">Embed:</h5>'."\n";
					$markup .= '<textarea class="embed_code" rows="7" cols="75" readonly="readonly">'."\n";
					$markup .= htmlspecialchars($embed_markup, ENT_QUOTES);
					$markup .= '</textarea>'."\n";
					$markup .= '</div>'."\n";
				}
				
				$markup .= '</div>'."\n";
			}
		}
		else
		{
			$markup .= '<p>Sorry, this media work cannot be displayed.</p>'."\n";
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