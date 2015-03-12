// Javascript code to for the dorian JH camps form
//
// @author Lucas Welper 1/27/2011
// @author Steve Smith 1.30.2015 modified for responsive

$(document).ready(function() {

    /*************/
    /** PageTwo **/
    /*************/

    //hide & toggle instrument fields
    hide_field('bandinstrument', '0');
    hide_field('orchestrainstrument', '0');
    hide_field('jazzinstrument', '0');
    hide_field('windchoirinstrument', '0');
    hide_field('brasschoirinstrument', '0');
    hide_field('lessoninstrument1', '0');
    hide_field('lessoninstrument2', '0');
    chooseLessons();
    $("input[name='choir_participant']").change(function() {toggle_fields('choir_participant',''); });
    $("input[name='band_participant']").change(function() {toggle_fields('band_participant','bandinstrument'); });
    $("input[name='orchestra_participant']").change(function() {toggle_fields('orchestra_participant','orchestrainstrument'); });
    $("input[name='jazz_participant']").change(function() {toggle_fields('jazz_participant','jazzinstrument'); });
    $("input[name='wind_choir_participant']").change(function() {toggle_fields('wind_choir_participant','windchoirinstrument'); });
    $("input[name='brass_choir_participant']").change(function() {toggle_fields('brass_choir_participant','brasschoirinstrument'); });

    //orchestra requirements
    $("#period_oneElement").blur(function() { checkRequirements('period_one'); });
    $("#period_twoElement").blur(function() { checkRequirements('period_two'); });
    $("#period_four_firstElement").blur(function() { checkRequirements('period_four'); });
    $("#period_fiveElement").blur(function() { checkRequirements('period_five'); });
    $("#period_sixElement").blur(function() { checkRequirements('period_six'); });

    //lesson choices
    $("#radio_private_lessons_0").click(function(){ chooseLessons(); });
    $("#radio_private_lessons_1").click(function(){ chooseLessons(); });
    $("#radio_private_lessons_2").click(function(){ chooseLessons(); });

    toggle_billing_address();

    $("input[name='billing_address']").change(function(){toggle_billing_address(); });
});

function hide_field(element, time)
{
    $('#'+element+'Item').hide(time);
}
function show_field(element, time)
{
    $('#'+element+'Item').show(time);
}

