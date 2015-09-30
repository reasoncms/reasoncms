/** 
 * geo.js
 *
 * provides mapping service to the event content manager.
 *
 * @author Nathan White
 * @requires jQuery
 *
 * @todo so there is this odd thing. If i turn off the hiding of the lat / lng fields I see that the longitude at least
 *       is sometimes slightly changed when I show the map ... why is this, and can we get rid of it? It should be based
 *       on the geocoded address provided by google so i'm not sure why it changes.
 *
 * @todo we should perhaps add a standardized loader for when we are switching layers - at least for the static map updates.
 * @todo for the dynamic geocoding of the address field consider using Google maps directly instead of our Reason geocoding class.
 * @todo support google maps premier
 */

$(document).ready(function()
{
	if ( typeof (google) == "undefined") return false;
	
	// if the current url is loaded over https, then https is available
	var https_available = ($(location).attr('protocol') == 'https:') ? true : false;
	
	// define globals we want all the functions to be able to access.
	var gmap; // the google map
	var gmarker; // the google map marker
	var gmap_known_address_lat_lng = {}; // javascript address cache object
	var gzoom_default = 11; // default zoom level for gmap
	var gmap_placing_marker = false; // track whether we are in "place a marker mode"
	var gmap_loading = false; // track whether we are loading the full map
	var address_sync = false; // track whether or not we are syncing our location with the address field
	var lat_element = $("input#latitudeElement");
	var long_element = $("input#longitudeElement");
	var address_element = $("input#addressElement");
	var auto_update_element = $("input#checkbox_auto_update_coordinates");
	var update_timer;
	
	var map_marker_text;
	var map_marker_add;
	var map_marker_remove;
	var map_marker_update;
	
	// lets define our main container divs that we want to be able to reference anywhere
	var map_container = $('<div id="mapContainer"></div>');
	var static_map_container = $('<div id="staticMapContainer"></div>');
	var full_map_container = $('<div id="fullMapContainer"></div>');
	var map_link_container = $('<div id="mapLinkContainer"></div>');
	
	var gmap_map_container;
	var gmap_options_container;
	var map_container;
	var map_row;
	
	init();
	
	// There is no real need to wrap this up in init but we do
	function init()
	{
		// lets grab our salient existing DOM elements once right here.
		var lat_row = $("tr#latitudeRow");
		var long_row = $("tr#longitudeRow");
		var auto_update_row = $("tr#autoupdatecoordinatesRow");	
			
		// lets clone the structure of a disco row, then replace it with our map element
		map_row = lat_row.clone().attr("id", "mapRow");
		$("td.words", map_row).text('Map:');
		$("td.element", map_row).html(map_container);
		
		lat_row.before(map_row);
		lat_row.hide();
		long_row.hide();
		auto_update_row.hide();
		
		
		address_element.bind("change", function()
		{
			update();
		});
		
		address_element.bind("keyup", function()
		{
			if (update_timer) clearTimeout(update_timer);
			update_timer = setTimeout(update, 3000);
		});
		
		if (should_sync_address_and_location())
		{
			start_address_sync();
			update();
		}
		else show("full_map");
		map_row.show();
	}
	
	/**
	 * This is called dynamically based on bound or timed events, and at init.
	 */
	function update()
	{
		if (update_timer) clearTimeout(update_timer);
		if (address_sync)
		{
			if (aloc = get_location_from_address(true))
			{
				update_location(aloc);
				show("static_map");
			}
			else
			{
				update_location(null);
				show("map_link");
			}
		}
		else
		{
			update_marker_address_distance();
			refresh_map_marker_update();
		}
	}
	
	/**
	 * show is designed to switch visibility between our various supported modes and handles hide / show as follows
	 *
	 * - calls the appropriate "show" method which should init / update the layer
	 * - set the layer visibility to show
	 * - hide all the other layers
	 *
	 * these are the layers we currently support
	 *
	 * - map_link
	 * - static_map
	 * - full_map
	 *
	 * @todo can we handle loaders here?
	 */
	function show(layer)
	{
		if (layer == "map_link")
		{
			static_map_container.hide();
			full_map_container.hide();
			show_map_link();
		}
		else if (layer == "static_map")
		{
			full_map_container.hide();
			map_link_container.hide();
			show_static_map();
		}
		else if (layer == "full_map")
		{
			static_map_container.hide();
			map_link_container.hide();
			show_full_map();
		}
	}

	/**
	 * Show a link which brings up the full map.
	 */
	function show_map_link()
	{
		// one time initialization
		if (typeof show_map_link.inited == 'undefined')
		{
			map_link = $('<a href="#">Map</a>');
			map_link.click(function()
			{
				stop_address_sync();
				show("full_map");
				return false;
			});
			map_link_container.prepend(map_link);
			map_container.prepend(map_link_container);
			show_map_link.inited = 'true';
		}
		
		// things to do every time we call this.
		start_address_sync();
		map_link_container.show();
	}
	
	/**
	 * Add and/or update a static map based upon the address field.
	 *
	 * @todo add something so we only reload if address has changed
	 */
	function show_static_map()
	{
		// one time initialization
		if (typeof show_static_map.inited == 'undefined')
		{
			map_container.prepend(static_map_container);
			static_map_container.click(function()
			{
				stop_address_sync();
				show("full_map");
				return false;
			});
			show_static_map.inited = true;
		}
		
		// things to do every time we call this.
		start_address_sync();
		if (address_element.val())
		{
			aloc = get_location_from_address(true);
			// get static map of it.
			if (aloc)
			{
				var src_base = (https_available) ? 'https://maps.googleapis.com/maps/api/staticmap' : 'http://maps.google.com/maps/api/staticmap' 
				var src = src_base + '?center='+aloc.lat()+','+aloc.lng()+'&markers='+aloc.lat()+','+aloc.lng()+'&zoom=11&size=300x300&sensor=false';
				static_map_container.html('<img width="300px" height="300px" src="'+src+'" />');
				static_map_container.show();
			}
			else // we should revert to just the show map
			{
				show("map_link");
			}
		}
		else
		{
			show("map_link");
		}
	}
	
	function show_full_map()
	{
		// lets put our containers before and after the link and remove the link
		if (typeof show_full_map.inited == 'undefined')
		{
			// lets declare our marker options
			map_marker_text = $('<span class="addMarkerText">Click on Map to Place Marker<span>');
			map_marker_add = $('<a class="addMarker" href="#">Place Marker</a>');
			map_marker_remove = $('<a class="removeMarker" href="#">Remove Marker</a>');
			map_marker_update = $('<a class="generateMarker" href="#">Place Map Marker from Address</a>');
			map_marker_loading = $('<img class="mapLoading" src="/reason_package/reason_4.0/www/ui_images/reason_admin/wait.gif" />');
			
			// lets declare our subcontainers and populate them
			gmap_map_container = $('<div id="gmapContainer"></div>'); // gmap container div
			gmap_options_container = $('<div id="gmapOptionsContainer" class="formComment smallText"></div>'); // map options container
			
			full_map_container.prepend(gmap_map_container).prepend(gmap_options_container);
			gmap_options_container.prepend(map_marker_loading).prepend(map_marker_update).prepend(map_marker_remove).prepend(map_marker_add).prepend(map_marker_text);
			map_container.prepend(full_map_container);
			
			gmap_start_load();
			gloc = get_location_from_entity();
			if (gloc)
			{
				create_google_map(gloc);
				create_google_map_marker(gloc);
			}
			else // this could involve some waiting
			{
				if (gloc = get_location_from_address())
				{
					create_google_map(gloc);
					create_google_map_marker(gloc);
				}
				else if (gloc = get_location_from_ip()) // if we can geolocate just show the map no marker
				{
					create_google_map(gloc);
				}
				else // lets show a world map
				{
					gloc = get_default_location();
					create_google_map(gloc, 3);
				}
			}
			gmap_end_load();
			
			map_marker_add.click(function()
			{
				if (!gmarker) // place the marker at the crosshair location.
				{
					gmap_start_click_to_place();
				}
				refresh_map_marker_update();
				return false;
			});
			
			map_marker_remove.click(function()
			{
				update_location(null);
				return false;
			});
			
			map_marker_update.click(function()
			{
				gmap_start_load();
				var aloc = get_location_from_address(true);
				if (aloc)
				{
					//gmap.setCenter(aloc);
					//gmap.setZoom(gzoom_default);
					if (!gmarker) create_google_map_marker(aloc);
					update_location(aloc);
					show("static_map");
				}
				else
				{
					alert('Please enter a valid address in the address field.');
				}
				gmap_end_load();
				return false;
			});
		
			refresh_map_marker_update();
			show_full_map.inited = true;
		}
		else // every time but first load
		{
			var gloc = get_location_from_address(true);
			if (gloc)
			{
				gmap.setCenter(gloc);
				gmap.setZoom(gzoom_default);
				if (!gmarker)
				{
					create_google_map_marker(gloc);
					refresh_map_marker_update();
				}
				else
				{
					update_location(gloc);
				}
			}
		}
		// stuff we always do
		stop_address_sync();
		full_map_container.show();
	}

	function start_address_sync()
	{
		address_sync = true;
		auto_update_element.attr('checked', true);
	}
	
	function stop_address_sync()
	{
		address_sync = false;
		auto_update_element.attr('checked', false);
	}
	
	function update_marker_address_distance()
	{
		a_loc = get_location_from_address(true); // we try cache first.
		e_loc = get_location_from_entity();
		if (a_loc && e_loc && !a_loc.equals(e_loc))
		{
			distance_meters = google.maps.geometry.spherical.computeDistanceBetween(a_loc, e_loc);
			distance_miles = (distance_meters*.000621371192);
			$("#addressRow div.formComment").text('The address and map coordinates are '+parseFloat(distance_miles).toFixed(2)+' miles apart.');
		}
		else if (a_loc && e_loc && address_element.val())
		{
			$("#addressRow div.formComment").text('The address and map coordinates are synchronized.');
		}
		else if (!a_loc && e_loc && address_element.val())
		{
			$("#addressRow div.formComment").text('This address cannot be geocoded but map coordinates are set.');
		}
		else if (a_loc && !e_loc && address_element.val())
		{
			$("#addressRow div.formComment").text('This address can be geocoded but coordinates are not set.');
		}
		else if (!a_loc && !e_loc && address_element.val())
		{
			$("#addressRow div.formComment").text('This address cannot be geocoded and coordinates are not set.');
		}
		else if (e_loc && !address_element.val())
		{
			$("#addressRow div.formComment").text('No address is entered but coordinates are set.');
		}
		else if (!e_loc && !address_element.val())
		{
			$("#addressRow div.formComment").text('Please provide as detailed an address as possible.');
		}
	}
	
	function gmap_start_click_to_place()
	{
		if (gmap_placing_marker == false)
		{
			gmap.setOptions({ draggableCursor: 'crosshair' });
			google.maps.event.addListenerOnce(gmap, 'click', function(event)
			{
				if (!gmarker && gmap_placing_marker) create_google_map_marker(event.latLng);
				gmap_stop_click_to_place();
			});
			gmap_placing_marker = true;
		}
	}
	
	function gmap_stop_click_to_place()
	{
		if (gmap_placing_marker == true)
		{
			gmap.setOptions({ draggableCursor: 'move' });
			google.maps.event.clearListeners(gmap, 'click');
			gmap_placing_marker = false;
		}
	}
	
	function gmap_start_load()
	{
		gmap_loading = true;
		refresh_map_marker_update();
	}
	
	function gmap_end_load()
	{
		gmap_loading = false;
		refresh_map_marker_update();
	}
	
	/**
	 * Set the visibility and text display of the map marker options.
	 * 
	 * @todo this seems to need a sanity check.
	 */
	function refresh_map_marker_update()
	{
		// if we do not have a marker but we have content in the address field - show and set text to "set"
		if (!gmap || address_sync) return false;
		if (gmap_loading)
		{
			map_marker_loading.show();
		}
		else
		{
			map_marker_loading.hide();
			address = address_element.val();
			aloc = get_location_from_address(true);
			if (address && aloc && !gmarker) map_marker_update.text('Place Map Marker From Address');
			if (!gmarker)
			{
				if (address && aloc)
				{
					gmap_stop_click_to_place();
					map_marker_update.text('Place Map Marker From Address');
					map_marker_update.show();
					map_marker_add.hide();
					map_marker_text.hide();
				}
				else
				{
					//map_marker_update.hide();
					if (gmap_placing_marker)
					{
						map_marker_text.show();
						map_marker_add.hide();
					}
					else
					{
						map_marker_text.hide();
						map_marker_add.show();
					}
					map_marker_update.hide();
				}
				map_marker_remove.hide();
				//auto_update_element.attr('checked', true);
			}
			else
			{
				if (!address || !aloc)
				{
					map_marker_text.hide();
					map_marker_update.hide();
					map_marker_add.hide();
					map_marker_remove.show();
					//auto_update_element.attr('checked', false);
				}
				else
				{
					aloc = get_location_from_address(true);
					eloc = get_location_from_entity();
					if (aloc && eloc && eloc.equals(aloc))
					{
						map_marker_text.hide()
						map_marker_update.hide();
						map_marker_add.hide();
						map_marker_remove.hide();
						//auto_update_element.attr('checked', true);
					}
					else
					{
						map_marker_update.text('Update Map Marker From Address');
						map_marker_text.hide();
						map_marker_update.show();
						map_marker_add.hide();
						map_marker_remove.hide();
						//auto_update_element.attr('checked', false);
					}
				}
			}
		}
	}

	/**
	 * Cases for which we return true
	 *
	 * - location is empty
	 * - address and location are populated and in sync
	 */
	function should_sync_address_and_location()
	{
		eloc = get_location_from_entity();
		if (!eloc) return true;
		else (aloc = get_location_from_address(true))
		{
			if (aloc && eloc.equals(aloc)) return true;
		}
	}

	/**
	 * @return mixed object LatLng
	 */
	function get_location_from_address_if_known()
	{
		var address = address_element.val();
		var loc = $.data(gmap_known_address_lat_lng, address);
		return loc;
	}
	
	/**
	 * @param optional param - if true we will try the javascript cache first.
	 * @return mixed object LatLng or FALSE
	 */
	function get_location_from_address(try_cache_first)
	{
		var address = address_element.val();

		if (try_cache_first)
		{
			cached = get_location_from_address_if_known();
			if (cached != undefined)
			{
				return cached;
			}
		}
		
		if (address != undefined && address != "")
		{
			// lets do our ajax to geolocate the address.
			var request_params = { 'module_api' : 'geocoder',
						 		   'geo_source' : 'address',
						 		   'geo_action' : 'encode',
						 		   'geo_value' : address };
			var loc;
			$.ajax(
			{ 
				data: request_params,
				async: false,
				cache: false,
				timeout: 5000,
				error: function()
        		{
        			loc = false;
        		},
        		success: function(myjson)
        		{
        			if (myjson.latitude && myjson.longitude)
        			{
        				loc = new google.maps.LatLng(myjson.latitude, myjson.longitude);
        			}
        			else
        			{
        				loc = false;
        			}
        			$.data(gmap_known_address_lat_lng, address, loc);
            	}
            });
            return loc;
        }
		else
		{
			return false;
		}
	}
	
	/**
	 * @return mixed object LatLng or FALSE
	 */
	function get_location_from_ip()
	{
		// lets do our ajax to geolocate the address.
		var request_params = { 'module_api' : 'geocoder',
					 		   'geo_source' : 'ip',
					 		   'geo_action' : 'encode'
					 		 };
		var loc;
			
		$.ajax(
		{ 
			data: request_params,
			async: false,
			cache: false,
			timeout: 5000,
			error: function()
    		{
    			loc = false;
    		},
    		success: function(myjson)
    		{
    			if (myjson.latitude && myjson.longitude)
    			{
    				loc = new google.maps.LatLng(myjson.latitude, myjson.longitude);
    			}
    			else loc = false;
        	}
        });
        return loc;
    }
    
	/**
	 * @return mixed object LatLng or false
	 */	
	function get_location_from_entity()
	{
		var lat = get_latitude_from_input_field();
		var lng = get_longitude_from_input_field();
		if (lat && lng)
		{
			loc = new google.maps.LatLng(lat, lng);
			return loc;
		}
		return false;
	}
	
	/**
	 * We use Northfield, MN as our default location.
	 */
	function get_default_location()
	{
		loc = new google.maps.LatLng(44.4583333, -93.1613889);
		return loc;
	}
	
	/**
	 * Get the latitude from the input field if it exists
	 */
	function get_latitude_from_input_field()
	{
		latitude = lat_element.val();
		return (latitude != undefined && latitude != 0) ? latitude : false;
	}
	
	/**
	 * Get the longitude from the input field if it exists
	 */
	function get_longitude_from_input_field()
	{
		longitude = long_element.val()
		return (longitude != undefined && longitude != 0) ? longitude : false;
	}
	
	function get_address()
	{
		address = address_element.val();
		return (address != undefined) ? address : "";
	}
	
	/**
	 * @param object gloc - google LatLng object
	 */
	function create_google_map(gloc, zoom)
	{
		if (gloc)
		{
			zoom = (zoom == undefined) ? gzoom_default : zoom;
			gmap = new google.maps.Map(gmap_map_container.get(0), {
				zoom: zoom,
				center: gloc,
				mapTypeId: google.maps.MapTypeId.ROADMAP });
		}
	}
	
	/**
	 * @param object gloc - google LatLng object
	 */
	function create_google_map_marker(gloc)
	{
		if (gmap && gloc && !gmarker) // lets never create it if it exists
		{
			gmap_stop_click_to_place();
			gmarker = new google.maps.Marker({
				position: gloc,
				title: 'Point A',
				map: gmap,
				draggable: true });			
			google.maps.event.addListener(gmarker, 'drag', function()
			{
				update_location(gmarker.getPosition());
			});
			update_location(gmarker.getPosition());
		}
	}
	
	/**
	 * Update our location fields and the gmarker if it exists and ignoreMarker has not been explicitly set to true.
	 */
	function update_location(latLng)
	{
		//ignoreMarker = (typeof(ignoreMarker) != 'undefined') ? ignoreMarker : false;
		//if (!ignoreMarker)
		//{
		//	update_marker_position(latLng);
		//}
		update_marker_position(latLng);
		update_location_fields(latLng);
		
		update_marker_address_distance();
		refresh_map_marker_update();
	}
	
	function update_marker_position(latLng)
	{
		if (gmarker)
		{
			if (latLng != null)
			{
				gmarker.setPosition(latLng);
			}
			else
			{
				gmarker.setMap(null);
				gmarker = null;
			}
		}
	}
	
	function update_location_fields(latLng)
	{
	
		if (latLng != null)
		{
			mylat = latLng.lat();
			mylng = latLng.lng();
		}
		else
		{
			mylat = '';
			mylng = '';
		}
		lat_element.val(mylat);
		long_element.val(mylng);
	}
});