/*
 *
 * Requires the following to have been included in the html:
 *
 * jquery
 * jquery maskedinput plugin
 * jquery ui + css
 *
 */

//document ready
$(function(){
    $("#PU_P1_ZIP").mask("99999?-9999");
    $("#PU_P2_ZIP").mask("99999?-9999");
    $("#PU_OC_ZIP").mask("99999?-9999");
    $("#LABELID_VAR8").parent().removeClass("label");
    $("#PU_OC_END_DATE").datepicker({dateFormat: "dd/mm/yyyy"});
    $("#PU_OC_START_DATE").datepicker({dateFormat: "dd/mm/yyyy"});
})