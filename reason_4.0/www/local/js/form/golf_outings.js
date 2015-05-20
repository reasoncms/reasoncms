$(function() {
    needs_payment = false;
// payment_amountElement = $("#payment_amountElement");
    payment_amountElement = $(".words:contains('Payment Amount')").next().find('input');
    payment_amountElement.attr('readonly', 'readonly');

    golf_registration_selector      = $(".words:contains('Golf Registration')");
    dinner_registration_selector    = $(".words:contains('Dinner Registration')");
    lunch_registration_selector     = $(".words:contains('Lunch Registration')");
    brunch_registration_selector    = $(".words:contains('Brunch Registration')");
    golfer_names_selector           = $(".words:contains('Golfer Name(s)')");
    dinner_names_selector           = $(".words:contains('Dinner Name(s)')");
    lunch_names_selector            = $(".words:contains('Lunch Name(s)')");
    brunch_names_selector           = $(".words:contains('Brunch Name(s)')");

    
    hide_initial_items();

    toggle_golfer_names();
    golf_registration_selector.next().find('input:radio').change(function(){
        toggle_golfer_names();
        set_payment_amount();
    });

    toggle_dinner_names();
    dinner_registration_selector.next().find('input:radio').change(function(){
        toggle_dinner_names();
        set_payment_amount();
    });
    
    toggle_lunch_names();
    lunch_registration_selector.next().find('input:radio').change(function(){
        toggle_lunch_names();
        set_payment_amount();
    });
    
    toggle_brunch_names();
    brunch_registration_selector.next().find('input:radio').change(function(){
        toggle_brunch_names();
        set_payment_amount();
    });

    same_billing();
    $("#checkbox_same_billing").change(function(){
        same_billing();
    });
});

function toggle_golfer_names(){
    if (typeof golf_registration_selector.next().find('input:radio:checked').val() === 'undefined') {
        golfer_names_selector.parent().hide(500);
    } else {
        golfer_names_selector.parent().show(500);
    }
}
function toggle_dinner_names(){
    if (typeof dinner_registration_selector.next().find('input:radio:checked').val() === 'undefined') {
        dinner_names_selector.parent().hide(500);
    } else {
        dinner_names_selector.parent().show(500);
    }
}
function toggle_lunch_names(){
    if (typeof lunch_registration_selector.next().find('input:radio:checked').val() === 'undefined') {
        lunch_names_selector.parent().hide(500);
    } else {
        lunch_names_selector.parent().show(500);
    }
}
function toggle_brunch_names(){
    if (typeof brunch_registration_selector.next().find('input:radio:checked').val() === 'undefined') {
        brunch_names_selector.parent().hide(500);
    } else {
        brunch_names_selector.parent().show(500);
    }
}

function set_payment_amount(){
    if (golf_registration_selector.next().find('input:radio:checked')
        || dinner_registration_selector.next().find('input:radio:checked')
        || lunch_registration_selector.next().find('input:radio:checked')
        || brunch_registration_selector.next().find('input:radio:checked'))
    {
        setTotal();
        needs_payment = true;
    } else {
        payment_amountElement.val("");
    }
}

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

function add(){
    golf_cost       = 0;
    dinner_cost     = 0;
    lunch_cost      = 0;
    brunch_cost     = 0;
    total_cost      = 0;

    if (golf_registration_selector.next().find('input:radio:checked'))
        golf_cost   = cleanup_cost(golf_registration_selector.next().find('input:radio:checked').val());
    if (dinner_registration_selector.next().find('input:radio:checked'))
        dinner_cost   = cleanup_cost(dinner_registration_selector.next().find('input:radio:checked').val());
    if (lunch_registration_selector.next().find('input:radio:checked'))
        lunch_cost   = cleanup_cost(lunch_registration_selector.next().find('input:radio:checked').val());
    if (brunch_registration_selector.next().find('input:radio:checked'))
            brunch_cost   = cleanup_cost(brunch_registration_selector.next().find('input:radio:checked').val());

    total_cost = golf_cost + dinner_cost + lunch_cost + brunch_cost;
    
    return total_cost;
}

function setTotal(){
    payment_amountElement.val('$' + add().toFixed(2));
    payment_amountElement.effect('highlight');
}

function hide_initial_items(){
    toggle_golfer_names('hide');
    toggle_dinner_names('hide');
    toggle_lunch_names('hide');
    toggle_brunch_names('hide');
    set_payment_amount();
}
