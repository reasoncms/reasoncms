<?php
/**
 * Displayer for Media works integrated with YouTube
 *
 * This file contains the YoutubeMediaWorkDisplayer class. It simply uses YouTube embedding to
 * display itself with an iframe.
 *
 * @package reason
 * @subpackage classes
 *
 * @author Marcus Huderle
 */
  
/**
 * include dependencies
 */
include_once('reason_header.php');
reason_include_once('classes/media/interfaces/media_work_displayer_interface.php');
require_once(SETTINGS_INC.'media_integration/media_settings.php');

/**
 * Displayer for Media works integrated with YouTube
 *
 * Here is an example of typical use:
 *
 *	$displayer = new YoutubeMediaWorkDisplayer();
 *	$displayer->set_media_work($my_media_work);
 *	$displayer->set_height('small');
 *	$html = $displayer->get_display_markup();
 */
class YoutubeMediaWorkDisplayer implements MediaWorkDisplayerInterface
{

	/**
	* @var object reason media work
	*/
	protected $media_work;

	/**
	* @var int width of display
	*/
	protected $width = 0;
	
	/**
	* @var int default width of display if none set
	*/
	protected $default_width = 360; // this variable shouldn't ever actually be used
	
	/**
	* @var int height of display
	*/
	protected $height = 0;
	
	/**
	* @var int default height of display if none is set
	*/
	protected $default_height = MEDIA_WORK_SMALL_HEIGHT;
	
	/** 
	* @var bool show controls flag;
	*/
	protected $show_controls = true;
	
	/** 
	* @var bool autostart flag
	*/
	protected $autostart = false;
	
	/**
	 * @var bool $analytics_on
	 */
	private $analytics_on = true;
	
	/**
	* @access public
	* @param $media_work entity
	*/
	public function set_media_work($media_work)
	{
		$this->media_work = $media_work;
	}

	/**
	* Sets the width for the displayer.
	* @access public
	* @param $width int
	*/
	public function set_width($width)
	{
		$this->width = $width;
	}	
	
	/**
	* @access private
	* @return int
	*/
	private function _get_default_width()
	{
		return $this->default_width;
	}
	
	/**
	* @access public
	* @param $height int or 'small', 'medium', 'large'
	*/
	public function set_height($height)
	{
		$this->height = $height;
	}	
	
	/**
	* @access private
	* @return integer
	*/
	private function _get_default_height()
	{
		return $this->default_height;
	}	
	
	/**
	* Returns the appropriate embedding width for the displayer. 
	*
	* @access private
	* @return integer
	*/
	public function get_embed_width() 
	{
		if ( !empty($this->width) )
			return $this->width;
		else 
			return $this->_get_width_from_height();
	}
	
	/**
	* Returns the appropriate embedding height for the displayer. 
	*
	* @access private
	* @return integer
	*/	
	public function get_embed_height()
	{
		if ( !empty($this->height) )
			return $this->_get_height();
		else
			return $this->_get_height_from_width();
	}
	
	/**
	* YouTube integration doesn't use media files, so we return an empty array.
	* @access public
	* @return array of media files
	*/
	public function get_media_files()
	{
		$files = array();
		return $files;
	}
	
	/**
	* @access public
	* @param $val bool
	*/
	public function set_autostart($val)
	{
		$this->autostart = $val;
	}

	/**
	 * @access public
	 * @param $val bool
	 */
	public function set_show_controls($val)
	{
		$this->show_controls = $val;
	}	
	
	/**
	 * Returns a width value in pixels for the current height of the displayer.  We assume a 4:3
	 * video aspect ratio, since the actual files stored by YouTube are different aspect ratios.
	 * Additionally, 4:3 still looks fine with 16:9 videos, for example.
	 *
	 * @return int
	 */
	function _get_width_from_height()
	{		
		return $this->_get_height()*1.3333; // assume a 4:3 aspect ratio
	}
	
	/**
	 * Returns an int keeping in mind the allowed enums.
	 *
	 * @return int
	 */ 
	private function _get_height()
	{
		if ($this->height == 'small')
		{
			return MEDIA_WORK_SMALL_HEIGHT;
		}
		elseif ($this->height == 'medium')
		{
			return MEDIA_WORK_MEDIUM_HEIGHT;
		}
		elseif ($this->height == 'large')
		{
			return MEDIA_WORK_LARGE_HEIGHT;
		}
		else
		{
			return $this->height;
		}
	}
	
