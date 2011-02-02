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
	$("input[name='band_participant']").change(function(){toggle_fields('band_participant','band_instrument')});
        $("input[name='orchestra_participant']").change(function(){toggle_fields('orchestra_participant','orchestra_instrument')});
        $("input[name='jazz_participant']").change(function(){toggle_fields('jazz_participant','jazz_instrument')});
        $("input[name='wind_choir_participant']").change(function(){toggle_fields('wind_choir_participant','wind_choir_instrument')});
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
        trigger = "input#checkbox_"+trigger+":checked";
        element = "tr#"+element.replace(/_/g,"")+"Row";

        var checked = $(trigger).val();

        if (checked){
            $(element).show();
        }else{
            $(element).hide();
        }
}

function checkRequirements(element)
{
        var selected;

        switch(element){
            case 'period_one':
                    selected = $("select#period_oneElement").val();
                    switch(selected){
                        case '1': chooseOrchestra(); break;
                        case '2': chooseBand(); break;
                    }
                    break;
            case 'period_two':
                    selected = $("select#period_twoElement").val();
                    switch(selected){
                        case '1': chooseChoir(); break;
                    }
                    break;
            case 'period_four':
                    selected = $("select#period_four_firstElement").val();
                    switch(selected){
                        case '1': chooseOrchestra(); break;
                    }
                    break;
            case 'period_five':
                    selected = $("select#period_fiveElement").val();
                    switch(selected){
                        case '1': chooseOrchestra(); break;
                        case '2': chooseBand(); break;
                    }
                    break;
            case 'period_six':
                    selected = $("select#period_sixElement").val();
                    switch(selected){
                        case '1': chooseChoir(); break;
                    }
                    break;
        }
}
function chooseOrchestra()
{
    $("#period_oneElement").val('1').attr('selected',true);
    $("#period_four_firstElement").val('1').attr('selected',true);
    $("#period_fiveElement").val('1').attr('selected',true);
}
function chooseBand()
{
    $("#period_oneElement").val('2').attr('selected',true);
    $("#period_four_firstElement").get(0).selectedIndex = 0;
    $("#period_fiveElement").val('2').attr('selected',true);
}
function chooseChoir()
{
    $("#period_twoElement").val('1').attr('selected',true);
    $("#period_sixElement").val('1').attr('selected',true);
}

function chooseLessons()
{
    var id = $("input[@name=testGroup]:checked").attr('id');

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