function toggle_fields(trigger, element)
{
    var trigger_element = "input#checkbox_"+trigger+":checked";
    var element_row = "#"+element+"Item";

    var checked = $(trigger_element).val();

    if (checked && element){
        $(element_row).show('500');
    }else{
        $(element_row).hide('500');
    }

    switch(trigger){
        case 'choir_participant':
            if(checked){ chooseChoir(); }else{ clearChoir(); }
            break;
        case 'band_participant':
            if(checked){ chooseBand(); }else{ clearBand(); }
            break;
        case 'orchestra_participant':
            if(checked){ chooseOrchestra(); }else{ clearOrchestra(); }
            break;
        case 'jazz_participant':
            if(checked){ chooseJazz(); }else{ clearJazz(); }
            break;
        case 'wind_choir_participant':
            if(checked){ chooseWoodwindChoir(); }else{ clearWoodwindChoir(); }
            break;
        case 'brass_choir_participant':
            if(checked){ chooseBrassChoir(); }else{ clearBrassChoir(); }
            break;
    }
}
function checkRequirements(element)
{
    var selected;

    switch(element){
        case 'period_one':
                selected = $("select#period_oneElement").val();
                switch(selected){
                    case 'orchestra': chooseOrchestra(); break;
                    case 'concert_band': chooseBand(); break;
                    default: chooseNone('period_one');
                }
                break;
        case 'period_two':
                selected = $("select#period_twoElement").val();
                switch(selected){
                    case 'choir': chooseChoir(); break;
                    default: chooseNone('period_two');
                }
                break;
        case 'period_four':
                selected = $("select#period_four_firstElement").val();
                switch(selected){
                    case 'orchestra': chooseOrchestra(); break;
                    default: chooseNone('period_four');
                }
                break;
        case 'period_five':
                selected = $("select#period_fiveElement").val();
                switch(selected){
                    case 'orchestra': chooseOrchestra(); break;
                    case 'concert_band': chooseBand(); break;
                    default: chooseNone('period_five');
                }
                break;
        case 'period_six':
                selected = $("select#period_sixElement").val();
                switch(selected){
                    case 'choir': chooseChoir(); break;
                    default: chooseNone('period_six');
                }
                break;
    }
}
function chooseOrchestra()
{
    $("#period_oneElement").val('orchestra')/*.attr('selected',true)*/;
    $("#period_four_firstElement").val('orchestra')/*.attr('selected',true)*/;
    $("#period_fiveElement").val('orchestra')/*.attr('selected',true)*/;
}
function clearOrchestra()
{
    if ($("#period_oneElement").val() == 'orchestra') {
        $("#period_oneElement").val('');
    }
    if ($("#period_four_firstElement").val() == 'orchestra') {
        $("#period_four_firstElement").val('');
    }
    if ($("#period_fiveElement").val() == 'orchestra') {
        $("#period_fiveElement").val('');
    }
}
function chooseBand()
{
    $("#period_oneElement").val('concert_band')/*.attr('selected',true)*/;
    if ($("#period_four_firstElement").val() == 'orchestra') {
        $("#period_four_firstElement").val('');
    }
    $("#period_fiveElement").val('concert_band')/*.attr('selected',true)*/;
}
function clearBand()
{
    if ($("#period_oneElement").val() == 'concert_band') {
        $("#period_oneElement").val('');
    }
    if ($("#period_fiveElement").val() == 'concert_band') {
        $("#period_fiveElement").val('');
    }
}
function chooseChoir()
{
    $("#period_twoElement").val('choir')/*.attr('selected',true)*/;
    $("#period_sixElement").val('choir')/*.attr('selected',true)*/;
}
function clearChoir()
{
    if ($("#period_twoElement").val() == 'choir'){
        $("#period_twoElement").val('');
    }
    if ($("#period_sixElement").val() == 'choir'){
        $("#period_sixElement").val('');
    }
}
function chooseJazz()
{
    $("#period_three_firstElement").val('jazz_band_blue')/*.attr('selected',true)*/;
    $("#period_four_firstElement").val('jazz_band_white')/*.attr('selected',true)*/;
}
function clearJazz()
{
    if ($("#period_three_firstElement").val() == 'jazz_band_blue') {
        $("#period_three_firstElement").val('');
    }
    if ($("#period_four_firstElement").val() == 'jazz_band_white') {
        $("#period_four_firstElement").val('');
    }
}
function chooseNone(period)
{
    switch(period){
        case 'period_one':
            if ($("#period_four_firstElement").val() == 'orchestra') {
                $("#period_four_firstElement").val('');
            }
            if ($("#period_fiveElement").val() == 'orchestra' || $("#period_fiveElement").val() == 'concert_band') {
                $("#period_fiveElement").val('');
            }
            break;
        case 'period_two':
            if ($("#period_sixElement").val() == 'choir') {
                $("#period_sixElement").val('');
            }
            break;
        case 'period_four':
            if ($("#period_oneElement").val() == 'orchestra') {
                $("#period_oneElement").val('');
            }
            if ($("#period_fiveElement").val() == 'orchestra' || $("#period_fiveElement").val() == 'concert_band') {
                $("#period_fiveElement").val('');
            }
            break;
        case 'period_five':
            if ($("#period_oneElement").val() == 'orchestra' || $("#period_oneElement").val() == 'concert_band') {
                $("#period_oneElement").val('');
            }
            if ($("#period_four_firstElement").val() == 'orchestra') {
                $("#period_four_firstElement").val('');
            }
            break;
        case 'period_six':
            if ($("#period_twoElement").val() == 'choir') {
                $("#period_twoElement").val('');
            }
            break;
    }
}
function chooseLessons()
{
    var id = $("input[name=private_lessons]:checked").attr('id');

    switch(id){
        case 'radio_private_lessons_0':
            hide_field('lessoninstrument1', '500');
            hide_field('lessoninstrument2', '500');
            break;
        case 'radio_private_lessons_1':
            show_field('lessoninstrument1', '500');
            hide_field('lessoninstrument2', '500');
            break;
        case 'radio_private_lessons_2':
            show_field('lessoninstrument1', '500');
            show_field('lessoninstrument2', '500');
            break;
    }
 }
function chooseWoodwindChoir()
{
    $("#period_three_firstElement").val('woodwind_choir')/*.attr('selected',true)*/;
}
function clearWoodwindChoir()
{
    if ($("#period_three_firstElement").val() == 'woodwind_choir') {
        $("#period_three_firstElement").val('');
    }
}
function chooseBrassChoir()
{
    $("#period_four_firstElement").val('brass_choir')/*.attr('selected',true)*/;
}
function clearBrassChoir()
{
    if ($("#period_four_firstElement").val() == 'brass_choir') {
        $("#period_four_firstElement").val('');
    }
}
function toggle_billing_address()
{
    if (!$("input[name='billing_address']:checked").val() ||
         $("input[name='billing_address']:checked").val() == 'entered')
    {
        $("#billingstreetaddressItem").hide('500');
        $("#billingcityItem").hide('500');
        $("#billingstateprovinceItem").hide('500');
        $("#billingzipItem").hide('500');
        $("#billingcountryItem").hide('500');
    } else {
        $("#billingstreetaddressItem").show('500');
        $("#billingcityItem").show('500');
        $("#billingstateprovinceItem").show('500');
        $("#billingzipItem").show('500');
        $("#billingcountryItem").show('500');
        $("select#billing_state_provinceElement").change();
    }
}