	/**
	 * Returns the height in pixels from the current width of the displayer.   We assume a 4:3
	 * video aspect ratio, since the actual files stored by YouTube are different aspect ratios.
	 * Additionally, 4:3 still looks fine with 16:9 videos, for example.
	 *
	 * @access private
	 * @return int
	 */
	private function _get_height_from_width()
	{
		return $this->width*0.75; // assume a 4:3 aspect ratio
	}	
	
	/**
	* Returns the html markup that will return the iframe markup for the media.
	*
	* @access public
	* @return string or false
	*/
	function get_display_markup()
	{	
		if (isset($this->media_work))
		{
			if ( !empty($this->height) )
			{
				$iframe_height = $this->_get_height();
			}
			else
			{
				$iframe_height = $this->_get_height_from_width();
			}
			
			if ( !empty($this->width) )
				$iframe_width = $this->width;
			else 
				$iframe_width = $this->_get_width_from_height();				
				
			//add video class using string on object
			$markup = '<iframe class="media_work_iframe video" title="Video" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" height="'.intval($iframe_height).'" width="'.intval($iframe_width).'" ';

			$markup .= 'src="'.$this->get_iframe_src($iframe_height, $iframe_width).'" ';
			
			$markup .= '>';
			$markup .= '</iframe>'."\n";
			
			return $markup;
		}
		else 
			return false;
	}
	
	public function get_iframe_src($iframe_height, $iframe_width)
	{
		$hash = $this->get_hash();
		$src = '//'.HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'scripts/media/media_iframe.php?media_work_id='.$this->media_work->id().'&amp;hash='.$hash;
		
		$src .= '&amp;height='.intval($iframe_height);
		$src .= '&amp;width='.intval($iframe_width);
			
		if ($this->autostart)
			$src .= '&amp;autostart=1';
			
		if (!$this->show_controls)
			$src .= '&amp;show_controls=false';
		
		if (!$this->analytics_on)
			$src .= '&amp;disable_google_analytics=true';
		return $src;
	}
	
	/**
	* Returns the html markup that will embed the media work.  Returns false if something is wrong.
	*
	* @return string or false
	*/
	function get_embed_markup()
	{		
		if (isset($this->media_work))
		{
			$markup = '';
			if ( !empty($this->height) )
			{
				$iframe_height = $this->_get_height();
			}
			else
			{
				$iframe_height = $this->_get_height_from_width();
			}
			
			if ( !empty($this->width) )
				$iframe_width = $this->width;
			else 
				$iframe_width = $this->_get_width_from_height();
			
			$markup .= '<div style="width:100%;height:0;padding-bottom:'.($iframe_height/$iframe_width*100).'%;position:relative;">';
			$markup .= '<iframe class="media_work_iframe" title="Video" width="'.intval($iframe_width).'" height="'.intval($iframe_height).'" ';
			
			$src = 'https://www.youtube.com/embed/'.$this->media_work->get_value('entry_id');
			
			$vars = array();				
			if ($this->autostart)
				$vars[] = 'autoplay=1';
			if (!$this->show_controls)
				$vars[]  .= 'controls=0';
			if (count($vars) > 0)
			{
				$src .= '?'.implode('&', $vars);
			}
			
			$markup .= 'src="'.$src.'" ';
			$markup .= 'frameborder="0" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;">';
			$markup .= '</iframe>'."\n";
			$markup .= '</div>';
			
			return $markup;
		}
		else 
		{
			return false;
		}
	}
	
	/**
 	* Gets a hash associated with the current media work. Used for validating the iframe script.
 	*/	
	public function get_hash()
	{
		if(empty($this->media_work))
			return NULL;
		return md5('media-work-hash-'.$this->media_work->id().'-'.$this->media_work->get_value('created_by').'-'.$this->media_work->get_value('creation_date'));
	}
	
	public function get_media_file_hash($media_file)
	{}
	
 	public function get_original_filename_hash()
 	{}
 	
 	/**
	 * Enables/Disables google analytics.
	 * @param bool $on
	 */
	public function set_analytics($on)
	{
		if ($on)
		{
			$this->analytics_on = true;
		}
		else
		{
			$this->analytics_on = false;
		}
	}
}
?>