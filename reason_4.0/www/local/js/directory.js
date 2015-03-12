$(document).ready(function() {

    $("#first_nameElement").focusWithoutScrolling();
    hide_all();
    // input_error_check("#departElement");
    // input_error_check("#titleElement");
    // input_error_check("#phone_numberElement");
    // input_error_check("#roomElement");
    // if ($("#search_forElement").val() != "anyone")
    // {
    //     show_all();
    // }
    // if ($("#majorElement").val() != "any")
    // {
    //     show_all();
    // }
    // if ($("#yearElement").val() != "any")
    // {
    //     show_all();
    // }

    var tablesorteropts = {
      theme: 'ice',
      tabIndex: true,
      widthFixed: false,
      sortList: [[1,1]],
      widgets : ["zebra", "columns", "filter", "resizeable"],
      widgetOptions : {
        columns : [ "primary", "secondary", "tertiary" ],
        columns_thead : true,
        filter_childRows : false,
        filter_columnFilters : true,
        filter_cssFilter : "tablesorter-filter",
        filter_formatter : null,
        filter_functions : {
            // '.name' : true,
            '.affiliation' : true,
            // '.email' : true,
            // '.spo' : true,
            // '.phone' : true
        },
        filter_hideFilters : false,
        filter_ignoreCase : true,
        filter_liveSearch : true,
        filter_reset : 'button.reset',
        filter_searchDelay : 300,
        filter_serversideFiltering: false,
        filter_startsWith : false,
        filter_useParsedData : false
      }
    };

    $("#directory").tablesorter(tablesorteropts);
});

$.fn.focusWithoutScrolling = function(){
    var x = window.scrollX, y = window.scrollY;

    // Multiply by 2 to ensure the cursor always ends up at the end;
    // Opera sometimes sees a carriage return as 2 characters.
    var strLength = this.val().length *2;
    this.focus();
    // set cursor to end of text
    this[0].setSelectionRange(strLength, strLength);
    window.scrollTo(x, y);
    return this; //chainability

};

// function show_all(){
//     $("#searchforItem").show();
//     $("#departItem").show();
//     $("#titleItem").show();
//     $("#displayasItem").show();
//     $("#hidecommentItem").show();
//     //table data shown only when logged in
//     $("#phonenumberItem").show();
//     $("#roomItem").show();
//     $("#studentcommentItem").show();
//     $("#majorItem").show();
//     $("#yearItem").show();
//     $("#facultycommentItem").show();
//     //$("#facultycommentItem").animate({"height": "toggle"}, { duration: 1000 });
// }
function hide_all()
{
    $("#searchforItem").hide();
    $("#departItem").hide();
    $("#titleItem").hide();
    $("#displayasItem").hide();
    $("#hidecommentItem").hide();
    //table data hidden only when logged in
    $("#phonenumberItem").hide();
    $("#roomItem").hide();
    $("#studentcommentItem").hide();
    $("#majorItem").hide();
    $("#yearItem").hide();
    $("#facultycommentItem").hide();
}
function toggle_all()
{
    $("#searchforItem").toggle(500);
    $("#departItem").toggle(500);
    $("#titleItem").toggle(500);
    $("#displayasItem").toggle(500);
    $("#hidecommentItem").toggle(500);
    //table data hidden only when logged in
    $("#phonenumberItem").toggle(500);
    $("#roomItem").toggle(500);
    $("#studentcommentItem").toggle(500);
    $("#majorItem").toggle(500);
    $("#yearItem").toggle(500);
    $("#facultycommentItem").toggle(500);
    $("#searchOptions").toggleClass("closedOptions");
    $("#searchOptions").toggleClass("openOptions");
}
// function hide_field(element)
// {
//     element = "tr#"+element;
//     $(element).hide();
// }
// function show_field(element)
// {
//     element = "tr#"+element;
//     $(element).show();
// }
// function animate_field(element)
// {
//     element = "tr#"+element;
//     $(element).animate({
//         "height": "toggle"
//     }, {
//         duration: 0
//     });
// }
// function input_error_check(html_id) {

//     if ($(html_id).val() != "")
//     {
//         show_all();
//     }
// }
