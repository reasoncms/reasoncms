$(document).ready(function() {
	alert('hihi');
    toggle_cc_eligibilty(0);
    $("input#id_banquet_0").change(function(){
        toggle_cc_eligibilty(500);
        });
});

function toggle_cc_eligibilty(time){
    if ($('input#id_banquet_0').is(':checked')){
        $('#input#id_banquet_attendees').show(time);
    } else {
        $('# input#id_banquet_attendees').hide(time);
    }
}