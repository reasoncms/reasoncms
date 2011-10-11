/* 
 * 
 * Dorian Vocal Festival registration js
 * https://www.luther.edu/music/dorian/vocal/nominate
 * shows or hides info based on answers
 * 
 * Steve Smith
 * 
 */

$(document).ready(function() {
    toggle_cc_eligibilty(0);
    $("input#checkbox_desired_participation_2").change(function(){
        toggle_cc_eligibilty(500);
        });
});

function toggle_cc_eligibilty(time){
    if ($('input#checkbox_desired_participation_2').is(':checked')){
        $('#cceligibilitycommentRow').show(time);
        $('#cceligibilityRow').show(time);
    } else {
        $('#cceligibilitycommentRow').hide(time);
        $('#cceligibilityRow').hide(time);
    }
}