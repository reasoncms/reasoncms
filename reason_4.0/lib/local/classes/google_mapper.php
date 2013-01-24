<?php
	/**
	 * Utility functions for google maps display
	 *
	 * @author Brian Jones
	 * @subpackage classes
	 */

	function draw_google_map($gmaps)
	// renders html and javascript for google maps display for both page and event types
	// $gmaps is a google maps object usually consisting of a single google map
	{	
		$protocol = ((HTTPS_AVAILABLE && on_secure_page()) ? 'https' : 'http');

		foreach( $gmaps AS $gmap )
		{
			echo '<script type="text/javascript" src="'.$protocol.'://maps.googleapis.com/maps/api/js?sensor=false"></script>';
			echo '
			<script type="text/javascript">
		
			var directionsService = new google.maps.DirectionsService();
			var directionsDisplay = new google.maps.DirectionsRenderer();
			var map;
				
			function initialize() {
				var latLng = new google.maps.LatLng('.$gmap->get_value('google_map_latitude').','.$gmap->get_value('google_map_longitude').');
				var myOptions = {
					zoom:'. $gmap->get_value('google_map_zoom_level') . ',
					center: latLng,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					mapTypeControl: false,
					streetViewControl: false
				};
				map = new google.maps.Map(document.getElementById(\'map_canvas\'), myOptions);
				directionsDisplay.setMap(map);
				directionsDisplay.setPanel(document.getElementById("directionsPanel"));';
					
				echo '
				var arrayOfMsids = ["'. preg_replace("|\s|", '","', $gmap->get_value('google_map_msid')) . '"];';
				if ($gmap->get_value('google_map_show_campus_template') == "yes")
				{
					// campus map id in googlemaps@luther.edu account
					echo 'arrayOfMsids.splice(0, 0, "203908844213597815590.0004cfa54d955e6e86cbb");';
				}
				echo 'var nyLayer = [];
				setLayers(arrayOfMsids, nyLayer);
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
				map.setZoom('.$gmap->get_value('google_map_zoom_level').');
			}
			
			google.maps.event.addDomListener(window, \'load\', initialize);
				
			</script>'."\n";
			 
			echo '<div id="map_canvas" style="height: 400px; width: 453px;"></div><br/>'."\n";
			if ($gmap->get_value('google_map_show_directions') == 'show')
			{
				echo 'From:<br/><input type="text" name="map_from" id="map_from" style="width: 260px;">'."\n";
				echo '<input type="submit" value="Get Directions" onClick="calculateRoute()">'."\n";
				echo '<input type="button" value="Reset" style="float:right; margin-top:2px;" onClick="resetDirections()">'."\n";
				echo '<div id="directionsPanel"><p><span id="totalDistance"></span></p></div><br/>'."\n";
			}
		}
		
	}