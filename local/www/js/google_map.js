/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function initialize()


{
    
    var map;

    function initialize() {
        var haightAshbury = new google.maps.LatLng(37.7699298, -122.4469157);
        var mapOptions = {
            zoom: 12,
            center: haightAshbury,
            mapTypeId: google.maps.MapTypeId.TERRAIN
        };
        map =  new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
    }

}
    
