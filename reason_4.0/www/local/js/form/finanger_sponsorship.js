
$(function() {
    needs_payment = false;
    payment_amountElement = $(".words:contains('Payment Amount')").next().find('input');
    payment_amountElement.attr('readonly', 'readonly');

    donation_selector   = $(".words:contains('Additional Donation')");
    tee_green_selector  = $(".words:contains('Please indicate')");
    hide_initial_items();

    same_billing();
    $("#checkbox_same_billing").change(function(){
        same_billing();
    });

    tee_green_selector.next().find('input:radio').change(function(){
        setTotal();
    });

    donation_selector.next().find('input:text').keyup(function(){
        setTotal();
    });
});

function toggle_dining_restrictions(){
    if (alumni_dinner_element.val() >= 1)
    {
        restrictions_selector.parent().show(500);
    } else {
        restrictions_selector.next().find('textarea').val('');
        restrictions_selector.parent().hide(500);
    }
}

function same_billing() {
    if (needs_payment == true) {
        if ($("#checkbox_same_billing").is(':checked')){
            $("#billingstreetaddressItem").hide(500);
            $("#billingcityItem").hide(500);
            $("#billingstateprovinceItem").hide(500);
            $("#billingzipItem").hide(500);
            $("#billingcountryItem").hide(500);
        } else {
            $("#billingstreetaddressItem").show(500);
            $("#billingcityItem").show(500);
            $("#billingstateprovinceItem").show(500);
            $("#billingzipItem").show(500);
            $("#billingcountryItem").show(500);
        }
    }
}

function cleanup_cost(coststring){
    if ((typeof coststring === 'undefined') || (coststring == 0 )){
        return 0;
    } else {
        //look for a dollar sign, return everything after the dollar sign
        i = coststring.indexOf('$');
        return parseInt(coststring.substring(i + 1));
    }
}

function add_costs(){
    tee_green_cost  = 0;
    donation        = 0;
    total           = 0;

    if ( tee_green_selector.next().find('input:radio:checked') ) {
        tee_green_cost = cleanup_cost(tee_green_selector.next().find('input:radio:checked').val());
    }

    if ( donation_selector.next().find('input:text').val() ) {
        donation = parseInt(donation_selector.next().find('input:text').val());
    }
    total = donation + tee_green_cost;
    return total;
}

function setTotal(){
    payment_amountElement.val('$' + add_costs());
    payment_amountElement.effect('highlight');
}

function hide_initial_items(){
    // toggle_billing();
}
