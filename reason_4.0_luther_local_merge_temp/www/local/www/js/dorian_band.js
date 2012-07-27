$(document).ready(function() {

    // Director Form //
    toggle_banquet_attendees(0);
    $("input[name$='banquet']").change(function(){
        toggle_banquet_attendees(500);
    });
        
    // Student Form //
    toggle_allstate_details(0);
    $("input[name$='all_state']").change(function(){
        toggle_allstate_details(500);
    });
    
    toggle_honor_band_details(0);
    $("input[name$='honor_band']").change(function(){
        toggle_honor_band_details(500);
    });
});

function toggle_banquet_attendees(time){
//    if (value == 'Y'){
    if ($("input#id_banquet_0").is(':checked')){ 
        $('#banquet_attendees').show(time);
    } else {
        $('#banquet_attendees').hide(time);
    }
}

function toggle_allstate_details(time){
//    if (value == 'Y'){
    if ($('input#id_all_state_0').is(':checked')){
        $('#allStateYears').show(time);
        $('#allStatePart').show(time);
        $('#allStateChair').show(time);
    } else {
        $('#allStateYears').hide(time);
        $('#allStatePart').hide(time);
        $('#allStateChair').hide(time);
    }
}

function toggle_honor_band_details(time){
//    if (value == 'Y'){
    if ($('input#id_honor_band_0').is(':checked')){
        $('#honorBandYears').show(time);
        $('#honorBandPart').show(time);
        $('#honorBandChair').show(time);
    } else {
        $('#honorBandYears').hide(time);
        $('#honorBandPart').hide(time);
        $('#honorBandChair').hide(time);
    }
}