// Javascript code to for the transcript requests form
//
// @author Steve Smith 2/8/2011

$(document).ready(function() {
    //hide  fields
   // hide_field('official_paper_comment');
    hide_field('official_email');
    hide_field('institution_name');
    hide_field('institution_attn');
    hide_field('institution_email');
    hide_field('address');
    hide_field('city');
    hide_field('state_province');
    hide_field('zip');
    hide_field('country');

    //delivery choices
    showDeliveryInfo();
    $("input[name='delivery_type']").change(function(){
        showDeliveryInfo();
    });
    $("input[name='deliver_to']").change(function(){
        showDeliveryInfo();
    });

    toggle_billing_address();
    $("input[name='billing_address']").change(function(){
        toggle_billing_address()
        });

    // Show/hide and populate Country field based on state/province choice
    $("select#state_provinceElement").change(function(){
        toggle_country_field("select#state_provinceElement","#countryItem" )});

    // Set the initial state for the Country field
    $("select#state_provinceElement").change();
    $("#countryItem").hide();

    var tablesorteropts = {
      theme: 'blue',
      widthFixed: false,
      sortList: [[0,0]],
      widgets : ["zebra", "columns", "filter", "resizeable"],
      widgetOptions : {
        columns : [ "primary", "secondary", "tertiary" ],
        columns_thead : true,
        filter_childRows : false,
        filter_columnFilters : true,
        filter_cssFilter : "tablesorter-filter",
        filter_formatter : null,
        filter_hideFilters : false,
        filter_ignoreCase : true,
        filter_liveSearch : true,
        filter_searchDelay : 300,
        filter_serversideFiltering: false,
        filter_startsWith : false,
        filter_useParsedData : false
      }
    };

    $("#" + table_id).tablesorter(tablesorteropts);
});

function hide_field(element)
{
    element = "#"+element.replace(/_/g,"")+"Item";
    $(element).hide();
}
function show_field(element)
{
    element = "#"+element.replace(/_/g,"")+"Item";
    $(element).show();
}
function showDeliveryInfo()
{
    // if sending paper, display only address fields
   if ($("input[name='delivery_type']:checked").val() == 'postal'
      && $("input[name=deliver_to]:checked").val() == 'Your address')
    {
        $('#official_emailElement').val('');
        hide_field('institution_name');
        hide_field('institution_attn');
        hide_field('official_email');
        show_field('address');
        show_field('city');
        show_field('state_province');
        show_field('zip');
        show_field('country');
    }
    if ($("input[name='delivery_type']:checked").val() == 'email'
      && $("input[name=deliver_to]:checked").val() == 'Your address')
    {
        var email = $('#emailElement').val();
        $('#official_emailElement').val(email);
        hide_field('institution_name');
        hide_field('institution_attn');
        show_field('official_email');
        hide_field('address');
        hide_field('city');
        hide_field('state_province');
        hide_field('zip');
        hide_field('country');
    }
    if ($("input[name='delivery_type']:checked").val() == 'postal'
      && $("input[name=deliver_to]:checked").val() == 'institution')
    {
        $('#official_emailElement').val('');
        show_field('institution_name');
        show_field('institution_attn');
        hide_field('official_email');
        show_field('address');
        show_field('city');
        show_field('state_province');
        show_field('zip');
        show_field('country');
    }
    if ($("input[name='delivery_type']:checked").val() == 'email'
      && $("input[name=deliver_to]:checked").val() == 'institution')
    {
        $('#official_emailElement').val('');
        show_field('institution_name');
        show_field('institution_attn');
        show_field('official_email');
        hide_field('address');
        hide_field('city');
        hide_field('state_province');
        hide_field('zip');
        hide_field('country');
    }
}
function toggle_billing_address() {
    if (!$("input[name='billing_address']:checked").val() ||
        $("input[name='billing_address']:checked").val() == 'entered')
        {
        $("#billingstreetaddressItem").hide();
        $("#billingcityItem").hide();
        $("#billingstateprovinceItem").hide();
        $("#billingzipItem").hide();
        $("#billingcountryItem").hide();
    } else {
        $("#billingstreetaddressItem").show();
        $("#billingcityItem").show();
        $("#billingstateprovinceItem").show();
        $("#billingzipItem").show();
        $("#billingcountryItem").show();
        $("select#billing_state_provinceElement").change();
    }
}
function toggle_country_field(stateElementSelector, countryItemSelector)
{
    // Show/hide and populate Country field based on state/province choice
    // If not US or Canada, show the Country field
    if ($(stateElementSelector).val() == "XX")
    {
        $(countryItemSelector + " select").val('');
        $("#countryItem").show();
        $("#billingcountryItem").show();
    }
    // If US or Canada, populate Country but hide it
    else
    {
        //$(countryItemSelector).hide();
        // If a Canadian province...
        if (/^(?:AB|BC|MB|NB|NL|NT|NS|NU|ON|PE|QC|SK|YT)$/.test($(stateElementSelector).val()))
        $(countryItemSelector + " select").val("CAN");
        // If anything else (other than unset)
        else if ($(stateElementSelector).val() != "")
        $(countryItemSelector + " select").val('USA');
    }
}