$(document).ready(function() {
 
    // Student Form //
    toggle_lesson_as_audition(0);
    $("input[name$='lesson']").change(function(){
        toggle_lesson_as_audition(500);
    });
    
    $("select[name$='instrument']").change(function(){
        set_section($(this).val());
    });
    
});

function toggle_lesson_as_audition(time){
    if ($('input#id_lesson_0').is(':checked')){
        $('#lessonAsAudition').show(time);
    } else {
        $('#lessonAsAudition').hide(time);
    }
}

function set_section(instrument){
    
        switch(instrument){
            case 'Violin':
                $("select[name$='section'] option[value='Viola']").remove();
                $("select[name$='section'] option[value='Cello']").remove();
                $("select[name$='section'] option[value='Double Bass']").remove();
                $("select[name$='section'] option[value='Other']").remove();
                break;
            case 'Viola':
                $("select[name$='section']").val("Viola");
                break;
            case 'Cello':
                $("select[name$='section']").val("Cello");
                break;
            case 'Double Bass':
                $("select[name$='section']").val("Double Bass");
                break;
            case 'Harp':
                $("select[name$='section']").val("Other");
                break;
        }
}