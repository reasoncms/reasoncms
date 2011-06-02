$(document).ready(function() {
    var $formSteps = $("div[id='formNavigation'] > ul > li > a");
    $.each($formSteps, function(index, value){
        $formSteps[index].href = '#';
        $(this).click(function(event){
            event.preventDefault();
            var $number;
            switch(index){
                case 0:
                    $number = "One";
                    break;
                case 1:
                    $number = "Two";
                    break;
                case 2:
                    $number = "Three";
                    break;
                case 3:
                    $number = "Four";
                    break;
                case 4:
                    $number = "Five";
                    break;
                case 5:
                    $number = "Six";
                    break;
            }
            $("input[name='__button_ApplicationPage" + $number + "']").click();
        })
    });

    /**All Pages **/
    /**************/
    $('.addButton').button({
        icons: {
            primary:'ui-icon-plusthick',
            secondary:'ui-icon-plusthick'
        }
    });

    $('.removeButton').button({
        icons: {
            primary:'ui-icon-minusthick',
            secondary:'ui-icon-minusthick'
        }
    });

    /**Page One - Enrollment Info **/
    /*******************************/
    $("#transfer_dialog").dialog({
        autoOpen: false,
        show: "blind",
        hide: "blind",
        modal: true
    });
    $("input[name='student_type']").change(function(){
        if ($("#radio_student_type_1").is(":checked")){
            $("#transfer_dialog").dialog("open");
            return false;
        }
    });

    $("#citizenship_dialog").dialog({
        autoOpen: false,
        show: "blind",
        hide: "blind",
        modal: true
    });
    $("input[name='citizenship_status']").change(function(){
        if ($("#radio_citizenship_status_3").is(":checked")){
            $("#citizenship_dialog").dialog("open");
            return false;
        }
    });

    /**Page Two - Personal Info **/
    /*****************************/
    $("#ssn_dialog").dialog({
        autoOpen: false,
        show: "blind",
        hide: "blind",
        modal: true
    });
    $("#ssn").click(function(){
        $("#ssn_dialog").dialog("open");
        return false;
    });
    $("#faith_dialog").dialog({
        autoOpen: false,
        show: "blind",
        hide: "blind",
        modal: true
    });
    $("#faith").click(function(){
        $("#faith_dialog").dialog("open");
        return false;
    });

    

    toggle_mailing_address();
    $("input[name='different_mailing_address']").change(function(){
        toggle_mailing_address();
    });


    /**Page Three - Family**/
    /***********************/
    //--!!!!wtf!!!!--//
    //$('#parent_1_first_nameElement').wtf('First');
	
    // $('#parent_1_first_nameElement').watermark('First');
    
    $("#family_dialog").dialog({
        autoOpen: false,
        show: "blind",
        hide: "blind",
        modal: true
    });
    $("#family").click(function(){
        $("#family_dialog").dialog("open");
        return false;
    });

    toggle_parent_college();
    $("input[name='legacy']").change(function(){
        toggle_parent_college();
    });

    $('#removeSibling').css('display', 'none'); // did this instead of hide because hide was removing some other css from the button
    $('[id^=sibling2]').hide();
    $('[id^=sibling3]').hide();
    $('[id^=sibling4]').hide();
    $('[id^=sibling5]').hide();
    //$("input[name='sibling_1_relation']").change(function(){
    //    add_sibling();
    //});

    $('#addSibling').click(function(){
        add_sibling();
    });
    $('#removeSibling').click(function(){
        remove_sibling();
    });

    /**Page Four - Education**/
    /**************************/

    $('#hs_nameElement').focus();
    $("#hs_nameElement").autocomplete({
        source: "https://reasondev.luther.edu/reason/autocomplete/ceeb.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#hs_addressElement' ).val(ui.item.current_hs_address);
            $( '#hs_cityElement' ).val(ui.item.current_hs_city);
            $( '#hs_state_provinceElement' ).val(ui.item.current_hs_state);
            $( '#hs_zipElement' ).val(ui.item.current_hs_zip);
            $( '#hs_countryElement' ).val(ui.item.current_hs_country);
   	}
    });

    $('#college_1_nameElement').autocomplete({
        source: "https://reasondev.luther.edu/reason/autocomplete/ceeb.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#college_1_addressElement' ).val(ui.item.current_hs_address);
            $( '#college_1_cityElement' ).val(ui.item.current_hs_city);
            $( '#college_1_state_provinceElement' ).val(ui.item.current_hs_state);
            $( '#college_1_zipElement' ).val(ui.item.current_hs_zip);
            $( '#college_1_countryElement' ).val(ui.item.current_hs_country);
   	}
    });

    $('#college_2_nameElement').autocomplete({
        source: "https://reasondev.luther.edu/reason/autocomplete/ceeb.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#college_2_addressElement' ).val(ui.item.current_hs_address);
            $( '#college_2_cityElement' ).val(ui.item.current_hs_city);
            $( '#college_2_state_provinceElement' ).val(ui.item.current_hs_state);
            $( '#college_2_zipElement' ).val(ui.item.current_hs_zip);
            $( '#college_2_countryElement' ).val(ui.item.current_hs_country);
   	}
    });

    $('#college_3_nameElement').autocomplete({
        source: "https://reasondev.luther.edu/reason/autocomplete/ceeb.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#college_3_addressElement' ).val(ui.item.current_hs_address);
            $( '#college_3_cityElement' ).val(ui.item.current_hs_city);
            $( '#college_3_state_provinceElement' ).val(ui.item.current_hs_state);
            $( '#college_3_zipElement' ).val(ui.item.current_hs_zip);
            $( '#college_3_countryElement' ).val(ui.item.current_hs_country);
   	}
    });

    $('#removeCollege').css('display', 'none'); // did this instead of hide because hide was removing some other css from the button
    $('[id^=college2]').hide();
    $('[id^=college3]').hide();

    $('#addCollege').click(function(){
        add_college();
    });

    $('#removeCollege').click(function(){
        remove_college();
    });

    //    $("#state_abbrev").autocomplete({
    //        source: "states_abbrev.php",
    //        minLength: 2
    //    });


    /**Page Five - Activities & Honors**/
    /**********************************/
    toggle_other_activity_details();
    //    $("input[name='band_participant']").change(function(){toggle_fields('band_participant','band_instrument'); });
    $("select[name='activity_1']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_2']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_3']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_4']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_5']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_6']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_7']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_8']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_9']").change(function(){
        toggle_other_activity_details();
    });
    $("select[name='activity_10']").change(function(){
        toggle_other_activity_details();
    });

    /**Page Six - **/
    /**********************************/
    toggle_instrument_info();
    $("input[name='music_audition']").change(function(){
        toggle_instrument_info();
    });

    toggle_conviction_history();
    $("input[name='conviction_history']").change(function(){
        toggle_conviction_history();
    });
    toggle_hs_discipline();
    $("input[name='hs_discipline_history']").change(function(){
        toggle_hs_discipline();
    });
});

