<?php
/**
 * Reason AV Display Class
 * @package reason
 * @subpackage classes
 */
 
/**
 * Define the default displayer, if it has not already been done
 */
if(!defined('REASON_DEFAULT_AV_DISPLAYER'))
	define('REASON_DEFAULT_AV_DISPLAYER','xhtml_strict');

/**
 * include the URL utils
 */
reason_include_once( 'function_libraries/url_utils.php' );

/** 
 * Reason AV Display Class
 * Generates the html markup used to embed media in a web page
 *
 * This class is actually a facade/wrapper for the actual class the generates the markup.
 * It will use the AV display file specified in the constructor; if there is no
 * string provided in the constructor, it will fall back to whatever is defined in
 * REASON_DEFAULT_AV_DISPLAYER.
 *
 * Usage Example:
 * <code>
 * $avd = new reasonAVDisplay();
 * $avd->disable_automatic_play_start();
 * $avd->set_parameter('qt','controller','false');
 * if($embed_markup = $avd->get_embedding_markup($entity))
 * {
 * 		echo $embed_markup;
 *		if($tech_notes = $avd->get_tech_note($entity))
 *		{
 *			echo '<div class="techNotes">'.$tech_notes.'</div>';
 *		}
 * }
 * </code>
 *
 * If you want to specify a displayer, the first line will look like this:
 * <code>
 * $avd = new reasonAVDisplay('displayer_filename');
 * </code>
 *
 * Note that the string given is a filename (without .php) in classes/av_displayers/.
 * 
 * Testing indicates that non-flash-based embed methods are not reliable when more than one exists on a page.
 * So it is likely best to organize interfaces, etc. to keep multiple players from conflicting.
 *
 * @author Matt Ryan
 */

class reasonAVDisplay
{
	var $_displayer;
	function reasonAVDIsplay($displayer = REASON_DEFAULT_AV_DISPLAYER)
	{
		$override = false;
		reason_include_once('classes/av_displayers/'.$displayer.'.php');
		if(empty($GLOBALS['reason_av_displayers'][$displayer]))
		{
			if(REASON_DEFAULT_AV_DISPLAYER == $displayer)
			{
				trigger_error('Default AV displayer ('.$displayer.') did not register itself; giving up.',HIGH);
				return;
			}
			trigger_error($displayer.' AV displayer did not register itself in the $GLOBALS array. Using default displayer instead ('.REASON_DEFAULT_AV_DISPLAYER.').');
			$this->reasonAVDIsplay();
			$override = true;
		}
		elseif(!class_exists($GLOBALS['reason_av_displayers'][$displayer]))
		{
			if(REASON_DEFAULT_AV_DISPLAYER == $displayer)
			{
				trigger_error('Default AV displayer class ('.$GLOBALS['reason_av_displayers'][$displayer].') does not exist; giving up.',HIGH);
				return;
			}
			trigger_error('Class defined for displayer ('.$GLOBALS['reason_av_displayers'][$displayer].') does not exist. Using default displayer ('.REASON_DEFAULT_AV_DISPLAYER.').');
			$override = true;
		}
		if($override)
		{
			$this->reasonAVDIsplay();
			return;
		}
		$this->_displayer = new $GLOBALS['reason_av_displayers'][$displayer]();
	}
	
	function _displayer_is_set_up()
	{
		return !empty($this->_displayer);
	}
	
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
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->set_parameter( $player, $param, $value );
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
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->disable_automatic_play_start();
	}
	/**
	 * Wraps up the various parameter modifications needed to enable automatic play in the various players
	 */
	function enable_automatic_play_start()
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->enable_automatic_play_start();
	}
	/**
	 * Wraps up the various parameter modifications needed to disable the controller in the various players
	 */
	function disable_controller()
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->disable_controller();
	}
	/**
	 * Wraps up the various parameter modifications needed to enable the controller in the various players
	 */
	function enable_controller()
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->enable_controller();
	}
	/**
	 * Sets the dimensions to be used by the class when generating markup for an video item
	 *
	 * @param string $width Width of the video in pixels
	 * @param string $height Height of the video in pixels
	 */
	function set_video_dimensions($width, $height)
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->set_video_dimensions($width, $height);
	}
	/**
	 * Sets the dimensions to be used by the class when generating markup for an audio item
	 *
	 * @param string $width Width of the player widget in pixels
	 * @param string $height Height of the player widget in pixels
	 */
	function set_audio_dimensions($width, $height)
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->set_audio_dimensions($width, $height);
	}
	/**
	 * Assign an image to be used as the placard image
	 * @param mixed $image An image URL, id, or entity
	 */
	function set_placard_image($entity)
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->set_placard_image($entity);
	}
	
	/**
	 * Set the dimensions of the placard image
	 * @param string $width Width of the placard image in pixels
	 * @param string $height Height of the placard image in pixels
	 */
	function set_placard_image_dimensions($width, $height)
	{
		if(!$this->_displayer_is_set_up()) return;
		
		if(!method_exists($this->_displayer,'set_placard_image_dimensions'))
			return false;
		
		return $this->_displayer->set_placard_image_dimensions($width, $height);
	}
	
	/**
	 * Clear a preview image, if any
	 */
	function clear_placard_image()
	{
		if(!$this->_displayer_is_set_up()) return;
		return $this->_displayer->clear_placard_image();
	}
	
	/**
	 * Creates appropriate markup for a given entity
	 * @param opject $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup($entity)
	{
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_embedding_markup($entity);
	}
	/**
	 * Creates appropriate markup for the quicktime player
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup_for_quicktime($entity)
	{
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_embedding_markup_for_quicktime($entity);
	}
	/**
	 * Creates appropriate markup for realplayer
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 * @todo figure out if there is an xhtml-compliant method for this (i.e. no embed tag).
	 */
	function get_embedding_markup_for_real($entity)
	{
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_embedding_markup_for_real($entity);
	}
	/**
	 * Creates appropriate markup for windows media
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object markup; empty if entity has no url
	 */
	function get_embedding_markup_for_windows_media($entity)
	{
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_embedding_markup_for_windows_media($entity);
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
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_embedding_markup_for_flash_video($entity);
	}
	/**
	 * Creates appropriate markup for .swf files
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string object/embed markup; empty if entity has no url
	 */
	function get_embedding_markup_for_flash($entity)
	{
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_embedding_markup_for_flash($entity);
	}
	
	/**
	 * Determines dimensions to use depending on whether file is audio or video
	 * @param object $entity Reason entity object of audio/video file type
	 * @return array $dimensions 'height'=>$height,'width'=>$width
	 */
	function get_dimensions($entity)
	{
		if(!$this->_displayer_is_set_up()) return array('height'=>0,'width'=>0);
		return $this->_displayer->get_dimensions($entity);
	}
	/**
	 * get information about required software and/or technical credits (e.g. for creative-commons licensed flash players
	 * @param object $entity Reason entity object of audio/video file type
	 * @return string tech notes; empty if none specified on object
	 */
	function get_tech_note($entity)
	{
		if(!$this->_displayer_is_set_up()) return '';
		return $this->_displayer->get_tech_note($entity);
	}
}

?>
