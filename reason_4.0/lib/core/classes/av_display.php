<?php

reason_include_once( 'function_libraries/url_utils.php' );

/** Reason AV Display Class
 * Generates the html markup used to embed media in a web page
 *
 * Usage Example:
 * <code>
 * $avd = new reasonAVDisplay();
 * $avd->disable_automatic_play_start();
 * $avd->set_parameter('qt','controller','false');
 * $embed_markup = $avd->get_embedding_markup($entity);
 * if(!empty($embed_markup))
 * {
 * 		echo $embed_markup;
 
 		$tech_notes = $avd->get_tech_notes($entity);
 		if(!empty($tech_notes))
 		{
 			echo '<div class="techNotes">'.$tech_notes.'</div>';
 		}
 * }
 * </code>
 * 
 * Testing indicates that these embed methods are not reliable when more than one exists on a page.
 * So it is likely best to organize interfaces, etc. to keep multiple players from conflicting.
 *
 * To do: figure out best method of handling streamable entities
 *
 * @author Matt Ryan
 */

class reasonAVDisplay
{
	/**
	 * Maps the format specified in the entity's media_format field to a method
	 * @var array keys are the entity values in media_format; values are the class method used to generate the markup for that format
	 */	
	var $formats_to_functions = array(
										'Quicktime'=>'get_embedding_markup_for_quicktime',
										'Real'=>'get_embedding_markup_for_real',
										'MP3'=>'get_embedding_markup_for_quicktime',
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
										'ShowStatusBar'=>'true',
										'ShowDisplay'=>'true',
										),
							'flv'=>array(
										'wmode'=>'transparent',
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
	var $audio_dimensions = array('width'=>'300','height'=>'20');
	
	/**
	 * Maps the format specified in the entity's media_format field to a string used to display browser requirements and (where needed) tech credits
	* Do not access directly; use the get_tech_note() function
	 * @var array keys are the entity values in media_format; values are the strings returned
	 */	
	var $formats_to_tech_notes = array(
										'Quicktime'=>'Not seeing the file? <a href="http://www.apple.com/quicktime/download/">Get Quicktime player.</a>',
										'Real'=>'Not seeing the file? <a href="http://www.real.com/player/">Get Realplayer.</a>',
										'MP3'=>'Not seeing the file? <a href="http://www.apple.com/quicktime/download/">Get Quicktime player.</a>',
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
		if(!empty($dimensions['height']) && !empty($dimensions['width']) )
		{
			$has_dimensions = true;
			if($this->parameters['qt']['controller'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 15;
			}
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		$ret[] = '<object id="quicktimeWidget'.$entity->id().'" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" '.$dimensions_attrs.'>';
		$ret[] = '<param name="src" value="'.$src.'" />';
		$ret[] = '<param name="autoplay" value="'.$this->parameters['qt']['autoplay'].'" />';
		$ret[] = '<param name="controller" value="'.$this->parameters['qt']['controller'].'" />';
		$ret[] = '<embed src="'.$src.'" '.$dimensions_attrs.' autoplay="'.$this->parameters['qt']['autoplay'].'" controller="'.$this->parameters['qt']['controller'].'" pluginspage="http://www.apple.com/quicktime/download/"></embed>';
		$ret[] = '</object>';
		return implode("\n",$ret);
	}
	/**
	 * Creates appropriate markup for realplayer
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup_for_real($entity)
	{
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if( !empty($dimensions['height']) && !empty($dimensions['width']) )
		{
			$has_dimensions = true;
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
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup_for_windows_media($entity)
	{
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if( !empty($dimensions['height']) && !empty($dimensions['width']) )
		{
			if($this->parameters['wmv']['ShowControls'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 44;
			}
			if($this->parameters['wmv']['ShowStatusBar'] == 'true')
			{
				$dimensions['height'] = $dimensions['height'] + 15;
			}
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		$show_display = $this->parameters['wmv']['ShowDisplay'];
		// remove the display if it's audio
		if($entity->get_value('av_type') == 'Audio')
		{
			$show_display = 'false';
		}
		
		$ret[] = '<object id="windowsMediaWidget'.$entity->id().'" '.$dimensions_attrs.' classid="clsid:22D6F312-B0F6-11D0-94AB-0080C74C7E95" type="application/x-oleobject" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701">';
		$ret[] = '<param name="FileName" value="'.$entity->get_value('url').'" />';
		$ret[] = '<param name="autostart" value="'.$this->parameters['wmv']['autostart'].'" />';
		$ret[] = '<param name="ShowControls" value="'.$this->parameters['wmv']['ShowControls'].'" />';
		$ret[] = '<param name="ShowDisplay" value="'.$show_display.'" />';
		$ret[] = '<param name="ShowStatusBar" value="'.$this->parameters['wmv']['ShowStatusBar'].'" />';
		$ret[] = '<embed type="application/x-mplayer2" pluginspage="http://microsoft.com/windows/mediaplayer/en/download/" src="'.$entity->get_value('url').'" '.$dimensions_attrs.' autostart="'.$this->parameters['wmv']['autostart'].'" showcontrols="'.$this->parameters['wmv']['ShowControls'].'" showdisplay="'.$show_display.'" showstatusbar="'.$this->parameters['wmv']['ShowStatusBar'].'"></embed>';
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
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if( !empty($dimensions['height']) && !empty($dimensions['width']) )
		{
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		
		$url = REASON_FLASH_VIDEO_PLAYER_URI.'?file='.$entity->get_value('url').'&amp;autostart='.$this->parameters['flv']['autostart'];
		
		$ret[] = '<object id="flashVideoWidget'.$entity->id().'" '.$dimensions_attrs.' classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" type="application/x-shockwave-flash" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">';
		$ret[] = '<param name="movie" value="'.$url.'" />';
		$ret[] = '<param name="wmode" value="'.$this->parameters['flv']['wmode'].'" />';
		$ret[] = '<embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="'.$url.'" '.$dimensions_attrs.'></embed>';
		$ret[] = '</object>';
		return implode("\n",$ret);
	}
	/**
	 * Creates appropriate markup for .swf files
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup_for_flash($entity)
	{
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
		if( !empty($dimensions['height']) && !empty($dimensions['width']) )
		{
			$dimensions_attrs = 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'"';
		}
		
		$ret[] = '<object id="flashWidget'.$entity->id().'" '.$dimensions_attrs.' classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" type="application/x-shockwave-flash" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">';
		$ret[] = '<param name="movie" value="'.$entity->get_value('url').'" />';
		$ret[] = '<param name="wmode" value="'.$this->parameters['flv']['wmode'].'" />';
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
		if($entity->get_value('av_type') == 'Audio')
		{
			$ret = $this->audio_dimensions;
		}
		else // Video
		{
			$ret = array( 'height'=>$entity->get_value('height'),'width'=>$entity->get_value('width') );
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
