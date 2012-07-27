$(document).ready(function() {
    var $formStepLinks = $("div[id*='formNavigation'] > ul > li > a");
    $.each($formStepLinks, function(index, value){
        $formStepLinks[index].href = '#';
        $(this).click(function(event){
            event.preventDefault();
            var $number;
            switch(index % 6){
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
    var url = document.URL;
    if ( url.match( /(PageOne)/) ){
        $("input[name='__button_ApplicationPageOne']").hide();
        $("input[name='__button_ApplicationPageTwo']").show().val('Next');
        $("input[name='__button_ApplicationPageThree']").hide();
        $("input[name='__button_ApplicationPageFour']").hide();
        $("input[name='__button_ApplicationPageFive']").hide();
        $("input[name='__button_ApplicationPageSix']").hide();
        $("input[name='__button_ApplicationConfirmation']").hide();
    } else if ( url.match( /(PageTwo)/ ) ){
        $("input[name='__button_ApplicationPageOne']").show().val('Previous');
        $("input[name='__button_ApplicationPageTwo']").hide();
        $("input[name='__button_ApplicationPageThree']").show().val('Next');
        $("input[name='__button_ApplicationPageFour']").hide();
        $("input[name='__button_ApplicationPageFive']").hide();
        $("input[name='__button_ApplicationPageSix']").hide();
        $("input[name='__button_ApplicationConfirmation']").hide();
    } else if ( url.match( /(PageThree)/ ) ){
        $("input[name='__button_ApplicationPageOne']").hide();
        $("input[name='__button_ApplicationPageTwo']").show().val('Previous');
        $("input[name='__button_ApplicationPageThree']").hide();
        $("input[name='__button_ApplicationPageFour']").show().val('Next');
        $("input[name='__button_ApplicationPageFive']").hide();
        $("input[name='__button_ApplicationPageSix']").hide();
        $("input[name='__button_ApplicationConfirmation']").hide();
    } else if ( url.match( /(PageFour)/ ) ){
        $("input[name='__button_ApplicationPageOne']").hide();
        $("input[name='__button_ApplicationPageTwo']").hide();
        $("input[name='__button_ApplicationPageThree']").show().val('Previous');
        $("input[name='__button_ApplicationPageFour']").hide();
        $("input[name='__button_ApplicationPageFive']").show().val('Next');
        $("input[name='__button_ApplicationPageSix']").hide();
        $("input[name='__button_ApplicationConfirmation']").hide();
    } else if ( url.match( /(PageFive)/ ) ){
        $("input[name='__button_ApplicationPageOne']").hide();
        $("input[name='__button_ApplicationPageTwo']").hide();
        $("input[name='__button_ApplicationPageThree']").hide();
        $("input[name='__button_ApplicationPageFour']").show().val('Previous');
        $("input[name='__button_ApplicationPageFive']").hide();
        $("input[name='__button_ApplicationPageSix']").show().val('Next');
        $("input[name='__button_ApplicationConfirmation']").hide();
    } else if ( url.match( /(PageSix)/ ) ){
        $("input[name='__button_ApplicationPageOne']").hide();
        $("input[name='__button_ApplicationPageTwo']").hide();
        $("input[name='__button_ApplicationPageThree']").hide();
        $("input[name='__button_ApplicationPageFour']").hide();
        $("input[name='__button_ApplicationPageFive']").show();
        $("input[name='__button_ApplicationPageFive']").val('Previous');
        $("input[name='__button_ApplicationPageSix']").hide();
        $("input[name='__button_ApplicationConfirmation']").show();
    }else{
        $("#discoSubmitRow").hide();
    }


    /**All Pages **/
    /**************/
    $("input[name*='first_name']").watermark('First');
    $("input[name*='middle_name']").watermark('Middle');
    $("input[name*='last_name']").watermark('Last');
    $("input[name*='preferred_first_name']").watermark('Nickname');
    $("input[name*='age']").watermark('Age');
    $("input[name*='grade']").watermark('Grade');
    $("input[name*='suffix']").watermark('Suffix');

    /* logout & save button */
    var logout_button = $("<input class='saveAndLogout' type='button' value='Save & Logout' style='float:right;' />");
    logout_button.click(function(event){
        event.preventDefault();
        $("input[name*='logout']").val(true);
        $("input[name='__button_ApplicationPageOne']").click();
    });
    var $logout_fields = $("input[name*='logout']");
    var $logout_test = 0;
    $.each($logout_fields, function(index, value) {
        $logout_test = 1;
    });
    if($logout_test == 1){
        $(".page-title").before(logout_button);
    }

    /* OpenId switch accounts dialog box */
    $("#openid_dialog").dialog({
        autoOpen: false,
        show: "blind",
        hide: "blind",
        modal: true
    });
    $("a[name='missing_info']").click(function(){
        $("#openid_dialog").dialog("open");
        return false;
    });


    var suffixes = [
        "D.C.",
        "D.D.S.",
        "D.N.",
        "D.O.",
        "D.V.M.",
        "II",
        "III",
        "IV",
        "V",
        "Jr.",
        "M.D.",
        "Ph.D.",
        "Sr.",
        "USAF (Ret.)"
    ];
    $("input[name*='suffix']").autocomplete({
        source: suffixes
    });

    $('.saveAndLogout').button({
        icons: {
            primary:'ui-icon-plusthick',
            secondary:'ui-icon-plusthick'
        }
    });
    
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
    $("#date_of_birth-mm").watermark('mm');
    $("#date_of_birth-dd").watermark('dd');
    $("#date_of_birth").watermark('yyyy');

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
    $("#ssn_1Element").mask("999");
    $("#ssn_2Element").mask("99");
    $("#ssn_3Element").mask("9999");
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
    $("#permanent_home_parentElement").change(function(){
       $("input[name='__button_ApplicationPageThree']").click();
    });
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

    toggle_parent_2_address();
    $("input[name='parent_2_address_same']").change(function(){
        toggle_parent_2_address();
    });

    toggle_parent_college();
    $("input[name='legacy']").change(function(){
        toggle_parent_college();
    });
    //autocomplete Parent's College
    $('#parent_1_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#parent_1_college_ceebElement').val(ui.item.id);
   	}
    });
    $('#parent_2_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#parent_2_college_ceebElement').val(ui.item.id);
   	}
    });
    $('#guardian_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#guardian_college_ceebElement').val(ui.item.id);
   	}
    });

    perculate_siblings_up();

    $('#removeSibling').css('display', 'none'); // did this instead of hide because hide was removing some other css from the button
    for (i=2; i<=5; i += 1)
    {
        if (has_data('[id^=sibling'+i+']')) {
            sibling_count += 1;
        } else {
            $('[id^=sibling'+i+']').hide();
        }
        if (sibling_count == 5) {
            $('#addSibling').hide();
        }
        if (sibling_count == 2) {
            $('#removeSibling').show();
        }
    }
    //autocomplete Sibling's College'
    $('#sibling_1_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#sibling_1_college_ceebElement').val(ui.item.id);
   	}
    });
    $('#sibling_2_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#sibling_2_college_ceebElement').val(ui.item.id);
   	}
    });
    $('#sibling_3_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#sibling_3_college_ceebElement').val(ui.item.id);
   	}
    });
    $('#sibling_4_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#sibling_4_college_ceebElement').val(ui.item.id);
   	}
    });
    $('#sibling_5_collegeElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui ) {
            $('#sibling_5_college_ceebElement').val(ui.item.id);
   	}
    });
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
        source: "/reason/autocomplete/ceeb.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#hs_ceebElement' ).val(ui.item.id);
   	}
    });

    $('#college_1_nameElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#college_1_ceebElement' ).val(ui.item.id);
   	}
    });

    $('#college_2_nameElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#college_2_ceebElement' ).val(ui.item.id);
   	}
    });

    $('#college_3_nameElement').autocomplete({
        source: "/reason/autocomplete/ceeb_college.php",
        minLength: 3,
        select: function( event, ui )
        {
            $( '#college_3_ceebElement' ).val(ui.item.id);
   	}
    });

    perculate_college_up();
    $('#removeCollege').css('display', 'none'); // did this instead of hide because hide was removing some other css from the button$('#removeCollege').css('display', 'none'); // did this instead of hide because hide was removing some other css from the button
    for (i=2; i<=3; i += 1)
    {
        if (college_has_data('#college_'+i+'_nameElement')) {
            college_count += 1;
        } else {
            $('[id^=college'+i+']').hide();
        }
        if (college_count == 3) {
            $('#addCollege').hide();
        }
        if (college_count == 2) {
            $('#removeCollege').show();
        }
    }
    

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
    
    perculate_activity_up();
    
    $('#removeActivity').css('display', 'none'); // did this instead of hide because hide was removing some other css from the button
    for (i=2; i<=10; i += 1)
    {
        if (activity_has_data('#activity_'+i+'Element')) {
            activity_count += 1;
        } else {
            $('[id^=activity'+i+']').hide();
        }
        if (activity_count == 10) {
            $('#addActivity').hide();
        }
        if (activity_count == 2) {
            $('#removeActivity').show();
        }
    }
    

    $('#addActivity').click(function(){
        add_activity();
    });

    $('#removeActivity').click(function(){
        remove_activity();
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
    $("input[name='hs_discipline']").change(function(){
        toggle_hs_discipline();
    });
});
function perculate_siblings_up() {
    // moves siblings up if the applicant has enterred blank siblings between
    //  non blank siblings
    blank_q = [];
    for (i=1; i<=5; i += 1)
    {
        if (has_data('[id^=sibling'+i+']') && blank_q[0] != null) {
            move_sibling_data(i, blank_q.shift());
            blank_q.push(i);
        } else if (! has_data('[id^=sibling'+i+']')) {
            blank_q.push(i);
        }
    }
}

