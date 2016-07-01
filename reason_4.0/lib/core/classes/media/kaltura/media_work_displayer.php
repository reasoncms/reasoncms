<?php
/**
 * Displayer for Media works integrated with Kaltura
 *
 * This file contains the KalturaMediaWorkDisplayer class.  It is used for embedding a media work using 
 * html5 tags.  It falls back to a flash video player if the user's browser doesn't support the 
 * html5 video tag.
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
require_once(SETTINGS_INC.'media_integration/media_settings.php');
require_once(SETTINGS_INC.'media_integration/kaltura_settings.php');
reason_include_once('classes/media/interfaces/media_work_displayer_interface.php');
include_once(INCLUDE_PATH.'kaltura/KalturaClient.php');
reason_include_once('classes/media/kaltura/shim.php');
reason_include_once('function_libraries/image_tools.php');
reason_include_once( 'classes/av_display.php' );

/**
 * Displayer for Media works integrated with Kaltura
 *
 * Here is an example of typical use:
 *
 *	$displayer = new KalturaMediaWorkDisplayer();
 *	$displayer->set_media_work($my_media_work);
 *	$displayer->set_height('small');
 *	$html = $displayer->get_display_markup();
 *
 * It is not recommended to explicitly use set_width() because the video will most likely not
 * be the same aspect ratio.  When only a height is set, the video player will always fit the 
 * video nicely.  
 *
 * When using this class to display an audio media work, specifying a height/width will have
 * no effect.
 */
