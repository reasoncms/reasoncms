<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileDirectionsModule';

class MobileDirectionsModule extends DefaultMinisiteModule {
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
        echo '<script language="Javascript">'."\n";
        echo 'if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(function(position) {
  doStuff(position.coords.latitude, position.coords.longitude);
  });
}
else {
  if (document.getElementById(\"GeoAPI\")) {
    document.getElementById(\"GeoAPI\").innerHTML = \"I\'m sorry but geolocation services are not supported by your browser";
    document.getElementById("GeoAPI").style.color = "#FF0000";
  }
}

function doStuff(mylat, mylong) {
  if (document.getElementById("GeoAPI")) {
    document.getElementById("GeoAPI").innerHTML = "<iframe style=\"width: 320px; height: 400px\" frameborder=\"0\" scrolling=\"no\" marginheight=\"0\" marginwidth=\"0\" src=\"http://maps.google.com/maps?f=d&source=s_d&saddr=" + mylat + "," + mylong + "&daddr=43.315921,-91.802895&output=embed\"></iframe>";
  }
}';

        echo '</script>';

    }
}
