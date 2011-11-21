$(document).ready(function() {
  
    // Student Form //
    toggle_lesson_as_audition(0);
    $("input[name$='lesson']").change(function(){
        toggle_lesson_as_audition(500);
    });
});

function toggle_lesson_as_audition(time){
//    if (value == 'Y'){
    if ($('input#id_lesson_0').is(':checked')){
        $('#lessonAsAudition').show(time);
    } else {
        $('#lessonAsAudition').hide(time);
    }
}