function move_sibling_data(from_sibling, to_sibling) {
    // move sibling data from from_sibling to to_sibling and clear out the from_sibling
    $('#sibling_'+to_sibling+'_first_nameElement').val($('#sibling_'+from_sibling+'_first_nameElement').val());
    $('#sibling_'+from_sibling+'_first_nameElement').val('');
    $('#sibling_'+to_sibling+'_last_nameElement').val($('#sibling_'+from_sibling+'_last_nameElement').val());
    $('#sibling_'+from_sibling+'_last_nameElement').val('');
    $('#sibling_'+to_sibling+'_ageElement').val($('#sibling_'+from_sibling+'_ageElement').val());
    $('#sibling_'+from_sibling+'_ageElement').val('');
    $('#sibling_'+to_sibling+'_gradeElement').val($('#sibling_'+from_sibling+'_gradeElement').val());
    $('#sibling_'+from_sibling+'_gradeElement').val('');
    $('#sibling_'+to_sibling+'_collegeElement').val($('#sibling_'+from_sibling+'_collegeElement').val());
    $('#sibling_'+from_sibling+'_collegeElement').val('');
    $('#radio_sibling_'+to_sibling+'_relation_0').attr('checked', $('#radio_sibling_'+from_sibling+'_relation_0').attr('checked'));
    $('#radio_sibling_'+from_sibling+'_relation_0').attr('checked', false);
    $('#radio_sibling_'+to_sibling+'_relation_1').attr('checked', $('#radio_sibling_'+from_sibling+'_relation_1').attr('checked'));
    $('#radio_sibling_'+from_sibling+'_relation_1').attr('checked', false);
}