class KalturaMediaWorkDisplayer implements MediaWorkDisplayerInterface
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
	 * @var array
	 */
	protected $media_files;
	
	/**
	 *  @var array 
	 */
	protected $media_type_map = array(
		'Video' => KalturaMediaType::VIDEO,
		'Audio' => KalturaMediaType::AUDIO,
	);
	
	/**
	 * @var array
	 */
	private static $ratios = array();
	
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
		if ($media_work->get_value('integration_library') == 'kaltura')
		{
			$this->media_work = $media_work;
			self::$ratios = array(); // make sure to reset the ratios array
		}
		else
		{
			trigger_error('The Media Work Displayer may only use kaltura-integrated media works.');
		}
	}

	/**
	 * Sets the width for the displayer. It is not recommended to use this function. Instead, use 
	 * set_height().
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
	 * Returns one media file for both mp4 and webm that is closest to the width and height of the
	 * displayer.  If audio, it just returns all of the media files.
	 *
	 * @access public
	 * @return array of media files
	 */
	public function get_media_files()
	{
		if ($this->media_work->get_value('av_type') == 'Video')
		{
			$embed_height = $this->_get_height();
			$embed_width = $this->width;
				
			$media_files = $this->_get_suitable_flavors($embed_width, $embed_height);
			
			$mp4 = false;
			$webm = false;
			$this->media_files = array();
			foreach ($media_files as $media_file)
			{
				// break if we've already found one of each type
				if ($mp4 && $webm)
					break;
				
				if ($mp4 == false && $media_file->get_value('mime_type') == 'video/mp4')
				{
					$this->media_files[] = $media_file;
					$mp4 = true;
					
				}
				elseif ($webm == false && $media_file->get_value('mime_type') == 'video/webm')
				{
					$this->media_files[] = $media_file;
					$webm = true;
				}
			}
			
			// sort the items the same way you would display them
			usort($this->media_files, 'cmp');	
		}
		else
		{
			$es = new entity_selector();
			$es->add_type(id_of('av_file'));
			$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_to_av_file'));
			$this->media_files = $es->run_one();
		}
		return $this->media_files;
	}

	private function _smallest_media_file()
	{
		$media_files = $this->_get_suitable_flavors(0, MEDIA_WORK_SMALL_HEIGHT);
			
		$mp4 = false;
		$webm = false;
		$this->media_files = array();
		foreach ($media_files as $media_file)
		{
			// break if we've already found one of each type
			if ($mp4 && $webm)
				break;
			
			if ($mp4 == false && $media_file->get_value('mime_type') == 'video/mp4')
			{
				$this->media_files[] = $media_file;
				$mp4 = true;
				
			}
			elseif ($webm == false && $media_file->get_value('mime_type') == 'video/webm')
			{
				$this->media_files[] = $media_file;
				$webm = true;
			}
		}
		
		// sort the items the same way you would display them
		usort($this->media_files, 'cmp');
		return current($this->media_files);
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
	 * Calculates the aspect ratio (width/height) of a media work for the current size of 
	 * the displayer.
	 *
	 * @param object $media_work
	 * @return mixed float $aspect_ratio or false if no files
	 */
	function get_video_aspect_ratio($media_work)
	{	
		$height = $this->_get_height();
		if (!empty(self::$ratios[$height]))
		{
			return self::$ratios[$height];
		}
		$media_file = $this->_smallest_media_file();
		
		if($media_file)
		{
			$width = (float)$media_file->get_value('width');
			$height = (float)$media_file->get_value('height');
			self::$ratios[$height] = $width/$height;
		}
		else
		{
			return false;
		}
			
		return self::$ratios[$height];
	}
	
	
	/**
	 * Returns a width generated from the aspect ratio of the original media work.  If no height is specified,
	 * it falls back to get_default_width().
	 *
	 * @return int
	 */
	function _get_width_from_height()
	{
		$aspect_ratio = $this->get_video_aspect_ratio($this->media_work);
		if ($aspect_ratio != false)
		{
			if ( !empty($this->height) )
			{
				return $aspect_ratio * $this->_get_height();
			}
			elseif ( !empty($this->default_height) )
			{
				return $aspect_ratio * $this->default_height;
			}
		}
		else
			return $this->_get_default_width();
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
	 * Returns a height generated from the aspect ratio of the original media work.  If no width is specified,
	 * it falls back to get_default_height().
	 *
	 * @access private
	 * @return int
	 */
	private function _get_height_from_width()
	{
		if ( !empty($this->width) )
		{
			$aspect_ratio = $this->get_video_aspect_ratio($this->media_work);
			if ($aspect_ratio != false)
				return $this->width / $aspect_ratio;
		}
		return $this->_get_default_height();
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
			if ($this->media_work->get_value('av_type') == 'Video')
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
			}
			else // for audio
			{
				$height = false;
				$iframe_height = 50;
				if ( !empty($this->width) )
					$iframe_width = $this->width;
				else
					$iframe_width = 360;
			}
			//add video or audio class using string on object
			$markup = '<iframe class="media_work_iframe ' . strtolower($this->media_work->get_value('av_type')) . '" marginwidth="0" marginheight="0" scrolling="no" frameborder="0" allowfullscreen="allowfullscreen" height="'.intval($iframe_height).'" width="'.intval($iframe_width).'" ';

			$markup .= 'src="'.$this->get_iframe_src($iframe_height, $iframe_width).'" ';
			
			if(!empty($this->media_work->get_value('av_type')))
				$markup .= 'title="'.reason_htmlspecialchars($this->media_work->get_value('av_type')).'" ';
			
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
 	 * Gets a hash associated with the current media work. Used for validating the iframe script.
 	 */	
	public function get_hash()
	{
		if(empty($this->media_work))
			return NULL;
		return md5('media-work-hash-'.$this->media_work->id().'-'.$this->media_work->get_value('created_by').'-'.$this->media_work->get_value('creation_date'));
	}
	
	
	/**
	 * Returns the html markup that will embed the media work.  Returns false if something is wrong.
	 *
	 * @return string or false
	 */
	function get_embed_markup()
	{		
		// First, ensure that a media work has been set
		if (isset($this->media_work))
		{
			$media_type = $this->media_work->get_value('av_type');
			if ( !empty($media_type) )
			{
				if ($this->media_type_map[$media_type] == KalturaMediaType::VIDEO)
				{
					return $this->_get_video_embed_markup();
				}
				elseif ($this->media_type_map[$media_type] == KalturaMediaType::AUDIO)
				{
					return $this->_get_audio_embed_markup();
				}
				else
				{
					trigger_error('Media Work with id = '.$this->media_work->id().' has an invalid av_type.');
				}
			}
			else
			{
				trigger_error('Media Work with id = '.$this->media_work->id().' has no av_type field.');
			}
		}

		// If something above isn't right, let's return false
		return false;
	}		

	/**
	 * Generates and returns the html markup used to represent a video media work.  Uses html5 video
	 * tags with a flash player fallback.
	 *
	 * @access private
	 * @return string embed markup
	 */
	private function _get_video_embed_markup()
	{	
		// change preload to "none" to allow poster support in IE9...
		$markup = '<video id="'.$this->media_work->id().'" preload="metadata" ';
		
		if ($this->show_controls) $markup .= 'controls="controls" ';
		if ($this->autostart) $markup .= 'autoplay="autoplay" ';
			
		// specify width and height attributes explicitly in the video tag every time
		// this is needed 1) so the browswer doesn't have to figure it out(?), 2) so the placard image
		// works nicely, and 3) so the flash video player is properly scaled because it doesn't automatically scale itself.
		$embed_width = $this->get_embed_width();
		$embed_height = $this->get_embed_height();
		
		$markup .= 'width="'.intval($embed_width).'" ';
		$markup .= 'height="'.intval($embed_height).'" ';			
			
		if ($poster_url = $this->_get_poster_image_url())
		{
			$markup .= 'poster="'.$poster_url.'" ';
		}
		
		$markup .= '>'."\n";
		
		$this->media_files = $this->_get_suitable_flavors($embed_width, $embed_height);
		
		$mp4 = null;
		foreach ($this->media_files as $media_file)
		{
			// Grab the largest available mp4 media file to use in the flash fallback
			if ($mp4 == null && $media_file->get_value('mime_type') == 'video/mp4')
				$mp4 = $media_file;
				
			$markup .= $this->_get_video_source_tag($media_file, $media_file->get_value('mime_type'));
		}
		
		if ($mp4 != null)
		{
			// Flash Video Fallback markup
			$avd = new reasonAVDisplay();
			$avd->set_video_dimensions($embed_width, $embed_height);
			
			$avd_autoplay = $this->autostart ? 'true' : 'false';
			$avd->set_parameter('flv', 'autostart', $avd_autoplay);
			$avd->set_parameter('flv', 'controlbar', 'over');
			
			if ($poster_url)
				$avd->set_placard_image($poster_url);
			
			if ( !$this->show_controls )
				$avd->set_parameter('flv', 'controlbar', '0');
			
			//$mp4->set_value('media_format', 'Flash Video');
			$mp4->set_value('url', $this->_match_protocol($mp4->get_value('url').'/a.mp4'));
			
			$avd_markup = $avd->get_embedding_markup_for_flash_video($mp4);
			//return $avd_markup; // uncomment this if testing the flash player
			$markup .= $avd_markup;
		}
		
		$markup .= '</video>'."\n";
		
		return $markup;
	}
	
	/**
	 * Returns the html markup for a single source tag inside a video tag.
	 *
	 * @param object $media file
	 * @param string $mime_type
	 */
	private function _get_video_source_tag($media_file, $mime_type)
	{
		$markup = '<source src="'.$this->_match_protocol($media_file->get_value('url')).'" ';
			
		if (!empty($mime_type))
			$markup .= 'type="'.$mime_type.'"';
		
		$markup .= '/>'."\n";
		return $markup;
	}
	
	/**
	 * Returns an array with the media files for each mime type that are the closest to the dimensions
	 * of this displayer.  The array should be sorted biggest to smallest and alternating between both formats.
	 *
	 * @param int width
	 * @param int height
	 * @return array
	 */
	function _get_suitable_flavors($width, $height)
	{	
		$dim = 'width';
		$val = $width;
		if ($height) {
			$dim = 'height';
			$val = $height;
		}
		
		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_to_av_file'));
		$media_files = $es->run_one();
		
		$html5_flavors = array();
		
		// split into arrays of each mime type (mp4, webm)
		foreach ($media_files as $file)
		{
			$html5_flavors[$file->get_value('mime_type')][] = $file;
		}
		
		$suitable_flavors = array();
		$smallest_flavors = array();
		
		// build an array containing media files that are less than the dimensions
		foreach ($html5_flavors as $name => $type)
		{	
			foreach ($type as $flavor)
			{
				$flavor_distance = $this->_get_flavor_dimension_distance($flavor, $dim, $val);
				// 30 means the flavor is 30 pixels bigger than the specified height
				// Let's say that you should grab a file that is up to 90 pixels higher than the specified height
				if ( $flavor_distance < 90) 
				{
					$suitable_flavors[] = $flavor;
				}
				
				// We also have to ensure that this function returns at least the smallest flavors
				if (empty($smallest_flavors) || $flavor->get_value($dim) == $smallest_flavors[0]->get_value($dim))
				{
					$smallest_flavors[] = $flavor;
				}
			}
		}
		
		// Make sure there exist suitable flavors
		if (empty($suitable_flavors))
		{
			$suitable_flavors = $smallest_flavors;
		}
		
		// Sort the array by largest to smallest media files and mp4 comes before webm 
		if (!function_exists('cmp'))
		{
			function cmp($a, $b)
			{
				$aval = $a->get_value('mime_type') == 'video/mp4' ? $a->get_value('height') + 1 : $a->get_value('height');
				$bval = $b->get_value('mime_type') == 'video/mp4' ? $b->get_value('height') + 1 : $b->get_value('height');
				
				if ($aval < $bval)
				{
					return 1;
				}
				elseif ($aval > $bval)
					return -1;
			}
		}
		usort($suitable_flavors, 'cmp');		
		
		return $suitable_flavors;
	}
	
	// Euclidean distance between this displayer's dimensions and the specified media file's dimensions
	// $dim is 'width' or 'height'
	// $val is the specified value of the width or height
	private function _get_flavor_dimension_distance($media_file, $dim, $val)
	{
		$diff = $media_file->get_value($dim) - $val;
		return $diff;
	}
	
	
	private function _get_poster_image_url()
	{
		$es = new entity_selector();
		$es->add_type(id_of('image'));	
		$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_to_primary_image'));
		$results = $es->run_one();
		
		if ( !empty($results) )
		{
			$primary_image = current($results);
			
			return reason_get_image_url($primary_image);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Generates and returns the html markup used to represent an audio media work.
	 *
	 * @access private
	 * @return string embed markup
	 */
	private function _get_audio_embed_markup()
	{
		$markup = '<audio preload="metadata" ';
		
		if ($this->show_controls)
			$markup .= 'controls="controls" ';
		if ($this->autostart)
			$markup .= 'autoplay="autoplay" ';	
		
		$markup .= '>';
		
		$es = new entity_selector();
		$es->add_type(id_of('av_file'));
		$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_to_av_file'));
		$es->set_order('av.mime_type ASC'); // 'mpeg' comes before 'ogg'
		$this->media_files = $es->run_one();
		
		$mp3 = false;
		foreach ($this->media_files as $file)
		{
			$markup .= '<source src="'.$this->_match_protocol($file->get_value('url')).'" type="'.$file->get_value('mime_type').'" />'."\n";
			if ($file->get_value('mime_type') == 'audio/mpeg')
				$mp3 = $file;
		}
		
		// Fall back to flash player
		if ($mp3)
		{
			$avd = new reasonAVDisplay();
			
			$avd_autoplay = $this->autostart ? 'true' : 'false';
			$avd->set_parameter('flv', 'autostart', $avd_autoplay);
			
			if ( !$this->show_controls )
				$avd->set_parameter('flv', 'controlbar', '0');
			
			$mp3->set_value('url', $this->_match_protocol($mp3->get_value('url').'/a.mp3'));
			
			$avd_markup = $avd->get_embedding_markup_For_flash_video($mp3);
			$markup .= $avd_markup;
			
			//return $avd_markup;  // uncomment this if testing the flash player
		}
		$markup .= '</audio>'."\n";

		return $markup;
	}
	
	private function _match_protocol($url)
	{
		if(defined('KALTURA_HTTPS_ENABLED') && KALTURA_HTTPS_ENABLED && on_secure_page())
		{
			if(strpos($url, 'http://') === 0)
			{
				return 'https://'.substr($url, 7);
			}
		}
		return $url;
	}
	
	public function get_media_file_hash($media_file)
	{
		if (!is_object($media_file))
		{
			return null;
		}
		return md5($media_file->id().$media_file->get_value('created_by').$media_file->get_value('creation_date').$this->media_work->get_value('salt'));
	}
	
	/**
 	 * Gets a hash for the end of the original filename when storing an original file.
 	 * @return string
 	 */
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