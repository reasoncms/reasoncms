<?php
/**
 * Utility functions for google maps display
 *
 * @author Brian Jones
 * @subpackage classes
 */

include_once(SETTINGS_INC .'google_api_settings.php');

function draw_google_map($gmaps)
// renders html and javascript for google maps display for both page and event types
// $gmaps is a google maps object usually consisting of a single google map
{	
	$protocol = (HTTPS_AVAILABLE && on_secure_page() ? 'https' : 'http');
	$campusTemplateID = (defined('GOOGLE_MAPS_CAMPUS_TEMPLATE_ID') ? GOOGLE_MAPS_CAMPUS_TEMPLATE_ID : '');
	$aspectRatio = (defined('GOOGLE_MAPS_ASPECT_RATIO') ? GOOGLE_MAPS_ASPECT_RATIO : 1.333333);

	foreach( $gmaps AS $gmap )
	{
		echo '<script type="text/javascript" src="'.$protocol.'://maps.googleapis.com/maps/api/js?sensor=false"></script>';
		echo '
		<script type="text/javascript">
	
		var directionsService = new google.maps.DirectionsService();
		var directionsDisplay = new google.maps.DirectionsRenderer();
		var map;
		var zoomLevel = '.$gmap->get_value('google_map_zoom_level').';
		google.maps.visualRefresh = true;
			
		function initialize() {
		if (document.getElementById("map_canvas").offsetWidth < 275) {
			zoomLevel = zoomLevel - 1;   // for smaller canvases zoom out one level.
		}
			var latLng = new google.maps.LatLng('.$gmap->get_value('google_map_latitude').','.$gmap->get_value('google_map_longitude').');
			var myOptions = {
				zoom: zoomLevel,
				center: latLng,
				mapTypeControlOptions: {mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.SATELLITE],
					style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				mapTypeControl: true,
				streetViewControl: false
			};
			setMapCanvasWidthHeigth();
			
			map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
			directionsDisplay.setMap(map);
			directionsDisplay.setPanel(document.getElementById("directionsPanel"));
				
			var arrayOfMsids = ["'. preg_replace("|\s|", '","', $gmap->get_value('google_map_msid')) . '"];';
			if ($gmap->get_value('google_map_show_campus_template') == "yes" && $campusTemplateID != '')
			{
				// campus map id in dedicated google account
				echo 'arrayOfMsids.splice(0, 0, "'.$campusTemplateID.'");';
			}
			echo 'var nyLayer = [];
			setLayers(arrayOfMsids, nyLayer);';
			if ($gmap->get_value('google_map_show_primary_pushpin') == "show")
			{
				echo 'var primaryPushpin = new google.maps.Marker({position: new google.maps.LatLng('.$gmap->get_value('google_map_primary_pushpin_latitude').', '.$gmap->get_value('google_map_primary_pushpin_longitude').'),map: map});';
			}
		echo '}
		
		function setMapCanvasWidthHeigth() {
			var defaultWidth = "460px";
			
			if (document.getElementById("map_canvas").offsetHeight <= 0 && document.getElementById("map_canvas").offsetWidth <= 0) {
				document.getElementById("map_canvas").style.width = defaultWidth;
				document.getElementById("map_canvas").style.height=Math.floor(defaultWidth/'.$aspectRatio.').toString().concat("px");
			}
			else if (document.getElementById("map_canvas").offsetHeight <= 0) {
				document.getElementById("map_canvas").style.height=Math.floor(document.getElementById("map_canvas").offsetWidth/'.$aspectRatio.').toString().concat("px");
			}
			else if (document.getElementById("map_canvas").offsetWidth <= 0) {
				document.getElementById("map_canvas").style.width=Math.floor(document.getElementById("map_canvas").offsetHeight*'.$aspectRatio.').toString().concat("px");
			}
		}
			
		function setLayers(arrayOfMsids, nyLayer) {
			for (var i = 0; i < arrayOfMsids.length; i++) {
				nyLayer[i] = new google.maps.KmlLayer(\''.$protocol.'://maps.google.com/maps/ms?msid=\' + arrayOfMsids[i] + \'&msa=0&output=kml\',
				{
					suppressInfoWindows: false,
					map: map,
					preserveViewport:true
				});
				nyLayer[i].setMap(map);
			}
		}
		
		function calculateRoute() {
			if (document.getElementById("map_from").value != "") {
				document.getElementById("directionsPanel").style.display="block";
				document.getElementById("totalDistance").style.display="block";
			} else {
				document.getElementById("directionsPanel").style.display="none";
				document.getElementById("totalDistance").style.display="none";
			}
			
			var map_from = document.getElementById("map_from").value;
			var map_to = new google.maps.LatLng('.$gmap->get_value('google_map_destination_latitude').','.$gmap->get_value('google_map_destination_longitude').');
			var request = {
				origin: map_from,
				destination: map_to,
				travelMode: google.maps.DirectionsTravelMode.DRIVING
			};
				
			directionsService.route(request, function(response, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					directionsDisplay.setDirections(response);
				}
			});
		}
		
		function resetDirections() {
			var latLng = new google.maps.LatLng('.$gmap->get_value('google_map_latitude').','.$gmap->get_value('google_map_longitude').');
				
			document.getElementById("directionsPanel").style.display="none";
			document.getElementById("totalDistance").style.display="none";
			directionsDisplay.setMap(null);
			directionsDisplay.setPanel(null);
			
			directionsDisplay = new google.maps.DirectionsRenderer();
			directionsDisplay.setMap(map);
			directionsDisplay.setPanel(document.getElementById("directionsPanel"));
			
			map.setCenter(latLng);
			map.setZoom(zoomLevel);
		}
		
		google.maps.event.addDomListener(window, \'load\', initialize);
			
		</script>'."\n";
		 
		echo '<div id="map_canvas"></div><br/>'."\n";
		if ($gmap->get_value('google_map_show_directions') == 'show')
		{
			echo 'From:<br/><input type="text" name="map_from" id="map_from">'."\n";
			echo '<input type="submit" value="Get Directions" onClick="calculateRoute()">'."\n";
			echo '<input type="button" value="Reset" id="map_reset" onClick="resetDirections()">'."\n";
			echo '<div id="directionsPanel"><p><span id="totalDistance"></span></p></div><br/>'."\n";
		}
	}
	
}