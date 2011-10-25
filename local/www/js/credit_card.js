

$(document).ready(function() {
    $(".words:contains('Payment Amount')").sibling().each(function(){
        alert(this.html());
    });
    
    $(".words:contains('Payment Amount')").change(function()
    {
        // If "Yes" is checked
        if ($("#radio_transfer_0").is(":checked"))
        {
            //show "transfer college" element
            $("#transfercollegeRow").show();
        }
        else
        {
            //otherwise, hide it
            $("#transfercollegeRow").hide();
        }
    })
           
});

function toggle_credit_card_info(){
    
    if ($("input[value='No Payment Option']")) {
        alert('yo');
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
    
}

    
    

    