function perculate_activity_up() {
    // moves activity up if the applicant has enterred blank activity between
    //  non blank activity
    blank_q = [];
    for (i=1; i<=10; i += 1)
    {
//        alert(activity_has_data('#activity_'+i+'Element'));
        if (activity_has_data('#activity_'+i+'Element') && blank_q[0] != null) {
            move_activity_data(i, blank_q.shift());
            blank_q.push(i);
        } else if (! activity_has_data('#activity_'+i+'Element')) {
//            alert('pushing');
            blank_q.push(i);
        } 
    }
}

function move_activity_data(from_activity, to_activity) {
    // move sibling data from from_activity to to_activity and clear out the from_activity
    $('#activity_'+to_activity+'Element').val($('#activity_'+from_activity+'Element').val());
    $('#activity_'+from_activity+'Element').val('1');
    $('#activity_'+to_activity+'_otherElement').val($('#activity_'+from_activity+'_otherElement').val());
    $('#activity_'+from_activity+'_otherElement').val('');
    $('#activity_'+to_activity+'_honorsElement').val($('#activity_'+from_activity+'_honorsElement').val());
    $('#activity_'+from_activity+'_honorsElement').val('');
    $('#checkbox_activity_'+to_activity+'_participation_0').attr('checked', $('#checkbox_activity_'+from_activity+'_participation_0').attr('checked'));
    $('#checkbox_activity_'+from_activity+'_participation_0').attr('checked', false);
    $('#checkbox_activity_'+to_activity+'_participation_1').attr('checked', $('#checkbox_activity_'+from_activity+'_participation_1').attr('checked'));
    $('#checkbox_activity_'+from_activity+'_participation_1').attr('checked', false);
    $('#checkbox_activity_'+to_activity+'_participation_2').attr('checked', $('#checkbox_activity_'+from_activity+'_participation_2').attr('checked'));
    $('#checkbox_activity_'+from_activity+'_participation_2').attr('checked', false);
    $('#checkbox_activity_'+to_activity+'_participation_3').attr('checked', $('#checkbox_activity_'+from_activity+'_participation_3').attr('checked'));
    $('#checkbox_activity_'+from_activity+'_participation_3').attr('checked', false);
    $('#checkbox_activity_'+to_activity+'_participation_4').attr('checked', $('#checkbox_activity_'+from_activity+'_participation_4').attr('checked'));
    $('#checkbox_activity_'+from_activity+'_participation_4').attr('checked', false);
}

