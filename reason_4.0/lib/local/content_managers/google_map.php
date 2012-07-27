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
			
			//$this->change_element_type('google_map_latitude', 'hidden');
			//$this->change_element_type('google_map_longitude', 'hidden');
			//$this->change_element_type('google_map_zoom_level', 'hidden');
			
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
			
			$msid = $this->get_value('google_map_msid');
			if ($msid != null)
			{
				$this->set_value('google_map_msid', preg_replace("|\s|", PHP_EOL, $msid));				
			}
			
		}
		
		function on_every_time()
		{
			parent::on_every_time();

			echo '<link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />'."\n";
			echo '<style type="text/css">
      				#map_canvas {
					width: 600px;
					height: 400px;
					}</style>'."\n";
			echo '
			<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
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
		        var nyLayer = [];
		        setLayers(arrayOfMsids, nyLayer, map)
				
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
				google.maps.event.addDomListener(document.disco_form.google_map_msid, \'change\', function(e) {
					for (var i = 0; i < arrayOfMsids.length; i++) {
						nyLayer[i].setMap(null);
					}
					arrayOfMsids.splice(0, arrayOfMsids.length);
					nyLayer.length = 0;
					
					arrayOfMsids = document.disco_form.google_map_msid.value.split("\n");
		        	setLayers(arrayOfMsids, nyLayer, map)
				}); 
			}
			
			function updateMarkerPosition(latLng) {
				document.disco_form.google_map_latitude.value = latLng.lat();
				document.disco_form.google_map_longitude.value = latLng.lng();
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