var sibling_count = 1;
function add_sibling() {
    sibling_count += 1;
    $('[id^=sibling'+sibling_count+']').show();
    if (sibling_count == 5) {
        $('#addSibling').hide();
    }
    if (sibling_count == 2) {
        $('#removeSibling').show();
    }
}
function remove_sibling() {
    $('[id^=sibling'+sibling_count+']').hide();
    $('[id^=sibling'+sibling_count+']').find('input[type="text"]').each(function() {
        $(this).val("");
    });
    $('[id^=sibling'+sibling_count+']').find('input[type="radio"]').each(function() {
        $(this).attr('checked', false);
    });
    $('[id^=sibling_'+sibling_count+'_state_provinceElement]').val('1');
    $('[id^=sibling_'+sibling_count+'_countryElement]').val('1');
    sibling_count -= 1;
    if (sibling_count == 1) {
        $('#removeSibling').hide();
    }
    if (sibling_count == 4) {
        $('#addSibling').show();
    }
}
var college_count = 1;
function add_college() {
    college_count += 1;
    $('[id^=college'+college_count+']').show();
    if (college_count == 3) {
        $('#addCollege').hide();
    }
    if (college_count == 2) {
        $('#removeCollege').show();
    }
}
function remove_college() {
    $('[id^=college'+college_count+']').hide();
    $('[id^=college'+college_count+']').find('input[type="text"]').each(function() {
        $(this).val("");
    });
    $('[id^=college'+college_count+']').find('input[type="radio"]').each(function() {
        $(this).attr('checked', false);
    });
    $('[id^=college_'+college_count+'_state_provinceElement]').val('1');
    $('[id^=college_'+college_count+'_countryElement]').val('1');
    college_count -= 1;
    if (college_count == 1) {
        $('#removeCollege').hide();
    }
    if (college_count == 2) {
        $('#addCollege').show();
    }
}
function toggle_conviction_history() {
    if ($("input[name='conviction_history']:checked").val() == 'Yes') {
        $("#convictiondetailscommentRow").show();
        $("#convictiondetailsRow").show();
    } else {
        $("#convictiondetailscommentRow").hide();
        $("#convictiondetailsRow").hide();
    }
}
function toggle_hs_discipline() {
    if ($("input[name='hs_discipline_history']:checked").val() == 'Yes') {
        $("#disciplinedetailscommentRow").show();
        $("#hsdisciplinedetailsRow").show();
    } else {
        $("#disciplinedetailscommentRow").hide();
        $("#hsdisciplinedetailsRow").hide();
    }
}
function toggle_instrument_info() {
    if ($("input[name='music_audition']:checked").val() == 'Yes') {
        $("#instrumentcommentRow").show();
        $("#musicauditioninstrumentRow").show();
    } else {
        $("#instrumentcommentRow").hide();
        $("#musicauditioninstrumentRow").hide();
    }
}
function toggle_mailing_address() {
    if ($("input[name='different_mailing_address']:checked").val() == 'Yes') {
        $("#mailingaddressRow").show();
        $("#mailingapartmentnumberRow").show();
        $("#mailingcityRow").show();
        $("#mailingstateprovinceRow").show();
        $("#mailingzippostalRow").show();
        $("#mailingcountryRow").show();
    } else {
        $("#mailingaddressRow").hide();
        $("#mailingapartmentnumberRow").hide();
        $("#mailingcityRow").hide();
        $("#mailingstateprovinceRow").hide();
        $("#mailingzippostalRow").hide();
        $("#mailingcountryRow").hide();
    }
}

