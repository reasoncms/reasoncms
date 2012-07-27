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
        echo '<head>';
        echo '<meta http-equiv="content-type" content="text/html; charset=utf-8"/>';
        echo '<meta name = "viewport" content = "width = device-width, height = device-height" />';
        echo '<title>Directions to Luther College</title>';
        echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=ABQIAAAA7xtXgBQ5h5ZMJwpZo5P3IxReGjaPQmjUbZCiiQGpR0ykuFwG0RRv9w8afWTI_k0B8Dgc7TVg2bQ7og"></script>';
        //echo '<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=ABQIAAAA19OJ4bGvkrXOC6rnLil7NBSAyM1u6fbi_hQ4-2bmFQwsz5cTxBStrHHccc-7QU5bAKBVhU5mqEw4Sg"></script>';
        echo '<style type="text/css">';
        echo 'body {';
        echo 'font-family: Verdana, Arial, sans serif;';
       // echo 'font-size: 11px;';
        //echo 'margin: 2px;';
        echo '}';
        echo 'table.directions th {';
        echo 'background-color:#EEEEEE;';
        echo '}';

        echo 'img {';
        echo '  color: #000000;';
        echo '}';
        echo '</style>';
        echo '<script type="text/javascript">';

        echo 'var map;';
        echo 'var gdir;';
        echo 'var geocoder = null;';
        echo 'var addressMarker;';

        echo 'function initialize() {';
        echo '  if (GBrowserIsCompatible()) {';
        echo '    map = new GMap2(document.getElementById("map_canvas"));';
        echo '    gdir = new GDirections(map, document.getElementById("directions"));';
        echo '    GEvent.addListener(gdir, "load", onGDirectionsLoad);';
        echo '    GEvent.addListener(gdir, "error", handleErrors);';

        echo '    map.setCenter(new GLatLng(43.312454,-91.805019), 14);';
        echo '    map.disableDragging();';
        echo '    map.addControl(new GSmallMapControl());';
        //setDirections("Iowa City, IA", "Luther College", "en_US");
        echo '  }';
        echo '}';

        echo 'function setDirections(fromAddress, toAddress, locale) {';
        echo '  gdir.load("from: " + fromAddress + " to: " + toAddress,';
        echo '            { "locale": locale });';
        echo '}';

        echo 'function handleErrors(){';
        echo ' if (gdir.getStatus().code == G_GEO_UNKNOWN_ADDRESS)';
        echo '   alert("No corresponding geographic location could be found for one of the specified addresses. This may be due to the fact that the address is relatively new, or it may be incorrect.\nError code: " + gdir.getStatus().code);';
        echo ' else if (gdir.getStatus().code == G_GEO_SERVER_ERROR)';
        echo '   alert("A geocoding or directions request could not be successfully processed, yet the exact reason for the failure is not known.\n Error code: " + gdir.getStatus().code);';

        echo ' else if (gdir.getStatus().code == G_GEO_MISSING_QUERY)';
        echo '   alert("The HTTP q parameter was either missing or had no value. For geocoder requests, this means that an empty address was specified as input. For directions requests, this means that no query was specified in the input.\n Error code: " + gdir.getStatus().code);';

        //   else if (gdir.getStatus().code == G_UNAVAILABLE_ADDRESS)  <--- Doc bug... this is either not defined, or Doc is wrong
        //     alert("The geocode for the given address or the route for the given directions query cannot be returned due to legal or contractual reasons.\n Error code: " + gdir.getStatus().code);

        echo ' else if (gdir.getStatus().code == G_GEO_BAD_KEY)';
        echo '   alert("The given key is either invalid or does not match the domain for which it was given. \n Error code: " + gdir.getStatus().code);';

        echo 'else if (gdir.getStatus().code == G_GEO_BAD_REQUEST)';
        echo '  alert("A directions request could not be successfully parsed.\n Error code: " + gdir.getStatus().code);';

        echo 'else alert("An unknown error occurred.");';
        echo '}';

        echo 'function onGDirectionsLoad(){';
        // Use this function to access information about the latest load()
        // results.

        // e.g.
        // document.getElementById("getStatus").innerHTML = gdir.getStatus().code;
        // and yada yada yada...
        echo '}';
        echo 'function geolocate(){';
        echo '   if (navigator.geolocation) {';
        echo '          navigator.geolocation.getCurrentPosition(function(position) {';
        echo '           setDirections((position.coords.latitude+","+position.coords.longitude), "Luther College Dahl Centennial Unionâ€Ž","en_US");';
        echo '           });';
        echo '       }';
        echo '       else {';
        echo 'alert("I am sorry, but geolocation services are not supported by your browser");';
        //if (document.getElementById("GeoAPI")) {
        //document.getElementById("GeoAPI").innerHTML = "I'm sorry but geolocation services are not supported by your browser";
        //document.getElementById("GeoAPI").style.color = "#FF0000";
        //}
        echo '      }';
        echo '  }';

        echo '  </script>';
        echo ' </head>';
        echo '<body onload="initialize()" onunload="GUnload()" style="font-family: Arial;border: 0 none;">';
        echo '<form action="#" onsubmit="setDirections(this.from.value, this.to.value, this.locale.value); return false">';
        echo '<table>';
        echo '<tr>';
        echo '<th align="Left">From:&nbsp;</th>';
        echo '<td><input type="text" size="25" id="fromAddress" name="from" value=""/>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th align="Left">To:&nbsp;</th>';
        echo '<td><input type="text" size="25" id="toAddress" name="to" value="Luther College" />';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th align="Left">Language:&nbsp;</th>';
        echo '<td colspan="3">';
        echo '<select id="locale" name="locale">';
        echo '<option value="en" selected>English</option>';
        echo '<option value="fr">French</option>';
        echo '<option value="de">German</option>';
        echo '<option value="ja">Japanese</option>';
        echo '<option value="es">Spanish</option>';
        echo '</select>';
        echo '<input name="submit" type="submit" value="Get Directions" />';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';
        echo '<br/>';

        //echo '<tr><a href="" onclick="geolocate(); return false">From Current Location</a> (not all browsers support)</tr>';
        echo '<span style="font-size:11px"><a href="" onclick="geolocate(); return false">From Current Location</a> (not all browsers support)</span>';
        echo '<table class="directions">';
        //echo '<tr>';
        //echo '<td valign="top">';
        echo '<div id="map_canvas" style="width: 100%; height: 400px; float: left;"></div>';
        //echo '<td>';
        //echo '</tr>';
        //echo '<tr>';
        //echo '<td valign="top">';
        echo '<div id="directions" style="width: 100%; float: left; background-color: #FFFFFF;"></div>';
        //echo '<td>';
        //echo '</tr>';
        echo '</table>';
        echo '</body>';
    }
}
?>
