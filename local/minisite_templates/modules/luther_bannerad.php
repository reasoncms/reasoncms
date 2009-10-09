<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherBanneradModule';
	
	class LutherBanneradModule extends ImageSidebarModule
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => true,
		'rand_flag' => false,
		'order_by' => '' );

		function init( $args = array() )
		{
			parent::init( $args );
			$head_items =& $this->parent->head_items;
		}
		
		function run()
		{
			$die = isset( $this->die_without_thumbmail ) ? $this->die_without_thumbnail : false;
			$popup = isset( $this->show_popup_link ) ? $this->show_popup_link : true;
			$desc = isset( $this->description ) ? $this->description : true;
			$text = isset( $this->additional_text ) ? $this->additional_text : "";
			
			if ( !empty($this->parent->textonly) )
				echo '<p>Banner ads</p>'."\n";
			
			echo '<div id="bannerleft">'."\n";
			foreach( $this->images AS $id => $image )
			{
				if (preg_match("/bannerad/", $image->get_value('keywords')))
				{
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					echo '<a href="' . $image->get_value('content') . '"><img src="' . $url . '" alt="' . $image->get_value('description') . '" "width=100%"/></a>';
					//break;
				}
			}
			echo "</div>\n";
		}
	}
?>
