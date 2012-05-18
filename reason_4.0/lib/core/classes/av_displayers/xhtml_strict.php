<?php
/**
 * Standards-based Reason AV Display Class
 * @package reason
 * @subpackage classes
 */

/**
 * include the URL utils
 */
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once( 'function_libraries/image_tools.php' );

/**
 * Register the class
 */
$GLOBALS['reason_av_displayers'][ basename( __FILE__, '.php') ] = 'xhtmlStrictReasonAVDisplay';

/** 
 * Standards-based Reason AV Display Class
 *
 * Generates standards-compliant xhtml markup used to embed media in a web page.
 *
 * Note that Real media and Flash .swfs are still producing non-standards-based <embed>s
 *
 * @todo figure out how to embed Real media and Flash .swf (not flvs -- those are fine)
 * in a standards-compliant way.
 *
 * @todo Either figure out how to embed everything without conditional comments, or
 * figure out how HTML comments can survive Tidy
 *
 * @todo add placard image to all players that support it
 *
 * @author Matt Ryan
 */

class xhtmlStrictReasonAVDisplay
{
	/**
	 * Maps the format specified in the entity's media_format field to a method
	 * @var array keys are the entity values in media_format; values are the class method used to generate the markup for that format
	 */	
	var $formats_to_functions = array(
										'Quicktime'=>'get_embedding_markup_for_quicktime',
										'Real'=>'get_embedding_markup_for_real',
										'MP3'=>'get_embedding_markup_for_flash_video',
										'Windows Media'=>'get_embedding_markup_for_windows_media',
										'Flash Video'=>'get_embedding_markup_for_flash_video',
'Flash'=>'get_embedding_markup_for_flash',
										);
	/**
	 * Storage for parameters used by the object/embed tags
	 * This array should not be edited directly; instead use the set_parameter method
	 * The class definition contains parameter defaults in this array,
	 * but a controller can modify current params in runtime using the set_parameter method
	 * @var array format: $parameters[$player][$parameter] = $value
	 */	
	var $parameters = array(
							'qt'=>array(
										'autoplay'=>'true',
										'controller'=>'true',
										),
							'real'=>array(
										'autostart'=>'true',
										'controls'=>'ImageWindow,ControlPanel',
										'console'=>'one',
										'nojava'=>'true',
										),
							'wmv'=>array(
										'autostart'=>'true',
										'ShowControls'=>'true',
										'ShowStatusBar'=>'false',
										'ShowDisplay'=>'false',
										),
							'flv'=>array(
										'autostart'=>'true',
										),
							'swf'=>array(
										'wmode'=>'transparent',
										),
							);
	/**
	 * Storage for default dimensions object/embed tags when embedding audio
	 * This array should not be edited directly; instead use the set_audio_dimensions method
	 * The class definition contains defaults in this array,
	 * but a controller can modify dimensions in runtime using the set_audio_dimensions method
	 * @var array 'width'=>$width_in_pixels,'height'=>$height_in_pixels
	 */	
	var $audio_dimensions = array('width'=>'300','height'=>'0');
	var $default_video_dimensions = array('width'=>'360','height'=>'240');
	/**
	 * Video dimensions to override those on the entity
	 *
	 * Set via set_video_dimensions()
	 *
	 * @var array 'width'=>$width_in_pixels,'height'=>$height_in_pixels
	 */
	var $video_dimensions;
	
	/**
	 * The placard image to be used (only some media formats suport this)
	 *
	 * @var string URL
	 */
	var $placard_image_url;
	
	/**
	 * Image dimensions for the placard image
	 *
	 * Via set_placard_image_dimensions()
	 *
	 * @var array 'width'=>$width_in_pixels,'height'=>$height_in_pixels
	 */
	var $placard_image_dimensions;
	
