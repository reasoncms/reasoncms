// Javascript code to for the dorian JH camps form
//
// @author Lucas Welper 1/27/2011

$(document).ready(function() {

        /*************/
	/** PageTwo **/
        /*************/

        //hide & toggle instrument fields
        hide_field('band_instrument');
        hide_field('orchestra_instrument');
        hide_field('jazz_instrument');
        hide_field('wind_choir_instrument');
        hide_field('brass_choir_instrument');
        hide_field('lesson_instrument_1');
        hide_field('lesson_instrument_2');
	$("input[name='band_participant']").change(function(){toggle_fields('band_participant','band_instrument'); });
        $("input[name='orchestra_participant']").change(function(){toggle_fields('orchestra_participant','orchestra_instrument'); });
        $("input[name='jazz_participant']").change(function(){toggle_fields('jazz_participant','jazz_instrument')});
        $("input[name='wind_choir_participant']").change(function(){toggle_fields('wind_choir_participant','wind_choir_instrument'); });
        $("input[name='brass_choir_participant']").change(function(){toggle_fields('brass_choir_participant','brass_choir_instrument')});

        //orchestra requirements
        $("#period_oneElement").blur(function() { checkRequirements('period_one'); });
        $("#period_twoElement").blur(function() { checkRequirements('period_two'); });
        $("#period_four_firstElement").blur(function() { checkRequirements('period_four'); });
        $("#period_fiveElement").blur(function() { checkRequirements('period_five'); });
        $("#period_sixElement").blur(function() { checkRequirements('period_six'); });

        //lesson choices
        $("#radio_private_lessons_0").click(function(){chooseLessons();});
        $("#radio_private_lessons_1").click(function(){chooseLessons();});
        $("#radio_private_lessons_2").click(function(){chooseLessons();});

        toggle_billing_address();

	$("input[name='billing_address']").change(function(){toggle_billing_address()});
});

function hide_field(element)
{
        element = "tr#"+element.replace(/_/g,"")+"Row";
        $(element).hide();
}
function show_field(element)
{
        element = "tr#"+element.replace(/_/g,"")+"Row";
        $(element).show();
}

