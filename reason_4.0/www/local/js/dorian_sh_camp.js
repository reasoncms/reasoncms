// Javascript code to for the dorian JH camps form
//
// @author Lucas Welper 1/27/2011

$(document).ready(function() {

    /*************/
    /** PageTwo **/
    /*************/

    //hide & toggle instrument fields
    hide_field('bandinstrument', '0');
    hide_field('orchestrainstrument', '0');
    hide_field('jazzinstrument', '0');
    hide_field('windchoirinstrument', '0');
    // hide_field('brasschoirinstrument', '0');
    hide_field('lessoninstrument1', '0');
    hide_field('lessoninstrument2', '0');
    chooseLessons();
    $("input[name='choir_participant']").change(function(){toggle_fields('choir_participant',''); });
    $("input[name='band_participant']").change(function(){toggle_fields('band_participant','bandinstrument'); });
    $("input[name='orchestra_participant']").change(function(){toggle_fields('orchestra_participant','orchestrainstrument'); });
    $("input[name='jazz_participant']").change(function(){toggle_fields('jazz_participant','jazzinstrument'); });
    $("input[name='wind_choir_participant']").change(function(){toggle_fields('wind_choir_participant','windchoirinstrument'); });
    // $("input[name='brass_choir_participant']").change(function(){toggle_fields('brass_choir_participant','brass_choir_instrument')});

    //orchestra requirements
    $("#period_oneElement").blur(function() { checkRequirements('period_one'); });
    $("#period_twoElement").blur(function() { checkRequirements('period_two'); });
    $("#period_three_firstElement").blur(function() { checkRequirements('period_three'); });
    $("#period_four_firstElement").blur(function() { checkRequirements('period_four'); });
    $("#period_fiveElement").blur(function() { checkRequirements('period_five'); });
    $("#period_sixElement").blur(function() { checkRequirements('period_six'); });

    //lesson choices
    $("#radio_private_lessons_0").click(function(){chooseLessons();});
    $("#radio_private_lessons_1").click(function(){chooseLessons();});
    $("#radio_private_lessons_2").click(function(){chooseLessons();});

    //choose workshops
    $("#radio_workshops_0").click(function(){chooseWorkshops();});
    $("#radio_workshops_1").click(function(){chooseWorkshops();});
    $("#radio_workshops_2").click(function(){chooseWorkshops();});
    $("#radio_workshops_3").click(function(){chooseWorkshops();});
    $("#radio_workshops_4").click(function(){chooseWorkshops();});

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
            if (checked){ chooseChoir(); }else{ clearChoir(); }
            break;
        case 'band_participant':
            if (checked){ chooseBand(); }else{ clearBand(); }
            break;
        case 'orchestra_participant':
            if (checked){ chooseOrchestra(); }else{ clearOrchestra(); }
            break;
        case 'jazz_participant':
            if (checked){ chooseJazz(); }else{ clearJazz(); }
            break;
        case 'wind_choir_participant':
            if (checked){ chooseWoodwindChoir(); }else{ clearWoodwindChoir(); }
            break;
        // case 'brass_choir_participant':
        //     if (checked){ chooseBrassChoir(); }else{ clearBrassChoir(); }
        //     break;
    }
}
function chooseWorkshops()
{
    var id = $("input[name=workshops]:checked").attr('id');

    switch(id){
        case 'radio_workshops_0':
            chooseMusicTheatre();
            clearActingCompany();
            clearKeyboardWorkshop();
            clearHarpWorkshop();
            break;
        case 'radio_workshops_1':
            chooseActingCompany();
            clearKeyboardWorkshop();
            clearMusicTheatre();
            clearHarpWorkshop();
            break;
        case 'radio_workshops_2':
            chooseKeyboardWorkshop();
            clearActingCompany();
            clearMusicTheatre();
            clearHarpWorkshop();
            break;
        case 'radio_workshops_3':
            chooseHarpWorkshop();
            clearActingCompany();
            clearMusicTheatre();
            clearKeyboardWorkshop();
            break;
        case 'radio_workshops_4':
            clearHarpWorkshop();
            clearActingCompany();
            clearMusicTheatre();
            clearKeyboardWorkshop();
            break;
    }
 }