	/**
	 * Maps the format specified in the entity's media_format field to a string used to display browser requirements and (where needed) tech credits
	* Do not access directly; use the get_tech_note() function
	 * @var array keys are the entity values in media_format; values are the strings returned
	 */	
	var $formats_to_tech_notes = array(
										'Quicktime'=>'Not seeing the file? <a href="http://www.apple.com/quicktime/download/">Get Quicktime player.</a>',
										'Real'=>'Not seeing the file? <a href="http://www.real.com/player/">Get Realplayer.</a>',
										'MP3'=>'Not seeing the file? <a href="http://www.macromedia.com/go/getflashplayer">Get Flash.</a> Flash video player by <a href="http://www.jeroenwijering.com/" title="Jeroen Wijering\'s website">Jeroen Wijering</a>',
										'Windows Media'=>'Not seeing the file? <a href="http://microsoft.com/windows/mediaplayer/en/download/">Get Windows Media Player.</a>',
										'Flash Video'=>'Not seeing the file? <a href="http://www.macromedia.com/go/getflashplayer">Get Flash.</a> Flash video player by <a href="http://www.jeroenwijering.com/" title="Jeroen Wijering\'s website">Jeroen Wijering</a>',
										'Flash'=>'Not seeing the file? <a href="http://www.macromedia.com/go/getflashplayer">Get Flash.</a>',
										);
	
	/**
	 * Sets a parameter for use when generating markup
	 *
	 * This can be used to help with selections on stuff like selecting relationship sites.
	 * @param string $player name of the player parameter applies to ('qt'=Quicktime,'real'=Real Player)
	 * @param string $param Name of the parameter being set
	 * @param string $value Value of the parameter being set
	 */
	function set_parameter( $player, $param, $value )
	{
		$this->parameters[$player][$param] = $value;
	}
	/**
	 * Clears a parameter
	 *
	 * @param string $player name of the player parameter applies to ('qt'=Quicktime,'real'=Real Player)
	 * @param string $param Name of the parameter being cleared
	 */
	function clear_parameter( $player, $param )
	{
		if(isset($this->parameters[$player][$param]))
		{
			unset($this->parameters[$player][$param]);
			return true;
		}
		return false;
	}
	/**
	 * Wraps up the various parameter modifications needed to disable automatic play in the various players
	 */
	function disable_automatic_play_start()
	{
		$this->set_parameter( 'qt', 'autoplay', 'false' );
		$this->set_parameter( 'real', 'autostart', 'false' );
		$this->set_parameter( 'wmv', 'autostart', 'false' );
		$this->set_parameter( 'flv', 'autostart', 'false' );
	}
	/**
	 * Wraps up the various parameter modifications needed to enable automatic play in the various players
	 */
	function enable_automatic_play_start()
	{
		$this->set_parameter( 'qt', 'autoplay', 'true' );
		$this->set_parameter( 'real', 'autostart', 'true' );
		$this->set_parameter( 'wmv', 'autostart', 'true' );
		$this->set_parameter( 'flv', 'autostart', 'true' );
	}
	/**
	 * Wraps up the various parameter modifications needed to disable the controller in the various players
	 *
	 * @todo add support for real player?
	 */
	function disable_controller()
	{
		$this->set_parameter( 'flv', 'controlbar', '0' );
		$this->set_parameter( 'qt', 'controller', 'false' );
		$this->set_parameter( 'wmv', 'ShowControls', 'false' );
	}
	/**
	 * Wraps up the various parameter modifications needed to enable the controller in the various players
	 *
	 * @todo add support for real player?
	 */
	function enable_controller()
	{
		$this->clear_parameter( 'flv', 'controlbar' );
		$this->set_parameter( 'qt', 'controller', 'true' );
		$this->set_parameter( 'wmv', 'ShowControls', 'true' );
	}
	/**
	 * Sets the dimensions to be used by the class when generating markup for a video item
	 *
	 * @param string $width Width of the video in pixels
	 * @param string $height Height of the video in pixels
	 */
	function set_video_dimensions($width, $height)
	{
		if(!is_array($this->video_dimensions))
			$this->video_dimensions = array();
		if(!empty($width))
		{
			$this->video_dimensions['width'] = $width;
		}
		if(!empty($height))
		{
			$this->video_dimensions['height'] = $height;
		}
	}
	/**
	 * Sets the dimensions to be used by the class when generating markup for an audio item
	 *
	 * @param string $width Width of the player widget in pixels
	 * @param string $height Height of the player widget in pixels
	 */
	function set_audio_dimensions($width, $height)
	{
		if(!empty($width))
		{
			$this->audio_dimensions['width'] = $width;
		}
		if(!empty($height))
		{
			$this->audio_dimensions['height'] = $height;
		}
	}
	
