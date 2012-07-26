<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherGmapModule';

	class LutherGmapModule extends DefaultMinisiteModule
	{
		function init( $args = array() )
		{

		}
		function has_content()
		{
			return true;
		}
		function run()
		{
                    echo '<script type="text/javascript" src="/reason/js/google_map.js"></script>';
                    echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=true&amp;key=ABQIAAAAz1k9MN50wbdsqa0Bg4eWaRSv_q0vVUSVmodOyRFLMdbe4MqtZxTMvBlgd_vgIFY6jEzQ7cbRv9ER6g" type="text/javascript"></script>';
                    echo '<div id="map_canvas" style="height: 400px; width: 450px;"></div>';
                   
                    //embed
                    //echo '<iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/?ie=UTF8&amp;ll=37.0625,-95.677068&amp;spn=42.089199,79.013672&amp;z=4&amp;output=embed"></iframe><br />';          
		}
	}
?>