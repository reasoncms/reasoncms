/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function initialize()


{
    
    var latlng = new google.maps.LatLng(9.931544168615512,76.27632894178791);
    var opt =
        { 
        center:latlng,
        zoom:10,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableAutoPan:false,
        navigationControl:true,
        navigationControlOptions: {style:google.maps.NavigationControlStyle.SMALL },
        mapTypeControl:true,
        mapTypeControlOptions: {style:google.maps.MapTypeControlStyle.DROPDOWN_MENU}
    };
    var map = new google.maps.Map(document.getElementById("map"),opt);
    var marker= new google.maps.Marker({
        position: new google.maps.LatLng(9.931544168615512,76.27632894178791),
        title: "CodeGlobe",
        clickable: true,
        map: map
    });


    var infiwindow = new google.maps.InfoWindow(
    {
        content: " I am here! "

    });


    google.maps.event.addListener(marker,'mouseover',function(){
        infiwindow.open(map,marker);
    });
    google.maps.event.addListener(marker,'mouseout',function(){
        infiwindow.close(map,marker);
    });


}
function test(event)
{

    alert( event.latLng.lat());
    alert(event.latLng.lng());

}
    
