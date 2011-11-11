$(document).ready(function() {

    // Director Form //
    toggle_banquet_attendees('N', 0);
    $("input[name$='banquet']").click(function(){
        toggle_banquet_attendees($(this).val(), 500);
    });
        
    // Student Form //
    toggle_allstate_details('N', 0);
    $("input[name$='all_state']").click(function(){
        toggle_allstate_details($(this).val(), 500);
    });
    
    toggle_honor_band_details('N', 0);
    $("input[name$='honor_band']").click(function(){
        toggle_honor_band_details($(this).val(), 500);
    });
});

function toggle_banquet_attendees(value, time){
    if (value == 'Y'){
        $('#banquet_attendees').show(time);
    } else {
        $('#banquet_attendees').hide(time);
    }
}

function toggle_allstate_details(value, time){
    if (value == 'Y'){
        $('#allStateYears').show(time);
        $('#allStatePart').show(time);
        $('#allStateChair').show(time);
    } else {
        $('#allStateYears').hide(time);
        $('#allStatePart').hide(time);
        $('#allStateChair').hide(time);
    }
}

function toggle_honor_band_details(value, time){
    if (value == 'Y'){
        $('#honorBandYears').show(time);
        $('#honorBandPart').show(time);
        $('#honorBandChair').show(time);
    } else {
        $('#honorBandYears').hide(time);
        $('#honorBandPart').hide(time);
        $('#honorBandChair').hide(time);
    }
}