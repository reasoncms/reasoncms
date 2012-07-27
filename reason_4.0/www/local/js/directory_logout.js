$(document).ready(function() {
    $("#directory").tablesorter();
    
    $("#first_nameElement").focus();
    hide_all();
    input_error_check("#departElement");
    input_error_check("#titleElement");
    if ($("#search_forElement").val() != "anyone")
    {
        show_all();
    }
});
function show_all(){
    $("#searchforRow").show();
    $("#departRow").show();
    $("#titleRow").show();
    $("#displayasRow").show();
    $("#hidecommentRow").show();
    //table data shown only when logged in
    //$("#phonenumberRow").show();
    //$("#roomRow").show();
    //$("#studentcommentRow").show();
    //$("#majorRow").show();
    //$("#yearRow").show();
    //$("#facultycommentRow").show();
    //$("#facultycommentRow").animate({"height": "toggle"}, { duration: 1000 });
}
function hide_all()
{
    $("#searchforRow").hide();
    $("#departRow").hide();
    $("#titleRow").hide();
    $("#displayasRow").hide();
    $("#hidecommentRow").hide();
    //table data hiden only when logged in
    //$("#phonenumberRow").hide();
    //$("#roomRow").hide();
    //$("#studentcommentRow").hide();
    //$("#majorRow").hide();
    //$("#yearRow").hide();
    //$("#facultycommentRow").hide();
}
function toggle_all()
{
    $("#searchforRow").toggle();
    $("#departRow").toggle();
    $("#titleRow").toggle();
    $("#displayasRow").toggle();
    $("#hidecommentRow").toggle();
    //table data hiden only when logged in
    //$("#phonenumberRow").toggle();
    //$("#roomRow").toggle();
    //$("#studentcommentRow").toggle();
    //$("#majorRow").toggle();
    //$("#yearRow").toggle();
    //$("#facultycommentRow").toggle();
}
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
function input_error_check(html_id) {

    if ($(html_id).val() != "")
    {
        show_all();
    }
}
