<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DirectionsModule';

class DirectionsModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
        /*	echo '<div class="googleMapsForm">'."\n";
	echo '<form action="http://maps.google.com/maps" method="get"	target="_blank">';
	echo '<div id="address">'.'Starting address:'.'</div>'."\n";
	echo '<div id="input">'.'<input type="text" name="saddr" />'.'</div>'."\n";
	echo '<input type="hidden" name="daddr" value="700 College Dr. Decorah, IA 52101-1041 (Luther College)" />';
	//	43.315921;-91.802895
	echo '<div id="getDirections">'.'<input type="submit" value="Get directions" />'.'</div>'."\n";
	echo '</form>';
	echo '</div>'; */

        echo '<div id="GeoAPI"></div>';
        echo '<script type="text/javascript" src="/javascripts/mobile_directions.js"></script>';

    }
}
?>