function checkRequirements(element)
{
    var selected;

    switch(element){
        case 'period_one':
                selected = $("select#period_oneElement").val();
                chooseNone('period_one');
                switch(selected){
                    case 'acting_company': chooseActingCompany(); break;
                    case 'orchestra': chooseOrchestra(); break;
                    case 'concert_band': chooseBand(); break;
                    case 'music_theatre': chooseMusicTheatre(); break;
                    case 'keyboard_workshop': chooseKeyboardWorkshop(); break;
                    // case 'mixed_media': chooseMixedMedia(); break;
                }
                break;
        case 'period_two':
                selected = $("select#period_twoElement").val();
                chooseNone('period_two');
                switch(selected){
                    case 'music_theatre': chooseMusicTheatre(); break;
                    case 'choir': chooseChoir(); break;
                }
                break;
        case 'period_three':
                selected = $("select#period_three_firstElement").val();
                chooseNone('period_three');
                switch(selected){
                    case 'acting_company': chooseActingCompany(); break;
                    case 'guitar_workshop': chooseGuitarWorkshop(); break;
                    case 'harp_workshop': chooseHarpWorkshop(); break;
                }
                break;
        case 'period_four':
                selected = $("select#period_four_firstElement").val();
                chooseNone('period_four');
                switch(selected){
                    case 'acting_company': chooseActingCompany(); break;
                    case 'music_theatre': chooseMusicTheatre(); break;
                    case 'keyboard_workshop': chooseKeyboardWorkshop(); break;
                    case 'orchestra': chooseOrchestra(); break;
                    case 'guitar_workshop': chooseGuitarWorkshop(); break;
                }
                break;
        case 'period_five':
                selected = $("select#period_fiveElement").val();
                chooseNone('period_five');
                switch(selected){
                    case 'acting_company': chooseActingCompany(); break;
                    case 'music_theatre': chooseMusicTheatre(); break;
                    case 'keyboard_workshop': chooseKeyboardWorkshop(); break;
                    case 'orchestra': chooseOrchestra(); break;
                    case 'concert_band': chooseBand(); break;
                    case 'harp_workshop': chooseHarpWorkshop(); break;
                    // case 'mixed_media': chooseMixedMedia(); break;
                }
                break;
        case 'period_six':
                selected = $("select#period_sixElement").val();
                chooseNone('period_six');
                switch(selected){
                    case 'music_theatre': chooseMusicTheatre(); break;
                    case 'choir': chooseChoir(); break;
                }
                break;
    }
}
function chooseOrchestra()
{
    $("#period_oneElement").val('orchestra');
    $("#period_four_firstElement").val('orchestra');
    $("#period_fiveElement").val('orchestra');
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
    $("#period_oneElement").val('concert_band');
    if ($("#period_four_firstElement").val() =='orchestra') {
        $("#period_four_firstElement").val('');
    }
    $("#period_fiveElement").val('concert_band');
}
function clearBand()
{
    if ($("#period_oneElement").val() == 'concert_band') {
        $("#period_oneElement").get(0).selectedIndex = 0;
    }
    if ($("#period_fiveElement").val() == 'concert_band') {
        $("#period_fiveElement").get(0).selectedIndex = 0;
    }
}
function chooseChoir()
{
    $("#period_twoElement").val('choir');
    $("#period_sixElement").val('choir');
}
function clearChoir()
{
    if ($("#period_twoElement").val() == 'choir') {
        $("#period_twoElement").val('');
    }
    if ($("#period_sixElement").val() == 'choir') {
        $("#period_sixElement").val('');
    }
}
function chooseJazz()
{
    $("#period_three_firstElement").val('jazz_band');
}
function clearJazz()
{
    if ($("#period_three_firstElement").val() =='jazz_band') {
        $("#period_three_firstElement").val('');
    }
}
function chooseNone(period)
{
    switch(period){
        case 'period_one':
            if ($("#period_twoElement").val() == 'music_theatre') {
                $("#period_twoElement").val('');
            }
            if ($("#period_four_firstElement").val() == 'music_theatre' || $("#period_four_firstElement").val() == 'keyboard_workshop'|| $("#period_four_firstElement").val() == 'orchestra') {
                    $("#period_four_firstElement").val('');
            }
            if ($("#period_fiveElement").val() == 'music_theatre' || $("#period_fiveElement").val() == 'keyboard_workshop' || $("#period_fiveElement").val() == 'orchestra') {
                    $("#period_fiveElement").val('');
            }
            if ($("#period_sixElement").val() == 'music_theatre') {
                $("#period_sixElement").val('');
            }
            break;
        case 'period_two':
            if ($("#period_oneElement").val() == 'music_theatre') {
                $("#period_oneElement").val('');
            }
            if ($("#period_four_firstElement").val() == 'music_theatre') {
                    $("#period_four_firstElement").val('');
            }
            if ($("#period_fiveElement").val() == 'music_theatre') {
                    $("#period_fiveElement").val('');
            }
            if ($("#period_sixElement").val() == 'music_theatre' || $("#period_sixElement").val() == 'choir') {
                $("#period_sixElement").val('');
            }
            break;
        case 'period_three':
            if ($("#period_four_firstElement").val() == 'guitar_workshop') {
                    $("#period_four_firstElement").val('');
            }
            if ($("#period_fiveElement").val() == 'harp_workshop') {
                    $("#period_fiveElement").val('');
            }
            break;
        case 'period_four':
            if ($("#period_oneElement").val() == 'music_theatre' || $("#period_oneElement").val() == 'keyboard_workshop' || $("#period_oneElement").val() == 'orchestra') {
                    $("#period_oneElement").val('');
            }
            if ($("#period_twoElement").val() == 'music_theatre') {
                    $("#period_twoElement").val('');
            }
            if ($("#period_threeElement").val() == 'guitar_workshop') {
                    $("#period_threeElement").val('');
            }
            if ($("#period_fiveElement").val() == 'music_theatre' || $("#period_fiveElement").val() == 'keyboard_workshop') {
                    $("#period_fiveElement").val('');
            }
            if ($("#period_sixElement").val() == 'music_theatre') {
                $("#period_sixElement").val('');
            }
            break;
        case 'period_five':
            if ($("#period_oneElement").val() == 'music_theatre' || $("#period_oneElement").val() == 'keyboard_workshop' || $("#period_oneElement").val() == 'concert_band' || $("#period_oneElement").val() == 'harp_workshop' || $("#period_oneElement").val() == 'orchestra' || $("#period_oneElement").val() == 'acting_company') {
                    $("#period_oneElement").val('');
            }
            if ($("#period_twoElement").val() == 'music_theatre') {
                $("#period_twoElement").val('');
            }
            if ($("#period_threeElement").val() == 'acting_company' || $("#period_threeElement").val() == 'guitar_workshop' || $("#period_threeElement").val() == 'harp_workshop') {
                    $("#period_threeElement").val('');
            }
            if ($("#period_four_firstElement").val() == 'music_theatre' || $("#period_four_firstElement").val() == 'keyboard_workshop' || $("#period_four_firstElement").val() == 'orchestra' || $("#period_four_firstElement").val() == 'guitar_workshop' || $("#period_four_firstElement").val() == 'acting_company') {
                    $("#period_four_firstElement").val('');
            }
            if ($("#period_sixElement").val() == 'music_theatre') {
                $("#period_sixElement").val('');
            }
            break;
        case 'period_six':
            if ($("#period_oneElement option[value='music_theatre']").attr('selected')){
                $("#period_oneElement").get(0).selectedIndex = 0;
            }
            if ($("#period_twoElement option[value='music_theatre']").attr('selected') || $("#period_twoElement option[value='choir']").attr('selected')){
                $("#period_twoElement").get(0).selectedIndex = 0;
            }
            if ($("#period_four_firstElement option[value='music_theatre']").attr('selected')){
                $("#period_four_firstElement").get(0).selectedIndex = 0;
            }
            if ($("#period_fiveElement option[value='music_theatre']").attr('selected')){
                $("#period_fiveElement").get(0).selectedIndex = 0;
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
    $("#period_three_firstElement").val('woodwind_choir');
}
function clearWoodwindChoir()
{
    if ($("#period_three_firstElement").val() == 'woodwind_choir') {
        $("#period_three_firstElement").val('');
    }
}
function chooseActingCompany()
{
    $("#period_oneElement").val('acting_company');
    $("#period_three_firstElement").val('acting_company');
    $("#period_four_firstElement").val('acting_company');
    $("#period_fiveElement").val('acting_company');
}
function clearActingCompany()
{
    if ($("#period_oneElement").val() == 'acting_company') {
        $("#period_oneElement").val('');
    }
    if ($("#period_three_firstElement").val() == 'acting_company') {
        $("#period_three_firstElement").val('');
    }
    if ($("#period_four_firstElement").val() == 'acting_company') {
        $("#period_four_firstElement").val('');
    }
    if ($("#period_fiveElement").val() == 'acting_company') {
        $("#period_fiveElement").val('');
    }
}
function chooseMusicTheatre()
{
    $("#period_oneElement").val('music_theatre');
    $("#period_twoElement").val('music_theatre');
    $("#period_four_firstElement").val('music_theatre');
    $("#period_fiveElement").val('music_theatre');
    $("#period_sixElement").val('music_theatre');
}
function clearMusicTheatre()
{
    if ($("#period_oneElement").val() == 'music_theatre') {
        $("#period_oneElement").val('');
    }
    if ($("#period_twoElement").val() == 'music_theatre') {
        $("#period_twoElement").val('');
    }
    if ($("#period_four_firstElement").val() == 'music_theatre') {
        $("#period_four_firstElement").val('');
    }
    if ($("#period_fiveElement").val() == 'music_theatre') {
        $("#period_fiveElement").val('');
    }
    if ($("#period_sixElement").val() == 'music_theatre') {
        $("#period_sixElement").val('');
    }
}
function chooseKeyboardWorkshop()
{
    $("#period_oneElement").val('keyboard_workshop');
    $("#period_four_firstElement").val('keyboard_workshop');
    $("#period_fiveElement").val('keyboard_workshop');
}
function clearKeyboardWorkshop()
{
    if ($("#period_oneElement").val() == 'keyboard_workshop') {
        $("#period_oneElement").val('');
    }
    if ($("#period_four_firstElement").val() == 'keyboard_workshop') {
        $("#period_four_firstElement").val('');
    }
    if ($("#period_fiveElement").val() == 'keyboard_workshop') {
        $("#period_fiveElement").val('');
    }
}
function chooseGuitarWorkshop()
{
    $("#period_three_firstElement").val('guitar_workshop');
    $("#period_four_firstElement").val('guitar_workshop');
}
function clearGuitarWorkshop()
{
    if ($("#period_three_firstElement").val() == 'guitar_workshop') {
        $("#period_three_firstElement").val('');
    }
    if ($("#period_four_firstElement").val() == 'guitar_workshop') {
        $("#period_four_firstElement").val('');
    }
}
function chooseHarpWorkshop()
{
    $("#period_three_firstElement").val('harp_workshop');
    $("#period_fiveElement").val('harp_workshop');
}
function clearHarpWorkshop()
{
    if ($("#period_three_firstElement").val() == 'harp_workshop') {
        $("#period_three_firstElement").val('');
    }
    if ($("#period_fiveElement").val() == 'harp_workshop') {
        $("#period_fiveElement").val('');
    }
}
function toggle_billing_address()
{
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