function perculate_college_up() {
    // moves siblings up if the applicant has enterred blank siblings between
    //  non blank siblings
    blank_q = [];
    for (i=1; i<=3; i += 1)
    {
        if (college_has_data('#college_'+i+'_nameElement') && blank_q[0] != null) {
            move_college_data(i, blank_q.shift());
            blank_q.push(i);
        } else if (! college_has_data('#college_'+i+'_nameElement')) {
            blank_q.push(i);
        }
    }
}

function move_college_data(from_college, to_college) {
    // move sibling data from from_sibling to to_sibling and clear out the from_sibling
    $('#college_'+to_college+'_nameElement').val($('#college_'+from_college+'_nameElement').val());
    $('#college_'+from_college+'_nameElement').val('');
}

function college_has_data(selector) {
    if ($(selector).val() != '') {
        return true;
    }
    return false;
}

function has_data(selector) {
//    alert(selector);
    return_val = false;
    $(selector).find(':text').each( function() {
        //alert($(this).val());
        if ($(this).val() != '') {
            return_val = true;
            return false;
        }
    });
    return return_val;
}

function activity_has_data(selector) {
    if ($(selector).val() != '') {
        return true;
    }
    return false;
}


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

var activity_count = 1;
function add_activity() {
    activity_count += 1;
    $('[id^=activity'+activity_count+']:not([id*=other])').show();
    if (activity_count == 10) {
        $('#addActivity').hide();
    }
    if (activity_count == 2) {
        $('#removeActivity').show();
    }
}
function remove_activity() {
    $('[id^=activity'+activity_count+']').hide();
    $('[id^=activity'+activity_count+']').find('input[type="text"]').each(function() {
        $(this).val("");
    });
    $('[id^=activity'+activity_count+']').find('input[type="radio"],input[type="checkbox"]').each(function() {
        $(this).attr('checked', false);
    });
    $('[id^=activity_'+activity_count+'Element]').val('1');
    activity_count -= 1;
    if (activity_count == 1) {  
        $('#removeActivity').hide();
    }
    if (activity_count == 9) {
        $('#addActivity').show();
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
        $("#convictionhistorydetailsRow").show();
    } else {
        $("#convictiondetailscommentRow").hide();
        $("#convictionhistorydetailsRow").hide();
    }
}
function toggle_hs_discipline() {
    if ($("input[name='hs_discipline']:checked").val() == 'Yes') {
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
        $("#mailingaddressheaderRow").show();
        $("#mailingaddressRow").show();
        $("#mailingaddress2Row").show();
        $("#mailingapartmentnumberRow").show();
        $("#mailingcityRow").show();
        $("#mailingstateprovinceRow").show();
        $("#mailingzippostalRow").show();
        $("#mailingcountryRow").show();
    } else {
        $("#mailingaddressheaderRow").hide();
        $("#mailingaddressRow").hide();
        $("#mailingaddress2Row").hide();
        $("#mailingapartmentnumberRow").hide();
        $("#mailingcityRow").hide();
        $("#mailingstateprovinceRow").hide();
        $("#mailingzippostalRow").hide();
        $("#mailingcountryRow").hide();
    }
}
function toggle_parent_2_address() {
    if ($("input[name='parent_2_address_same']:checked").val() == 'no') {
        $("#parent2addressRow").show();
        $("#parent2address2Row").show();
        $("#parent2apartmentnumberRow").show();
        $("#parent2cityRow").show();
        $("#parent2stateprovinceRow").show();
        $("#parent2zippostalRow").show();
        $("#parent2countryRow").show();
        $("#parent2phonegroupRow").show();
    } else {
        $("#parent2addressRow").hide();
        $("#parent2address2Row").hide();
        $("#parent2apartmentnumberRow").hide();
        $("#parent2cityRow").hide();
        $("#parent2stateprovinceRow").hide();
        $("#parent2zippostalRow").hide();
        $("#parent2countryRow").hide();
        $("#parent2phonegroupRow").hide();
    }
}
function toggle_parent_college() {
    if ($("input[name='legacy']:checked").val() == 'Yes') {
        $("#parent1collegecommentRow").show();
        $("#parent1collegeRow").show();
        $("#parent2collegecommentRow").show();
        $("#parent2collegeRow").show();
        $("#guardiancollegecommentRow").show();
        $("#guardiancollegeRow").show();
    } else {
        $("#parent1collegecommentRow").hide();
        $("#parent1collegeRow").hide();
        $("#parent2collegecommentRow").hide();
        $("#parent2collegeRow").hide();
        $("#guardiancollegecommentRow").hide();
        $("#guardiancollegeRow").hide();
    }
}
function toggle_other_activity_details() {
    if (($("select[name='activity_1']").val() == 'Other') || ($("select[name='activity_1']").val() == 'PROGV')) {
        $("tr#activity1otherRow").show();
    } else {
        $("tr#activity1otherRow").hide();
    }
    if (($("select[name='activity_2']").val() == 'Other') || ($("select[name='activity_2']").val() == 'PROGV')) {
        $("tr#activity2otherRow").show();
    } else {
        $("tr#activity2otherRow").hide();
    }
    if (($("select[name='activity_3']").val() == 'Other') || ($("select[name='activity_3']").val() == 'PROGV')) {
        $("tr#activity3otherRow").show();
    } else {
        $("tr#activity3otherRow").hide();
    }
    if (($("select[name='activity_4']").val() == 'Other') || ($("select[name='activity_4']").val() == 'PROGV')) {
        $("tr#activity4otherRow").show();
    } else {
        $("tr#activity4otherRow").hide();
    }
    if (($("select[name='activity_5']").val() == 'Other') || ($("select[name='activity_5']").val() == 'PROGV')) {
        $("tr#activity5otherRow").show();
    } else {
        $("tr#activity5otherRow").hide();
    }
    if (($("select[name='activity_6']").val() == 'Other') || ($("select[name='activity_6']").val() == 'PROGV')) {
        $("tr#activity6otherRow").show();
    } else {
        $("tr#activity6otherRow").hide();
    }
    if (($("select[name='activity_7']").val() == 'Other') || ($("select[name='activity_7']").val() == 'PROGV')) {
        $("tr#activity7otherRow").show();
    } else {
        $("tr#activity7otherRow").hide();
    }
    if (($("select[name='activity_8']").val() == 'Other') || ($("select[name='activity_8']").val() == 'PROGV')) {
        $("tr#activity8otherRow").show();
    } else {
        $("tr#activity8otherRow").hide();
    }
    if (($("select[name='activity_9']").val() == 'Other') || ($("select[name='activity_9']").val() == 'PROGV')) {
        $("tr#activity9otherRow").show();
    } else {
        $("tr#activity9otherRow").hide();
    }
    if (($("select[name='activity_10']").val() == 'Other') || ($("select[name='activity_10']").val() == 'PROGV')) {
        $("tr#activity10otherRow").show();
    } else {
        $("tr#activity10otherRow").hide();
    }
}
