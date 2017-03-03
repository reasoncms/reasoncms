<?php
require_once(SETTINGS_INC.'media_integration/media_settings.php');
require_once(SETTINGS_INC.'media_integration/kaltura_settings.php');
reason_include_once( 'classes/media/kaltura/media_work_displayer.php' );
reason_include_once('classes/media/kaltura/media_work_size_selector.php');
reason_include_once('classes/media/kaltura/shim.php');
reason_include_once('classes/media/interfaces/displayer_chrome_interface.php');

/**
 * The display chrome is used by the av module.  It uses the parameters unique to the AV module
 * to correctly display info about the media work.
 *
 * @author Marcus Huderle
 */
class KalturaAVDisplayerChrome implements DisplayerChromeInterface
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
		$this->displayer = new KalturaMediaWorkDisplayer();
		$this->displayer->set_media_work($media_work);
		$this->shim = new KalturaShim();
	}
	
	public function set_module($av)
	{
		$this->av_module = $av;
		$this->request = $av->request;
	}
	
	public function set_head_items($head_items)
	{
		$this->size_selector = new KalturaMediaWorkSizeSelector();
		$this->size_selector->set_head_items($head_items);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH.'media/kaltura/av.js');
		$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'media/kaltura/av.css');
	}
	
	/**
	 * Returns the html markup to display the media with share/download info.
	 * @return string
	 */
	public function get_html_markup()
	{
		$markup = '';
	
		if ($this->media_work->get_value('transcoding_status') == 'ready')
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
				if ($this->media_work->get_value('av_type') == 'Video')
				{
					$markup .= $this->size_selector->get_size_selector_html($this->media_work, $height);
				}
				
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
				
				if ($this->media_work->get_value('show_download'))
				{
					$markup .= '<div class="download">'."\n";
					$markup .= '<h5 class="download_label">Download:</h5>'."\n";
					$markup .= '<ul class="media_file_list">'."\n";
					
					// Offer an original file download link if this parameter is set
					if ($this->av_module->params['offer_original_download_link'] && !empty($this->shim))
					{
						$file_ext = $this->shim->get_source_file_extension($this->media_work);
						if($orig_url = $this->shim->get_original_data_url($this->media_work->get_value('entry_id')))
							$markup .= '<li class="orig_li"><a href="'.$orig_url.'">original (.'.$file_ext.')</a></li>'."\n";
					}
					
					if ($this->media_work->get_value('av_type') == 'Video')
					{
						// We must provide the url for each size here so that the javascript has a hook for the download links.
						$mp4_vals = array();
						$webm_vals = array();
						
						$small_av_files = $this->displayer->_get_suitable_flavors(MEDIA_WORK_SMALL_HEIGHT, MEDIA_WORK_SMALL_HEIGHT);
						$small_mp4 = $small_av_files[0];
						$mp4_vals['small'] = $small_mp4->get_value('download_url');
						$small_webm = $small_av_files[1];
						$webm_vals['small'] = $small_webm->get_value('download_url');
						
						$medium_av_files = $this->displayer->_get_suitable_flavors(MEDIA_WORK_MEDIUM_HEIGHT, MEDIA_WORK_MEDIUM_HEIGHT);
						$med_mp4 = $medium_av_files[0];
						$mp4_vals['medium'] = $med_mp4->get_value('download_url');
						$med_webm = $medium_av_files[1];
						$webm_vals['medium'] = $med_webm->get_value('download_url');
						
						$large_av_files = $this->displayer->_get_suitable_flavors(MEDIA_WORK_LARGE_HEIGHT, MEDIA_WORK_LARGE_HEIGHT);
						$large_mp4 = $large_av_files[0];
						$mp4_vals['large'] = $large_mp4->get_value('download_url');
						$large_webm = $large_av_files[1];
						$webm_vals['large'] = $large_webm->get_value('download_url');
						
						$av_files = $this->displayer->get_media_files();
						
						$markup .= '<li class="mp4_li"><a href="'.$av_files[0]->get_value('download_url').'" 
											data-small-url="'.$mp4_vals['small'].'"
											data-medium-url="'.$mp4_vals['medium'].'"
											data-large-url="'.$mp4_vals['large'].'">.mp4</a></li>'."\n";
						$markup .= '<li class="webm_li"><a href="'.$av_files[1]->get_value('download_url').'" 
											data-small-url="'.$webm_vals['small'].'"
											data-medium-url="'.$webm_vals['medium'].'"
											data-large-url="'.$webm_vals['large'].'">.webm</a></li>'."\n";
					}
					elseif ($this->media_work->get_value('av_type') == 'Audio')
					{
						$av_files = $this->displayer->get_media_files();
						foreach ($av_files as $file) 
						{
							$url = $file->get_value('download_url') ? $file->get_value('download_url') : $file->get_value('url');
							$extension = $file->get_value('mime_type');
							// people know what mp3 is, not mpeg, so we display mpegs as mp3s
							if ($extension == 'audio/mpeg')
							{	
								$extension = 'audio/mp3';
							}
							$parts = explode('/', $extension);
							$extension = end($parts);
							$markup .= '<li class="'.reason_htmlspecialchars(str_replace(' ','-',$extension)).'_li"><a href="'.reason_htmlspecialchars($url).'">.'.reason_htmlspecialchars($extension).'</a></li>'."\n";
						}
					}
					$markup .= '</ul>'."\n";
					$markup .= '</div>'."\n";
				}
				$markup .= '</div>'."\n";
			}
		}
		else
		{
			if ($this->media_work->get_value('av_type') == 'Video') {
				$markup .= '<p>This video is currently processing. Try again later.</p>'."\n";
			} elseif ($this->media_work->get_value('av_type') == 'Audio') {
				$markup .= '<p>This audio file is currently processing. Try again later.</p>'."\n";
			} else {
				$markup .= '<p>This item is currently processing. Try again later.</p>'."\n";
			}
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