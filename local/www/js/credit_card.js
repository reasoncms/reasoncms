/**
 *
 * Hides credit card fields for forms that offer a no payment options
 *
 * @author Steve Smith
 * @author Ben Wilbur
 * 
 * @todo add features for all credit card payments and add to /usr/local/webapps/reason/reason_package_local/local/minisite_templates/modules/form/views/thor/credit_card_payment.php
 * @todo feature - if multipage disco, autofill credit card and billing fields with previous information (checkbox "Use same address....")
 */


$(document).ready(function() {

    if ($("input[value='No Payment Option']")) {
        hide_credit_card_info();
    }
    
    $(".words:contains('Payment Amount')").next().children().children().children().children().change(function(){        
        var value = $(this+":checked").val();
        if (value.charAt(0) == '$'){
            show_credit_card_info();
        } else {
            hide_credit_card_info();
        }

    });           
});

function hide_credit_card_info(){
    
    $("#paymentnoteRow").css('display', 'none');
    $("#creditcardtypeRow").css('display', 'none');
    $("#creditcardnumberRow").css('display', 'none');
    $("#creditcardexpirationmonthRow").css('display', 'none');
    $("#creditcardexpirationyearRow").css('display', 'none');
    $("#creditcardnameRow").css('display', 'none');
    $("#billingstreetaddressRow").css('display', 'none');
    $("#billingcityRow").css('display', 'none');
    $("#billingstateprovinceRow").css('display', 'none');
    $("#billingzipRow").css('display', 'none');
    $("#billingcountryRow").css('display', 'none');
    
}

function show_credit_card_info(){
    
    $("#paymentnoteRow").show();
    $("#creditcardtypeRow").show();
    $("#creditcardnumberRow").show();
    $("#creditcardexpirationmonthRow").show();
    $("#creditcardexpirationyearRow").show();
    $("#creditcardnameRow").show();
    $("#billingstreetaddressRow").show();
    $("#billingcityRow").show();
    $("#billingstateprovinceRow").show();
    $("#billingzipRow").show();
    $("#billingcountryRow").show();
    
}

    
    

    