	/**
	 * Assign an image to be used as the placard image
	 * @param mixed $image An image URL, id, or entity
	 */
	function set_placard_image($image)
	{
		if(is_numeric($image) || is_object($image))
		{
			$this->placard_image_url = reason_get_image_url($image);
		}
		elseif(is_string($image))
		{
			$this->placard_image_url = $image;
		}
		else
		{
			trigger_error('Placard image must be an image id, url, or entity');
		}
	}
	
	/**
	 * Clear a preview image, if any
	 */
	function clear_placard_image()
	{
		$this->placard_image_url = null;
	}
	
	/**
	 * Set the dimensions of the placard image
	 * @param string $width Width of the placard image in pixels
	 * @param string $height Height of the placard image in pixels
	 */
	function set_placard_image_dimensions($width, $height)
	{
		if(!empty($width))
		{
			$this->placard_image_dimensions['width'] = $width;
		}
		if(!empty($height))
		{
			$this->placard_image_dimensions['height'] = $height;
		}
	}
	
	/**
	 * Creates appropriate markup for a given entity
	 * @param opject $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup($entity)
	{
		if(!is_object($entity) && is_numeric($entity)) // check if passed an id
		{
			$entity = new entity($entity);
		}
		if( $entity->get_value('url') && array_key_exists($entity->get_value('media_format'), $this->formats_to_functions) )
		{
			$function = $this->formats_to_functions[$entity->get_value('media_format')];
			return $this->$function($entity);
		}
		else
		{
			return '';
		}
	}
	/**
	 * Creates appropriate markup for the quicktime player
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 * @todo Find out if there is a way to do this without IE conditional comments
	 *       (they mean that this method cannot be inserted into any tidied content,
	 *        since Tidy kills (x)html comments.)
	 */
	function get_embedding_markup_for_quicktime($entity)
	{
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if($entity->get_value('default_media_delivery_method') == 'streaming' || strpos($entity->get_value('url'),'rtsp://') === 0)
		{
			if(defined('QUICKTIME_LINK_WEB_PATH'))
			{
				$src = QUICKTIME_LINK_WEB_PATH.'?id='.$entity->id();
			}
			else
			{
				trigger_error('"QUICKTIME_LINK_WEB_PATH" not defined -- please define it so that streaming quicktime media can work');
				$src = $entity->get_value('url');
			}
		}
		else
		{
			$src = $entity->get_value('url');
		}
		if($dimensions['width'] || $dimensions['height'] )
		{
			if($this->parameters['qt']['controller'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 15;
			}
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		
		$ret[] = '<object id="quicktimeWidget'.$entity->id().'IEOnly" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" '.$dimensions_attrs.'>';
		$ret[] = '<param name="src" value="'.$src.'" />';
		$ret[] = '<param name="autoplay" value="'.$this->parameters['qt']['autoplay'].'" />';
		$ret[] = '<param name="controller" value="'.$this->parameters['qt']['controller'].'" />';
		$ret[] = '<!--[if !IE]>-->';
		$ret[] = '<object id="quicktimeWidget'.$entity->id().'" type="video/quicktime" data="'.$src.'" '.$dimensions_attrs.'>';
		$ret[] = '<param name="autoplay" value="'.$this->parameters['qt']['autoplay'].'" />';
		$ret[] = '<param name="controller" value="'.$this->parameters['qt']['controller'].'" />';
		$ret[] = '</object>';
		$ret[] = '<!--<![endif]-->';
		$ret[] = '</object>';
		
		return implode("\n",$ret);
	}
	/**
	 * Creates appropriate markup for realplayer.
	 *
	 * Note that the markup this method produces is not yet xhtml-compliant.
	 *
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 * @todo figure out if there is an xhtml-compliant method for this (i.e. no embed tag).
	 */
	function get_embedding_markup_for_real($entity)
	{
		if(!$entity->get_value('url'))
			return '';
		
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if($dimensions['width'] || $dimensions['height'])
		{
			$dimensions['height'] = $dimensions['height'] + 20;
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
	
		$controls = $this->parameters['real']['controls'];
		// Slightly hairy mucking with parameter value  removes the ImageWindow control if the file is an audio file
		if($entity->get_value('av_type') == 'Audio')
		{
			$controls_array = explode(',',$controls);
			foreach($controls_array as $key=>$val)
			{
				if($val == 'ImageWindow')
				{
					unset($controls_array[$key]);
				}
			}
			$controls = implode(',',$controls_array);
		}
		
		$ret[] = '<object id="realWidget'.$entity->id().'" '.$dimensions_attrs.' classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA">';
		$ret[] = '<param name="src" value="'.$entity->get_value('url').'" />';
		$ret[] = '<param name="autostart" value="'.$this->parameters['real']['autostart'].'" />';
		$ret[] = '<param name="controls" value="'.$controls.'" />';
		$ret[] = '<param name="console" value="'.$this->parameters['real']['console'].'" />';
		$ret[] = '<embed src="'.$entity->get_value('url').'" '.$dimensions_attrs.' nojava="'.$this->parameters['real']['nojava'].'" autostart="'.$this->parameters['real']['autostart'].'" controls="'.$this->parameters['real']['controls'].'" pluginspage="http://www.real.com/player/"></embed>';
		$ret[] = '</object>';
		return implode("\n",$ret);
	}
	/**
	 * Creates appropriate markup for windows media
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object markup; empty if entity has no url
	 */
	function get_embedding_markup_for_windows_media($entity)
	{
		if(!$entity->get_value('url'))
			return '';
		
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if( $dimensions['height'] || $dimensions['width'] )
		{
			if($this->parameters['wmv']['ShowControls'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 44;
			}
			if($this->parameters['wmv']['ShowStatusBar'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 15;
			}
			if($this->parameters['wmv']['ShowDisplay'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 72;
			}
			if($this->parameters['wmv']['ShowStatusBar'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 22;
			}
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		$show_display = $this->parameters['wmv']['ShowDisplay'];
		// remove the display if it's audio
		if($entity->get_value('av_type') == 'Audio')
		{
			$show_display = 'false';
		}
		
		$ret[] = '<object id="windowsMediaWidget'.$entity->id().'" type="video/x-ms-wmv" data="'.$entity->get_value('url').'" '.$dimensions_attrs.'>';
		$ret[] = '<param name="src" value="'.$entity->get_value('url').'" />';
		$ret[] = '<param name="autostart" value="'.$this->parameters['wmv']['autostart'].'" />';
		$ret[] = '<param name="ShowControls" value="'.$this->parameters['wmv']['ShowControls'].'" />';
		$ret[] = '<param name="ShowDisplay" value="'.$show_display.'" />';
		$ret[] = '<param name="ShowStatusBar" value="'.$this->parameters['wmv']['ShowStatusBar'].'" />';
		$ret[] = '<param name="stretchToFit" value="true" />';
		$ret[] = '</object>';
		
		return implode("\n",$ret);
	}
	/**
	 * Creates appropriate markup for .flv files
	 * Generates markup expected by Jeroen Wijering's flash video player: http://www.jeroenwijering.com/?item=Flash_Video_Player
	 * Requires an instance of the flash video player SWF to be available at the
	 * URL specified in the constant REASON_FLASH_VIDEO_PLAYER_URI
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup_for_flash_video($entity)
	{
		if(!$entity->get_value('url'))
			return '';
			
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		$orig_dimensions = $dimensions;
		if(isset($this->parameters['flv']['controlbar']))
		{
			if(is_numeric($this->parameters['flv']['controlbar']))
				$dimensions['height'] = $dimensions['height'] + $this->parameters['flv']['controlbar'];
		}
		else
		{
			$dimensions['height'] = $dimensions['height'] + 20;
		}
		$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		
		$url = REASON_FLASH_VIDEO_PLAYER_URI.'?file='.$entity->get_value('url').'&amp;autostart='.$this->parameters['flv']['autostart'];
		if(isset($this->parameters['flv']['controlbar']))
			$url .= '&amp;controlbar='.htmlspecialchars($this->parameters['flv']['controlbar']);
		if(!empty($this->placard_image_url))
			$url .= '&amp;image='.htmlspecialchars($this->placard_image_url);
		$ret[] = '<object type="application/x-shockwave-flash" data="'.$url.'" '.$dimensions_attrs.' id="flashVideoWidget'.$entity->id().'">';
		$ret[] = '<param name="movie" value="'.$url.'" />';
		if($entity->get_value('av_type') != 'Audio')
		{
			$ret[] = '<param name="allowfullscreen" value="true" />';
		}
		$extension = $this->get_extension($entity->get_value('url'));
		if('flv' != $extension)
		{
			$link_text = 'Audio' == $entity->get_value('av_type') ? 'Listen' : 'Watch Video (.'.htmlspecialchars($extension).')';
			$link = '<a href="'.$entity->get_value('url').'">';
			if(!empty($this->placard_image_url))
			{
				$placard_width = !empty($this->placard_image_dimensions['width']) ? $this->placard_image_dimensions['width'] : $orig_dimensions['width'];
				$placard_height = !empty($this->placard_image_dimensions['height']) ? $this->placard_image_dimensions['height'] : $orig_dimensions['height'];
				$link .= '<img src="'.htmlspecialchars($this->placard_image_url).'" alt="'.$link_text.'" width="'.$placard_width.'" height="'.$placard_height.'" />';
			}
			else
			{
				$link .= $link_text;
			}
			$link .= '</a>';
			$ret [] = $link;
		}
		$ret [] = '</object>';
		
		return implode("\n",$ret);
	}
	/**
	 * Creates appropriate markup for .swf files
	 *
	 * Note that this does not yet produce standards-compliant markup.
	 * Flash Satay won't work reliably enough, as cross-domain restrictions
	 * prevent the playing of swf files hosted on arbitrary domains via the
	 * flash loader file.
	 *
	 * The solution here might work as at least a first step (e.g. XHTML
	 * compliance). It still has the drawback of having conditional comments,
	 * which will keep it from being embeddable in Reason content blocks.
	 *
	 * http://wiki.dreamhost.com/index.php/Object_Embedding
	 *
	 *
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 * @todo Figure out how to do this using standards-based method
	 */
	function get_embedding_markup_for_flash($entity)
	{
		if(!$entity->get_value('url'))
			return '';
		
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if( !empty($dimensions['height']) && !empty($dimensions['width']) )
		{
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		
		$ret[] = '<object id="flashWidget'.$entity->id().'" '.$dimensions_attrs.' classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" type="application/x-shockwave-flash" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">';
		$ret[] = '<param name="movie" value="'.$entity->get_value('url').'" />';
		if(!empty($this->parameters['swf']['wmode']))
			$ret[] = '<param name="wmode" value="'.$this->parameters['swf']['wmode'].'" />';
		$ret[] = '<embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="'.$entity->get_value('url').'" '.$dimensions_attrs.'></embed>';
		$ret[] = '</object>';
		return implode("\n",$ret);
	}
	
	/**
	 * Determines dimensions to use depending on whether file is audio or video
	 * @param object $entity Reason entity object of audio/video file type
	 * @return array $dimensions 'height'=>$height,'width'=>$width
	 */
	function get_dimensions($entity)
	{
		$ret = array();
		
		if($entity->get_value('av_type') == 'Audio')
		{
			$ret = $this->audio_dimensions;
		}
		else // Video, etc.
		{
			if(!empty($this->video_dimensions['height']))
				$ret['height'] = $this->video_dimensions['height'];
			elseif($entity->get_value('height'))
				$ret['height'] = $entity->get_value('height');
			else
				$ret['height'] = $this->default_video_dimensions['height'];
				
			if(!empty($this->video_dimensions['width']))
				$ret['width'] = $this->video_dimensions['width'];
			elseif($entity->get_value('width'))
				$ret['width'] = $entity->get_value('width');
			else
				$ret['width'] = $this->default_video_dimensions['width'];
		}
		return $ret;
	}
	/**
	 * get information about required software and/or technical credits (e.g. for creative-commons licensed flash players
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string tech notes; empty if none specified on object
	 */
	function get_tech_note($entity)
	{
		if(!is_object($entity) && is_numeric($entity)) // check if passed an id
		{
			$entity = new entity($entity);
		}
		if(array_key_exists($entity->get_value('media_format'), $this->formats_to_tech_notes) )
		{
			return $this->formats_to_tech_notes[$entity->get_value('media_format')];
		}
	}
	
	/**
	 * get the file extension
	 * @param string $filename a url or filename
	 * @return string $extension
	 */
	function get_extension($filename)
	{
		return trim(strtolower(str_replace('.','',strrchr($filename,'.'))));
	}
}

?>
