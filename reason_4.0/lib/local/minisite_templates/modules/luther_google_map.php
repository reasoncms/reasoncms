<?php
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherGoogleMapModule';

	define("DEFAULT_ZOOM_LEVEL", 16);
	define("DEFAULT_LATITUDE", 43.313625);
	define("DEFAULT_LONGITUDE", -91.804547);
	
	class LutherGoogleMapModule extends DefaultMinisiteModule
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
			$site_id = $this->site_id;
			$es = new entity_selector( $site_id );
			$es->add_type( id_of( 'google_map_type' ) );
			$es->add_right_relationship($this->cur_page->id(), relationship_id_of('page_to_google_map'));
			$es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_google_map'));
			$es->set_order('rel_sort_order'); 
			$gmaps = $es->run_one();
			
			foreach( $gmaps AS $gmap )
			{

				//echo '<link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css">'."\n";

				echo '<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>';
				if ($gmap->get_value('google_map_show_campus_template') == "yes")
				{
					echo '<script type="text/javascript" src="http://knuth.luther.edu/~bantch01/lutherMaps/markers.js"> </script>';
					echo '<script type="text/javascript" src="http://knuth.luther.edu/~bantch01/lutherMaps/polygons.js"> </script>';
				}
				echo '
				<script type="text/javascript">
			
				function initialize() {
					var latLng = new google.maps.LatLng('.$gmap->get_value('google_map_latitude').','.$gmap->get_value('google_map_longitude').');
			        var myOptions = {
						zoom:'. $gmap->get_value('google_map_zoom_level') . ',
						center: latLng,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						mapTypeControl: false,
						streetViewControl: false
			        };
			        var map = new google.maps.Map(document.getElementById(\'map_canvas\'), myOptions);';			        
			        if ($gmap->get_value('google_map_show_campus_template') == "yes")
					{
						echo 'drawCampusTemplate(map);';
					}
					echo '
			        var arrayOfMsids = ["'. preg_replace("|\s|", '","', $gmap->get_value('google_map_msid')) . '"];
			        var nyLayer = [];
			        setLayers(arrayOfMsids, nyLayer, map);
				}
			
				function setLayers(arrayOfMsids, nyLayer, map) {
					for (var i = 0; i < arrayOfMsids.length; i++) {
			        	nyLayer[i] = new google.maps.KmlLayer(\'http://maps.google.com/maps/ms?msid=\' + arrayOfMsids[i] + \'&msa=0&output=kml\',
						{
		                	suppressInfoWindows: false,
		                	map: map,
		                	preserveViewport:true 
		        		});
						nyLayer[i].setMap(map);
					} 
				}
				
				function drawCampusTemplate(map) {
					var gmarkers = [];
					var gpolygons = [];
	
					var aColor = "red";
					var recColor = "blue";
					var resColor = "yellow";
					var susColor = "green";
					var parkColor = "grey";
					var tempColor = "black";
	
					var lutherPolygon;
					// Creating a POLYGON and positioning it on the map
					for (polygon in polygonCoords) {
						var name = polygonCoords[polygon][0];
						var coords = polygonCoords[polygon][1];
						var desc = polygonCoords[polygon][2];
						var cat = polygonCoords[polygon][3];
						if (cat == "academic") tempColor = aColor;
						if (cat == "recreational") tempColor = recColor;
						if (cat == "residential") tempColor = resColor;
						if (cat == "sustainability") tempColor = susColor;
						if (cat == "parking") tempColor = parkColor;
						
						lutherPolygon = new google.maps.Polygon({
							paths: coords,
							map: map,
							strokeColor: tempColor,
							strokeOpacity: 0.45,
							strokeWeight: 0,
							fillColor: tempColor,
							fillOpacity: 0.25,
							polyName: name,
							polyDesc: desc,
							category: cat,
							id: polygon	
						});
	
	  					lutherPolygon.setMap(map);
					}
				}
		
				google.maps.event.addDomListener(window, \'load\', initialize);			
			
				</script>'."\n";
        	
				echo '<div id="map_canvas" style="height: 400px; width: 453px;"></div><br/>'."\n";
			}      
		}
	}
?>
