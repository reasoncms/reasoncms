<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'streamModule';

	class StreamModule extends DefaultMinisiteModule
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
                        echo '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"';
                        echo 'width="699" height="598"';
                        echo 'codebase="http://www.apple.com/qtactivex/qtplugin.cab">';
                        echo '<param name="SRC" value = "/reason/images/270672.jpg" />';
                        echo '<param name="QTSRC" value = "/reason/images/270672.jpg" />';
                        echo '<param name="HREF" value = "rtsp://video-3.luther.edu/worship.sdp" />';
                        echo '<param name="AUTOPLAY" value = "true" />';
                        echo '<param name="CONTROLLER" value = "false" />';
                        echo '<param name="TYPE" value = "video/quicktime" />';
                        echo '<param name="TARGET" value = "myself" />';
                        echo '<embed src = "/reason/images/352571.png" qtsrc = "/reason/images/352571.png" href =';
//                        echo '<embed href =';
                        echo '"rtsp://video-3.luther.edu/worship.sdp" target ="myself" controller =';
                        echo '"false" width = "699" height = "598" loop = "False" autoplay = "true"';
                        echo 'plugin = "quicktimeplugin" type ="video/quicktime" cache = "false"';
                        echo 'pluginspage= "http://www.apple.com/quicktime/download/" >';
                        echo '</embed>';
                        echo '</object>';
		}
	}
?>
