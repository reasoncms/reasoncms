$(document).ready(function() {
    $(".words:contains('sports designation')").parent().hide(); 
    $(".words:contains('Employer Name')").parent().hide(); 
    $(".words:contains('Class Year')").parent().hide(); 

    $('input:checkbox[value="Norse Athletic Association (NAA)"]').change(function(){
        if (this.checked)
            $(".words:contains('sports designation')").parent().show(500);
        else
            $(".words:contains('sports designation')").parent().hide(500);
    });

    employer_match_id = ($(".words:contains('employer match gifts')").parent().attr('id'));
    employer_match_id = employer_match_id.replace(/id/i, "_id");
    employer_match = employer_match_id.replace(/Item/i, "");
    $('input[name="'+employer_match+'"]').change(function(){
        if ($('input[name="'+employer_match+'"]:checked').val() == 'Yes'){
            $(".words:contains('Employer Name')").parent().show(500);
        } else {
            $(".words:contains('Employer Name')").parent().hide(500);
        }
    });


    $('input:checkbox[value="Alum"]').change(function(){
        if (this.checked)
            $(".words:contains('Class Year')").parent().show(500);
        else 
            $(".words:contains('Class Year')").parent().hide(500);
    });
});