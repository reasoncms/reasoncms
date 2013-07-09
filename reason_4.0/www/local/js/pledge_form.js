$(document).ready(function() {
    $(".words:contains('sports designation')").parent().hide(); 
    $(".words:contains('Employer Name')").parent().hide(); 
    $(".words:contains('Class Year')").parent().hide(); 

    $('input:checkbox[value="Norse Athletic Association (NAA)"]').change(function(){
        if (this.checked)
            $(".words:contains('sports designation')").parent().show();
        else
            $(".words:contains('sports designation')").parent().hide();
    });

    var employer_match_id = ($(".words:contains('employer match gifts')").parent().attr('id'));
    var employer_match = employer_match_id.replace(/row/i, "");
    $('input[name="'+employer_match+'"]').change(function(){
        alert('yo');
    });


    $('input:checkbox[value="Alum"]').change(function(){
        if (this.checked)
            $(".words:contains('Class Year')").parent().show();
        else 
            $(".words:contains('Class Year')").parent().hide();
    });
});

function hide_field(element)
{
    element = "tr#"+element;
    $(element).hide();
}
function show_field(element)
{
    element = "tr#"+element;
    $(element).show();
}
function animate_field(element)
{
    element = "tr#"+element;
    $(element).animate({
        "height": "toggle"
    }, {
        duration: 0
    });
}