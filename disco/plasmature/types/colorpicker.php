<?php

/**
 * Color Picker type library.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * Displays a color picker interface using the farbtastic tool
 * @package disco
 * @subpackage plasmature
 */
class colorpickerType extends defaultType
{
	var $type = 'colorpicker';
	var $colorpicker_id;
	var $default_color="FFFFFF";

	function grab()
	{
		$HTTP_VARS = $this->get_request();
		if ( isset( $HTTP_VARS[ $this->name ] ) )
		{
			$color=trim($HTTP_VARS[ $this->name ],"#");
				$this->set($color);
		}
		else
		{
			$this->set( 'FFFFFF' );
		}
	}
	
	function get_val()
	{
		$pattern="/[^0-9A-Fa-f]/";
		$matches=array();
		
		$val=$this->get();
		$num_digits=strlen($val);
		if(empty($val))
		{
			$val=$this->default_color;
		}
		elseif( !empty($val) && preg_match($pattern,$val,$matches)>0)
		{
			trigger_error($val.' is non hex in '.$this->name.' Only A through F or 0 through 9 allowed.');
		}
		elseif( $num_digits!=6)
		{
			if($num_digits!=3)
			{
				trigger_error($val. ' is incorrect length in '.$this->name.' Must be 3 or 6 hex digits long.');
			}
		}
		$val="#".$val;
		return $val;
	}
	
	function get_display()
	{
		$color_wheel_src=REASON_PACKAGE_HTTP_BASE_PATH."silk_icons/color_wheel.png";
		$red_x_src=REASON_PACKAGE_HTTP_BASE_PATH."silk_icons/cross.png";
		$val=$this->get_val();
		
		
		$str ="<script type=\"text/javascript\" src=\"".REASON_PACKAGE_HTTP_BASE_PATH."colorpicker/colorpicker.js\"></script>\n";
		$str.="<link rel=\"stylesheet\" type=\"text/css\" href=\"".REASON_PACKAGE_HTTP_BASE_PATH."colorpicker/colorpicker.css\" />\n";

		$str.="<span  id=\"swatch-".$this->name."\" name=\"".$this->name."\" style=\"background-color:".$val.";\" >
		&nbsp;&nbsp;&nbsp;&nbsp;</span>\n";


		$str.="<input type=\"text\" id=\"".$this->name."\" name=\"".$this->name."\" value=\"".$val."\"  />\n";


		$str.="<span class=\"".$this->name." colorpicker_control\" > <img alt=\"Open Color Picker\" name=\"color_wheel\" src=\"".$color_wheel_src."\" /></span>\n";
		
		$str.= '<div class="'.$this->name.' colorpicker_wrap">'."\n";
		$str.="<div class=\"".$this->name." colorpicker_close\"> \n";		
		$str.="<img alt=\"Close Color Picker\" name=\"close_red_x\" src=\"".$red_x_src."\" />\n";
		$str.="</div>";

		$str.="<div id=\"colorpicker-".$this->name."\" ></div>\n";
		$str.= '</div>'."\n";
		
		//Place Massive Amounts of Javascript here
		$str.="<script type=\"text/javascript\">\n";
		$str.="  $(document).ready(function() {
			
			$('.".$this->name.".colorpicker_wrap').hide();
			
			$('.".$this->name.".colorpicker_control').click(function(){
				
				$(this).hide();
				$('.".$this->name.".colorpicker_wrap').show();
				$('#colorpicker-".$this->name."').farbtastic(change_color)
				$('#colorpicker-".$this->name."').css('display','block');
				
			});


			$('.".$this->name.".colorpicker_close').click(function(){
				
				$('.".$this->name.".colorpicker_control').show();
				$('.".$this->name.".colorpicker_wrap').hide();
				$('#colorpicker-".$this->name."').unbind('#".$this->name."');
				$('#colorpicker-".$this->name."').css('display','none');
			
			});
		    
			function change_color(color)
			{
				textbox=$('#".$this->name."');
				textbox.val(color);
				swatch=$('#swatch-".$this->name."');
				swatch.css('background-color',color)
			}
		  });
		</script>\n";

		
		return $str;
	}	
	
	function get_label_target_id()
	{
		return $this->name;
	}
}
?>