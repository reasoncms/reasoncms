<?php
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherImageSidebarModule';

	class LutherImageSidebarModule extends ImageSidebarModule
	{
		var $es;
		var $images;

		var $acceptable_params = array(
		'num_to_display' => '',
		'caption_flag' => false,
		'rand_flag' => true,
		'order_by' => '' );

		function init( $args = array() )
		{
			parent::init( $args );
			$head_items =& $this->parent->head_items;
		}
		
		function has_content()
		{
			if( $this->images )
				return true;
			else
				return false;
		}
		
		function run()
		{
			
			if ( !empty($this->parent->textonly) )
				echo '<h3>Quote</h3>'."\n";
			
			foreach( $this->images AS $id => $image )
			{
				if (!preg_match("/imagetop|bannerad|video|map/", $image->get_value('keywords')))
				{
					echo '<aside id="attribute">'."\n";
					$url = WEB_PHOTOSTOCK . $id . '.' . $image->get_value('image_type');
					echo '<img src="' . $url . '">'."\n";
					echo '</aside>'."\n";
					return;
				}
			}
		}
		

	}
?>
