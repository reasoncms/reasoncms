<?php
/**
 * Example theme customizer
 *
 * @package reason
 * @subpackage theme_customizers
 */

/**
 * Include interface and other dependencies
 */
reason_include_once('theme_customizers/interface.php');

/**
 * Register the customizer
 */
$GLOBALS[ 'reason_theme_customizers' ][ basename( __FILE__, '.php' ) ] = 'cloakThemeCustomizer';

/**
 * Example theme customizer
 * 
 * This class can be used as a simple example of a theme customizer. It sports a simple interface
 * for choosing a few colors and a font family, and produces the appropriate CSS to apply those
 * customizations.
 *
 * @author Matt Ryan
 */
class cloakThemeCustomizer implements reasonThemeCustomizerInterface
{
	/**
	 * A place to store the customization data
	 */
	protected $data;
	
	/**
	 * Set the customization data current stored on a site
	 *
	 * @param object $data Unserialized json data
	 * @return void
	 */
	public function set_customization_data($data)
	{
		$this->data = $data;
	}
	
	/**
	 * Get data in the form to be stored
	 *
	 * If given a submitted disco form, this method transforms the form into the appropriate form.
	 *
	 * Otherwise, this method returns the data set on the object (if available)
	 *
	 * @param object $disco
	 * @return mixed stdClass object Data if available, NULL if not
	 */
	public function get_customizaton_data($disco = false)
	{
		if($disco)
		{
			$obj = new stdClass;
			// $obj->background_color = $disco->get_value('background_color');
			// $obj->text_color = $disco->get_value('text_color');
			// $obj->banner_font = $disco->get_value('banner_font');
			$obj->banner_image_id = $disco->get_value('banner_image_id');
			return $obj;
		}
		else
			return $this->data;
	}
	
	/**
	 * Set up the theme customization form
	 *
	 * @param object $disco
	 * @return void
	 */
	public function modify_form($disco)
	{
		$data = $this->get_customizaton_data();
		
		// $disco->add_element('background_color','colorpicker');
		// if(isset($data->background_color))
		// 	$disco->set_value('background_color',$data->background_color);
			
		// $disco->add_element('text_color','colorpicker');
		// if(isset($data->text_color))
		// 	$disco->set_value('text_color',$data->text_color);
			
		// $disco->add_element('banner_font','select_no_sort',array('options'=>array('Verdana'=>'Verdana','Georgia'=>'Georgia','Comic Sans MS'=>'Comic Sans','Lucida Handwriting'=>'Lucida Handwriting',),'add_empty_value_to_top' => true,));
		// if(isset($data->banner_font))
		// 	$disco->set_value('banner_font',$data->banner_font);
			
		$disco->add_element('banner_image_id','text');
		if(isset($data->banner_image_id))
			$disco->set_value('banner_image_id',$data->banner_image_id);
	}
	
	/**
	 * Modify the head items of a Reason page
	 *
	 * This is the primary (and simplest) way for a theme customizer to affect the look of a site.
	 *
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items)
	{
		$data = $this->get_customizaton_data();
		if(!empty($data))
		{
			$css = '';
			if(!empty($data->background_color))
				$css .= 'body{background-color:#'.$data->background_color.';}';
			if(!empty($data->text_color))
				$css .= 'body{color:#'.$data->text_color.';}';
			if(!empty($data->banner_font))
				$css .= '#banner,.banner{font-family:"'.$data->banner_font.'";}';
			if(!empty($data->banner_image_id))
			{
				$image = new entity($data->banner_image_id);
				if($image->get_values() && $image->get_value('type') == id_of('image'))
				{
					$rsi = new reasonSizedImage();
					$rsi->set_id($data->banner_image_id);
					$rsi->set_width(1680);
					$rsi->set_height(205);
					$css .= '#banner,.banner{background-image:url("'.$rsi->get_url().'");}';
				}
				else
				{
					trigger_error('Banner image ID in theme customizer not valid');
				}
			}
			
			$head_items->add_head_item('style',array('type'=>'text/css'),$css);
		}
	}
	
	/**
	 * Can a given user modify the theme?
	 *
	 * Theme customizers can specify which users are allowed to modify the theme. This allows the
	 * administrative interface to only offer modification links to those 
	 *
	 * Note that users with the privilege customize_all_themes bypass this check.
	 *
	 * Note also that users without access to the site are blocked from theme customization by
	 * default.
	 *
	 * Therefore, if this method simply returns false, only administrators will be able to customize the theme. If it simply returns true, administrators and site users will be able to customize the
	 * theme. Of course, more sophisticated logic could be implemented allowing only certain users
	 * or roles to customize the theme.
	 *
	 * @param integer $user_id
	 * @return boolean
	 */
	public function user_can_customize($user_id)
	{
		return true;
	}
}
?>