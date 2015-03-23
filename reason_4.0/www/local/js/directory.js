$(document).ready(function() {

    $("#first_nameElement").focusWithoutScrolling();
    hide_all();
    input_error_check("#departElement");
    input_error_check("#titleElement");
    input_error_check("#phone_numberElement");
    input_error_check("#roomElement");
    if ($("#search_forElement").val() != "anyone") {
        show_all();
    }
    if ($("#majorElement").length && $("#majorElement").val() != "any") {
        show_all();
    }
    if ($("#yearElement").length && $("#yearElement").val() != "any") {
        show_all();
    }
    clearOptions();

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
            '.affiliation' : true,
            '.year': true,
        },
        filter_hideFilters : false,
        filter_ignoreCase : true,
        filter_liveSearch : true,
        filter_reset : '.filterReset',
        filter_searchDelay : 300,
        filter_serversideFiltering: false,
        filter_startsWith : false,
        filter_useParsedData : false
      }
    };

    $("#directory").tablesorter(tablesorteropts);
});

$.fn.focusWithoutScrolling = function() {
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

function show_all() {
    $("#searchforItem").show();
    $("#departItem").show();
    $("#titleItem").show();
    $("#displayasItem").show();
    $("#hidecommentItem").show();
    //table data shown only when logged in
    $("#phonenumberItem").show();
    $("#roomItem").show();
    $("#studentcommentItem").show();
    $("#majorItem").show();
    $("#yearItem").show();
    $("#facultycommentItem").show();
    //$("#facultycommentItem").animate({"height": "toggle"}, { duration: 1000 });
}
function hide_all() {
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
function toggle_all() {
    $("#searchforItem").toggle();
    $("#departItem").toggle();
    $("#titleItem").toggle();
    $("#displayasItem").toggle();
    $("#hidecommentItem").toggle();
    //table data hidden only when logged in
    $("#phonenumberItem").toggle();
    $("#roomItem").toggle();
    $("#studentcommentItem").toggle();
    $("#majorItem").toggle();
    $("#yearItem").toggle();
    $("#facultycommentItem").toggle();
    clearOptions();
}
function clearOptions()
{
    if ($("#searchforItem").is(':visible')) {
        $(".searchOptions").addClass("openOptions").removeClass("closedOptions");
        $(".openOptions").text('Clear Options');
    } else {
        $(".searchOptions").addClass("closedOptions").removeClass("openOptions");
        $(".closedOptions").text('More Options');
        $("#search_forElement").val('anyone');
        $("#departElement").val('');
        $("#titleElement").val('');
        $("#display_asElement").val('book');
        $("#phone_numberElement").val('');
        $("#roomElement").val('');
        $("#majorElement").val('any');
        $("#yearElement").val('any');
    }
}
function input_error_check(html_id) {
    if ( $(html_id).length && $(html_id).val() ) {
        show_all();
    }
}

function createEmailLink() {
    $(".emailLink").each(function(){
        link = $(this).html();
        link = link.replace('<i class="fa fa-at"></i>', '@');
        $(this).attr('href','mailto:'+link);
    });
}
