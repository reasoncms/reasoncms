<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'GoogleMap';

	/**
	 * A content manager for displaying a Google Map
	 */
	define("DEFAULT_ZOOM_LEVEL", 16);
	define("DEFAULT_LATITUDE", 43.313625);
	define("DEFAULT_LONGITUDE", -91.804547);
	
	class GoogleMap extends ContentManager
	{

		function alter_data()
		{
			$this->set_comments('google_map_msid', form_comment('My places map id (e.g. 206354152960879485239.0004bd7c539131dd1e563) containing custom polygons and placemarks.<br/>Place multiple ids on a separate lines.'));
			$this->set_display_name('google_map_zoom_level', 'Zoom level');
			$this->set_display_name('google_map_latitude', 'Latitude');
			$this->set_display_name('google_map_longitude', 'Longitude');
			$this->set_display_name('google_map_msid', 'Map ID');
			$this->set_display_name('google_map_show_campus_template', 'Show Campus Template');
			$this->set_display_name('google_map_destination_latitude', 'Destination Latitude');
			$this->set_display_name('google_map_destination_longitude', 'Destination Longitude');
			$this->set_display_name('google_map_show_directions', 'Directions');
			$this->change_element_type('google_map_show_directions', 'radio_no_sort', array('options'=>array('show'=>'Show <span class="smallText formComment">(Drag the "To" bubble to set destination)</span>','hide'=>'Hide <span id="destination_lat_long" class="smallText formComment">(Don\'t display the directions interface)</span>')));
			
			//$this->change_element_type('google_map_destination_latitude', 'hidden');
			//$this->change_element_type('google_map_destination_longitude', 'hidden');
			$this->change_element_type('no_share', 'hidden');
						
			if (!$this->get_value('google_map_zoom_level'))
			{
				$this->set_value('google_map_zoom_level', DEFAULT_ZOOM_LEVEL);
			}
			if (!$this->get_value('google_map_latitude'))
			{
				$this->set_value('google_map_latitude', DEFAULT_LATITUDE);
			}
			if (!$this->get_value('google_map_longitude'))
			{
				$this->set_value('google_map_longitude', DEFAULT_LONGITUDE);
			}
			if (!$this->get_value('google_map_destination_latitude') && !$this->get_value('google_map_destination_longitude'))
			{
				$this->set_value('google_map_destination_latitude', $this->get_value('google_map_latitude'));
				$this->set_value('google_map_destination_longitude', $this->get_value('google_map_longitude'));
			}
			if (!$this->get_value('google_map_show_directions'))
			{
				$this->set_value('google_map_show_directions', 'show');
			}
			
			$msid = $this->get_value('google_map_msid');
			if ($msid != null)
			{
				$this->set_value('google_map_msid', preg_replace("|\s|", PHP_EOL, $msid));				
			}
			
			$this->set_order(
				array(
					'name',
					'unique_name',
					'google_map_zoom_level',
					'google_map_latitude',
					'google_map_longitude',
					'google_map_msid',
					'google_map_show_campus_template',
					'google_map_show_directions',
					'google_map_destination_latitude',
					'google_map_destination_longitude',
				)
			);
			
		}
		
		function on_every_time()
		{
			$protocol = (HTTPS_AVAILABLE && on_secure_page() ? 'https' : 'http');
			
			parent::on_every_time();

			echo '<link href="'.$protocol.'://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />'."\n";
			echo '<style type="text/css">
      				#map_canvas {
					width: 600px;
					height: 400px;
					}
					#googlemapdestinationlatitudeRow {display: none;}  /* hack to avoid changing hidden input field */
					#googlemapdestinationlongitudeRow {display: none;}
					</style>'."\n";
			echo '
			<script type="text/javascript" src="'.$protocol.'://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
			<script type="text/javascript" src="'.$protocol.'://google-maps-utility-library-v3.googlecode.com/svn/trunk/styledmarker/src/StyledMarker.js"></script>
			<script type="text/javascript">
						
			function initialize() {
				var latLng = new google.maps.LatLng('.$this->get_value('google_map_latitude').','.$this->get_value('google_map_longitude').');
		        var myOptions = {
					zoom:'. $this->get_value('google_map_zoom_level') . ',
					center: latLng,
					mapTypeId: google.maps.MapTypeId.ROADMAP,
					mapTypeControl: false,
					streetViewControl: false
		        };
		        var map = new google.maps.Map(document.getElementById(\'map_canvas\'), myOptions);
		        
		        var arrayOfMsids = ["'. preg_replace("|\s|", '","', $this->get_value('google_map_msid')) . '"];
		        if (document.disco_form.google_map_show_campus_template.options[document.disco_form.google_map_show_campus_template.options.selectedIndex].value == "yes") {
					arrayOfMsids.splice(0, 0, "203908844213597815590.0004cfa54d955e6e86cbb");
				}
		        var nyLayer = [];
		        setLayers(arrayOfMsids, nyLayer, map);
		             
		        var destMarker = new StyledMarker({styleIcon:new StyledIcon(StyledIconTypes.BUBBLE,{color:"#cccccc",text:"To"}),position:new google.maps.LatLng('.$this->get_value('google_map_destination_latitude').', '.$this->get_value('google_map_destination_longitude').'),map:map,draggable:true});
		        if (document.getElementById("radio_google_map_show_directions_0").checked) {
		        	showDestination(destMarker, 0);
		        } else {
		        	showDestination(destMarker, 1);
		        }
				
				google.maps.event.addListener(map, \'dragend\', function(e) {
					updateMarkerPosition(map.getCenter());
				});
				google.maps.event.addListener(map, \'zoom_changed\', function(e) {
					document.disco_form.google_map_zoom_level.value = map.getZoom();
				});  
				google.maps.event.addDomListener(document.disco_form.google_map_zoom_level, \'change\', function(e) {
					map.setZoom(parseInt(document.disco_form.google_map_zoom_level.value));
				});
				google.maps.event.addDomListener(document.disco_form.google_map_latitude, \'change\', function(e) {
					var cntr = new google.maps.LatLng(Math.min(85.0511, Math.max(-85.0511, parseFloat(document.disco_form.google_map_latitude.value))), parseFloat(document.disco_form.google_map_longitude.value));
					map.setCenter(cntr);
					updateMarkerPosition(map.getCenter());
				});
				google.maps.event.addDomListener(document.disco_form.google_map_longitude, \'change\', function(e) {
					var cntr = new google.maps.LatLng(parseFloat(document.disco_form.google_map_latitude.value), parseFloat(document.disco_form.google_map_longitude.value));
					map.setCenter(cntr);
					updateMarkerPosition(map.getCenter());
				});
				google.maps.event.addDomListener(document.getElementById("radio_google_map_show_directions_0"), \'change\', function(e) {
					showDestination(destMarker, 0);
				});
				google.maps.event.addDomListener(document.getElementById("radio_google_map_show_directions_1"), \'change\', function(e) {
					showDestination(destMarker, 1);
				});
				google.maps.event.addDomListener(destMarker, \'dragend\', function(e) {
					var dragEnd = e.latLng;
					document.disco_form.google_map_destination_latitude.value = dragEnd.lat();
					document.disco_form.google_map_destination_longitude.value = dragEnd.lng();
				});
				
				google.maps.event.addDomListener(document.disco_form.google_map_show_campus_template, \'change\', function(e) {
					for (var i = 0; i < arrayOfMsids.length; i++) {
						nyLayer[i].setMap(null);
					}
					arrayOfMsids.splice(0, arrayOfMsids.length);
					nyLayer.length = 0;
					
					arrayOfMsids = document.disco_form.google_map_msid.value.split("\n");
					if (document.disco_form.google_map_show_campus_template.options[document.disco_form.google_map_show_campus_template.options.selectedIndex].value == "yes")
					{
						arrayOfMsids.splice(0, 0, "203908844213597815590.0004cfa54d955e6e86cbb");
					}
					setLayers(arrayOfMsids, nyLayer, map)
				});
				google.maps.event.addDomListener(document.disco_form.google_map_msid, \'change\', function(e) {
					for (var i = 0; i < arrayOfMsids.length; i++) {
						nyLayer[i].setMap(null);
					}
					arrayOfMsids.splice(0, arrayOfMsids.length);
					nyLayer.length = 0;
					
					arrayOfMsids = document.disco_form.google_map_msid.value.split("\n");
					if (document.disco_form.google_map_show_campus_template.options[document.disco_form.google_map_show_campus_template.options.selectedIndex].value == "yes")
					{
						arrayOfMsids.splice(0, 0, "203908844213597815590.0004cfa54d955e6e86cbb");
					}
		        	setLayers(arrayOfMsids, nyLayer, map)
				}); 
			}
			
			function updateMarkerPosition(latLng) {
				document.disco_form.google_map_latitude.value = latLng.lat();
				document.disco_form.google_map_longitude.value = latLng.lng();
			}
			
			function setLayers(arrayOfMsids, nyLayer, map) {
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
			
			function showDestination(destMarker, show) {
				if (show == 0) {
					destMarker.setVisible(true);
				}
				else {
					destMarker.setVisible(false);
				}
			}
		
			google.maps.event.addDomListener(window, \'load\', initialize);			
			
			</script>'."\n";
        	
        	echo '<div id="map_canvas"></div><br/>'."\n";
        	
        	/*echo '<script type="text/javascript">
        		$(document).ready(function() {
        			$("input").change(function() {
    					$(this).css("background-color","#FFFFCC");
    					google.maps.setZoom(11);
  					});
  				});
  			</script>'."\n";*/
		
		}
		
		function process()
		{
			parent::process();
		}
	}
?>