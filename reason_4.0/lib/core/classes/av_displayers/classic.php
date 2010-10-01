<?php
/**
 * Classic (non-standards) Reason AV Display Class
 * @package reason
 * @subpackage classes
 */

/**
 * include the URL utils and parent class
 */
include_once('reason_header.php');
reason_include_once( 'function_libraries/url_utils.php' );
reason_include_once( 'classes/av_displayers/xhtml_strict.php' );

/**
 * Register the class
 */
$GLOBALS['reason_av_displayers'][ basename( __FILE__, '.php') ] = 'classicReasonAVDisplay';

/** 
 * Classic Reason AV Display Class
 *
 * Generates classic-style (e.g. non-standards-based, using <embed>, etc.) html markup
 * to embed media in a web page
 *
 * @author Matt Ryan
 */

class classicReasonAVDisplay extends xhtmlStrictReasonAVDisplay
{
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
		if($dimensions['width'] || $dimensions['height'] )
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
	 * @return string object/embed markup; empty if entity has no url
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
		if(!$entity->get_value('url'))
			return '';
		
		$ret = array();
		$dimensions_attrs = '';
		$dimensions = $this->get_dimensions($entity);
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
			$url .= '&amp;controlbar='.$this->parameters['flv']['controlbar'];
		
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
		$ret[] = '<param name="wmode" value="'.$this->parameters['flv']['wmode'].'" />';
		$ret[] = '<embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="'.$entity->get_value('url').'" '.$dimensions_attrs.'></embed>';
		$ret[] = '</object>';
		return implode("\n",$ret);
	}
}

?>