function toggle_fields(trigger, element)
{
        var trigger_element = "input#checkbox_"+trigger+":checked";
        var element_row = "tr#"+element.replace(/_/g,"")+"Row";

        var checked = $(trigger_element).val();

        if (checked){
            $(element_row).show();
        }else{
            $(element_row).hide();
        }

        switch(trigger){
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
        $("#period_oneElement").val('orchestra').attr('selected',true);
        $("#period_four_firstElement").val('orchestra').attr('selected',true);
        $("#period_fiveElement").val('orchestra').attr('selected',true);
}
function clearOrchestra()
{
        if($("#period_oneElement option[value='orchestra']").attr('selected')){
            $("#period_oneElement").get(0).selectedIndex = 0;
        }
        if($("#period_four_firstElement option[value='orchestra']").attr('selected')){
            $("#period_four_firstElement").get(0).selectedIndex = 0;
        }
        if($("#period_fiveElement option[value='orchestra']").attr('selected')){
            $("#period_fiveElement").get(0).selectedIndex = 0;
        }
}
function chooseBand()
{
        $("#period_oneElement").val('concert_band').attr('selected',true);
        if($("#period_four_firstElement option[value='orchestra']").attr('selected')){
            $("#period_four_firstElement").get(0).selectedIndex = 0;
        }
        $("#period_fiveElement").val('concert_band').attr('selected',true);
}
function clearBand()
{
        if($("#period_oneElement option[value='concert_band']").attr('selected')){
            $("#period_oneElement").get(0).selectedIndex = 0;
        }
        if($("#period_fiveElement option[value='concert_band']").attr('selected')){
            $("#period_fiveElement").get(0).selectedIndex = 0;
        }
}
function chooseChoir()
{
    $("#period_twoElement").val('choir').attr('selected',true);
    $("#period_sixElement").val('choir').attr('selected',true);
}
function clearChoir()
{
        if($("#period_twoElement option[value='choir']").attr('selected')){
            $("#period_twoElement").get(0).selectedIndex = 0;
        }
        if($("#period_sixElement option[value='choir']").attr('selected')){
            $("#period_sixElement").get(0).selectedIndex = 0;
        }
}
function chooseJazz()
{
    $("#period_three_firstElement").val('jazz_band_blue').attr('selected',true);
    $("#period_four_firstElement").val('jazz_band_white').attr('selected',true);
}
function clearJazz()
{
        if($("#period_three_firstElement option[value='jazz_band_blue']").attr('selected')){
            $("#period_three_firstElement").get(0).selectedIndex = 0;
        }
        if($("#period_four_firstElement option[value='jazz_band_white']").attr('selected')){
            $("#period_four_firstElement").get(0).selectedIndex = 0;
        }
}
function chooseNone(period)
{
    switch(period){
        case 'period_one':
            if($("#period_four_firstElement option[value='orchestra']").attr('selected')){
                $("#period_four_firstElement").get(0).selectedIndex = 0;
            }
            if($("#period_fiveElement option[value='orchestra']").attr('selected') || $("#period_fiveElement option[value='concert_band']").attr('selected')){
                $("#period_fiveElement").get(0).selectedIndex = 0;
            }
            break;
        case 'period_two':
            if($("#period_sixElement option[value='choir']").attr('selected')){
                $("#period_sixElement").get(0).selectedIndex = 0;
            }
            break;
        case 'period_four':
            if($("#period_oneElement option[value='orchestra']").attr('selected')){
                $("#period_oneElement").get(0).selectedIndex = 0;
            }
            if($("#period_fiveElement option[value='orchestra']").attr('selected') || $("#period_fiveElement option[value='concert_band']").attr('selected')){
                $("#period_fiveElement").get(0).selectedIndex = 0;
            }
            break;
        case 'period_five':
            if($("#period_oneElement option[value='orchestra']").attr('selected') || $("#period_oneElement option[value='concert_band']").attr('selected')){
                $("#period_oneElement").get(0).selectedIndex = 0;
            }
            if($("#period_four_firstElement option[value='orchestra']").attr('selected')){
                $("#period_four_firstElement").get(0).selectedIndex = 0;
            }
            break;
        case 'period_six':
            if($("#period_twoElement option[value='choir']").attr('selected')){
                $("#period_twoElement").get(0).selectedIndex = 0;
            }
            break;
    }
}
function chooseLessons()
{
    var id = $("input[name=testGroup]:checked").attr('id');

    switch(id){
        case 'radio_private_lessons_0':
            hide_field('lesson_instrument_1');
            hide_field('lesson_instrument_2');
            break;
        case 'radio_private_lessons_1':
            show_field('lesson_instrument_1');
            hide_field('lesson_instrument_2');
            break;
        case 'radio_private_lessons_2':
            show_field('lesson_instrument_1');
            show_field('lesson_instrument_2');
            break;
    }
 }
 function chooseWoodwindChoir()
    {
        $("#period_three_firstElement").val('woodwind_choir').attr('selected',true);
    }
    function clearWoodwindChoir()
    {
            if($("#period_three_firstElement option[value='woodwind_choir']").attr('selected')){
                $("#period_three_firstElement").get(0).selectedIndex = 0;
            }
    }
    function chooseBrassChoir()
    {
        $("#period_four_firstElement").val('brass_choir').attr('selected',true);
    }
    function clearBrassChoir()
    {
            if($("#period_four_firstElement option[value='brass_choir']").attr('selected')){
                $("#period_four_firstElement").get(0).selectedIndex = 0;
            }
    }
    function toggle_billing_address()
{
	if (!$("input[name='billing_address']:checked").val() ||
	     $("input[name='billing_address']:checked").val() == 'entered')
	{
		$("tr#billingstreetaddressRow").hide();
		$("tr#billingcityRow").hide();
		$("tr#billingstateprovinceRow").hide();
		$("tr#billingzipRow").hide();
		$("tr#billingcountryRow").hide();
	} else {
		$("tr#billingstreetaddressRow").show();
		$("tr#billingcityRow").show();
		$("tr#billingstateprovinceRow").show();
		$("tr#billingzipRow").show();
		$("tr#billingcountryRow").show();
		$("select#billing_state_provinceElement").change();
	}
}