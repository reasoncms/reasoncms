
$(function() {
    needs_payment = false;
    payment_amountElement = $(".words:contains('Payment Amount')").next().find('input');
    payment_amountElement.attr('readonly', 'readonly');

    donation_selector   = $(".words:contains('Additional Donation')");
    tee_green_selector  = $(".words:contains('Please indicate')");
    show_hide_signage_textareas();

    same_billing();
    $("#checkbox_same_billing").change(function(){
        same_billing();
    });

    tee_green_selector.next().find('input:radio').change(function(){
        show_hide_signage_textareas();
        setTotal();
    });

    donation_selector.next().find('input:text').keyup(function(){
        setTotal();
    });
});

function same_billing() {
        if ($("#checkbox_same_billing").is(':checked')){
            address = $(".words:contains('Street Address')").next().find('textarea').val();
            city = $(".words:contains('City')").next().find('input:text').val();
            state = $(".words:contains('State')").next().find('select').val();

            $("#billingstreetaddressItem").find('textarea').val(address);
            $("#billingcityItem").find('input:text').val(city);
            $("#billingstateprovinceItem").find('select').val(state);
            $("#billingstreetaddressItem").hide(500);
            $("#billingcityItem").hide(500);
            $("#billingstateprovinceItem").hide(500);
            $("#billingzipItem").hide(500);
            $("#billingcountryItem").hide(500);
        } else {
            $("#billingstreetaddressItem").find('textarea').val('');
            $("#billingcityItem").find('input:text').val('');
            $("#billingstateprovinceItem").find('select').val('');
            $("#billingstreetaddressItem").show(500);
            $("#billingcityItem").show(500);
            $("#billingstateprovinceItem").show(500);
            $("#billingzipItem").show(500);
            $("#billingcountryItem").show(500);
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
        donation = cleanup_cost(donation_selector.next().find('input:text').val());
    }
    total = donation + tee_green_cost;
    return total;
}

function setTotal(){
    payment_amountElement.val('$' + add_costs());
    payment_amountElement.effect('highlight');
}

function show_hide_signage_textareas(){
    tee_green_quant = tee_green_selector.next().find('input:radio:checked').val();
    if ( typeof tee_green_quant !== 'undefined' ) {
        switch(tee_green_quant) {
            case 'One - $100':
                $(".words:contains('Text for Tee/Green Sponsorship 1')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 2')").parent().hide('500');
                $(".words:contains('Text for Tee/Green Sponsorship 3')").parent().hide('500');
                $(".words:contains('Text for Tee/Green Sponsorship 4')").parent().hide('500');
                $(".words:contains('Text for Tee/Green Sponsorship 5')").parent().hide('500');
                break;
            case 'Two - $200':
                $(".words:contains('Text for Tee/Green Sponsorship 1')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 2')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 3')").parent().hide('500');
                $(".words:contains('Text for Tee/Green Sponsorship 4')").parent().hide('500');
                $(".words:contains('Text for Tee/Green Sponsorship 5')").parent().hide('500');
                break;
            case 'Three - $300':
                $(".words:contains('Text for Tee/Green Sponsorship 1')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 2')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 3')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 4')").parent().hide('500');
                $(".words:contains('Text for Tee/Green Sponsorship 5')").parent().hide('500');
                break;
            case 'Four - $400':
                $(".words:contains('Text for Tee/Green Sponsorship 1')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 2')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 3')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 4')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 5')").parent().hide('500');
                break;
            case 'Five - $500':
                $(".words:contains('Text for Tee/Green Sponsorship 1')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 2')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 3')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 4')").parent().show('500');
                $(".words:contains('Text for Tee/Green Sponsorship 5')").parent().show('500');
                break;
        }
    } else {
        $(".words:contains('Text for Tee/Green Sponsorship')").parent().hide();
    }
}

// function hide_initial_items(){
//     // toggle_billing();
// }