function toggle_parent_college() {
    if ($("input[name='legacy']:checked").val() == 'Yes') {
        $("#parent1collegecommentRow").show();
        $("#parent1collegeRow").show();
        $("#parent1collegeaddressRow").show();
        $("#parent1collegecityRow").show();
        $("#parent1collegestateprovinceRow").show();
        $("#parent1collegezippostalRow").show();
        $("#parent1collegecountryRow").show();
        $("#parent2collegecommentRow").show();
        $("#parent2collegeRow").show();
        $("#parent2collegeaddressRow").show();
        $("#parent2collegecityRow").show();
        $("#parent2collegestateprovinceRow").show();
        $("#parent2collegezippostalRow").show();
        $("#parent2collegecountryRow").show();
        $("#guardiancollegecommentRow").show();
        $("#guardiancollegeRow").show();
        $("#guardiancollegeaddressRow").show();
        $("#guardiancollegecityRow").show();
        $("#guardiancollegestateprovinceRow").show();
        $("#guardiancollegezippostalRow").show();
        $("#guardiancollegecountryRow").show();
    } else {
        $("#parent1collegecommentRow").hide();
        $("#parent1collegeRow").hide();
        $("#parent1collegeaddressRow").hide();
        $("#parent1collegecityRow").hide();
        $("#parent1collegestateprovinceRow").hide();
        $("#parent1collegezippostalRow").hide();
        $("#parent1collegecountryRow").hide();
        $("#parent2collegecommentRow").hide();
        $("#parent2collegeRow").hide();
        $("#parent2collegeaddressRow").hide();
        $("#parent2collegecityRow").hide();
        $("#parent2collegestateprovinceRow").hide();
        $("#parent2collegezippostalRow").hide();
        $("#parent2collegecountryRow").hide();
        $("#guardiancollegecommentRow").hide();
        $("#guardiancollegeRow").hide();
        $("#guardiancollegeaddressRow").hide();
        $("#guardiancollegecityRow").hide();
        $("#guardiancollegestateprovinceRow").hide();
        $("#guardiancollegezippostalRow").hide();
        $("#guardiancollegecountryRow").hide();
    }
}
function toggle_other_activity_details() {
    if ($("select[name='activity_1']").val() == 'Other') {
        $("tr#activity1otherRow").show();
    } else {
        $("tr#activity1otherRow").hide();
    }
    if ($("select[name='activity_2']").val() == 'Other') {
        $("tr#activity2otherRow").show();
    } else {
        $("tr#activity2otherRow").hide();
    }
    if ($("select[name='activity_3']").val() == 'Other') {
        $("tr#activity3otherRow").show();
    } else {
        $("tr#activity3otherRow").hide();
    }
    if ($("select[name='activity_4']").val() == 'Other') {
        $("tr#activity4otherRow").show();
    } else {
        $("tr#activity4otherRow").hide();
    }
    if ($("select[name='activity_5']").val() == 'Other') {
        $("tr#activity5otherRow").show();
    } else {
        $("tr#activity5otherRow").hide();
    }
    if ($("select[name='activity_6']").val() == 'Other') {
        $("tr#activity6otherRow").show();
    } else {
        $("tr#activity6otherRow").hide();
    }
    if ($("select[name='activity_7']").val() == 'Other') {
        $("tr#activity7otherRow").show();
    } else {
        $("tr#activity7otherRow").hide();
    }
    if ($("select[name='activity_8']").val() == 'Other') {
        $("tr#activity8otherRow").show();
    } else {
        $("tr#activity8otherRow").hide();
    }
    if ($("select[name='activity_9']").val() == 'Other') {
        $("tr#activity9otherRow").show();
    } else {
        $("tr#activity9otherRow").hide();
    }
    if ($("select[name='activity_10']").val() == 'Other') {
        $("tr#activity10otherRow").show();
    } else {
        $("tr#activity10otherRow").hide();
    }
}
//$.fn.wtf = function(args){
//    var text = args;
//
//    $("#parentmaritalstatuscommentRow").hide();
//    alert(text);
//}