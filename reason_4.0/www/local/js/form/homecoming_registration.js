$(document).ready(function(){
    
    $("#payment_amountElement").attr('readonly', 'readonly');
    
    hide_initial_items();

    toggle_reunion_reservations();
    $(".words:contains('Reunion Class Year')").next().find('select').change(function(){
        toggle_reunion_reservations();
    });

    toggle_dining_restrictions();
    $(".words:contains('Friday's')").next().find('select').change(function(){
        toggle_dining_restrictions();
        toggle_billing();
    });
    
    toggle_guest_information();
    $(".words:contains('Spouse/Guest Name')").next().find('input').blur(function(){
        toggle_guest_information();
    });

    toggle_guest_class_year();
    $(".words:contains('Attended Luther')").next().find('input:radio').change(function(){
        toggle_guest_class_year();
    });

    same_billing();
    $("#checkbox_same_billing").change(function(){
        same_billing();
    });

    $(".words:contains('Reunion Dinner/Reception')").next().find('select').change(function(){
        toggle_billing();
    });

    $(".words:contains('50th Reunion Booklet')").next().find('select').change(function(){
        toggle_billing();
    });
});

var humph = 1;
function toggle_50_year_options(show_or_hide){
    if (show_or_hide == 'show')
    {
        $("#50yearreunionheaderItem").show(500);
        $(".words:contains('50th Reunion Luncheon')").parent().show(500);
        $(".words:contains('Ride in Parade')").parent().show(500);
        $(".words:contains('50th Reunion Booklet')").parent().show(500);
    } else {
        $("#50yearreunionheaderItem").hide(500);
        $(".words:contains('50th Reunion Luncheon')").parent().hide(500);
        $(".words:contains('Ride in Parade')").parent().hide(500);
        $(".words:contains('50th Reunion Booklet')").parent().hide(500);
    }
}

function toggle_billing(){
    if (($(".words:contains('Friday's')").next().find('select').val() >= 1)
        || ($(".words:contains('Reunion Dinner/Reception')").next().find('select').val() >= 1)
        || ($(".words:contains('50th Reunion Booklet')").next().find('select').val() >= 1))
    {
        // $(".words:contains('Payment Amount')").parent().hide(500);
        $("#hrItem").show(500);
        $("#paymentnoteItem").show(500);
        $("#paymentamountItem").show(500);
        $("#creditcardtypeItem").show(500);
        $("#creditcardnumberItem").show(500);
        $("#creditcardexpirationmonthItem").show(500);
        $("#creditcardexpirationyearItem").show(500);
        $("#creditcardnameItem").show(500);
        $("#billingstreetaddressItem").show(500);
        $("#billingcityItem").show(500);
        $("#billingstateprovinceItem").show(500);
        $("#billingzipItem").show(500);
        $("#billingcountryItem").show(500);
        setTotal();
    } else {
        $("#hrItem").hide(500);
        $("#paymentnoteItem").hide(500);
        $("#paymentamountItem").hide(500);
        $("#creditcardtypeItem").hide(500);
        $("#creditcardnumberItem").hide(500);
        $("#creditcardexpirationmonthItem").hide(500);
        $("#creditcardexpirationyearItem").hide(500);
        $("#creditcardnameItem").hide(500);
        $("#billingstreetaddressItem").hide(500);
        $("#billingcityItem").hide(500);
        $("#billingstateprovinceItem").hide(500);
        $("#billingzipItem").hide(500);
        $("#billingcountryItem").hide(500);
    }
}

function toggle_dining_restrictions(){
    if ($(".words:contains('Friday's')").next().find('select').val() >= 1)
    {
        $(".words:contains('dining restrictions')").parent().show(500);
    } else {
        $(".words:contains('dining restrictions')").next().find('textarea').val('');
        $(".words:contains('dining restrictions')").parent().hide(500);
    }
}

function toggle_guest_information(){
    if (!$(".words:contains('Spouse/Guest Name')").next().find('input').val())
    {
        $(".words:contains('Attended Luther')").parent().hide(500);
    } else {
        $(".words:contains('Attended Luther')").parent().show(500);        
    }
}

function toggle_guest_class_year(){
    if ($(".words:contains('Attended Luther')").next().find('input:radio:checked').val() == "Yes"){
        $(".words:contains('Guest Class Year')").parent().show(500);
    } else {
        $(".words:contains('Guest Class Year')").parent().hide(500);
    }
}

function toggle_reunion_reservations(){
    var year = (new Date).getFullYear();
    var classyear = $(".words:contains('Class Year')").next().find('select').val();
    var reunion = year - classyear;
    var reunion_reservations_element = $(".words:contains('Reunion Dinner/Reception')").parent();
    var luncheon_element = $(".words:contains('Saturday Luncheon')").parent();
    var seventieth_lunch_element = $(".words:contains('70th Reunion Dinner')").parent();

    var five_year_cost = $(".words:contains('5 year')").next().find('input').val();

    if (reunion % 5 == 0) 
    {
        $("#classreunionreservationsheaderItem").show(500);
    } else {
        $("#classreunionreservationsheaderItem").hide(500);
    }
    switch (reunion) 
    {
        case 75:
            luncheon_element.show(500);
            reunion_reservations_element.hide(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 70:
            luncheon_element.show(500);
            reunion_reservations_element.hide(500);
            seventieth_lunch_element.show(500);
            toggle_50_year_options('hide');
            break;
        case 65:
            luncheon_element.show(500);
            reunion_reservations_element.hide(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 60:
            luncheon_element.show(500);
            reunion_reservations_element.hide(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 55:
            luncheon_element.show(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 50:
            luncheon_element.show(500);
            reunion_reservations_element.show(500);
            toggle_50_year_options('show');
            seventieth_lunch_element.hide(500);
            break;
        case 45:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 40:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 35:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 30:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 25:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 20:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 15:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 10:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        case 5:
            luncheon_element.hide(500);
            reunion_reservations_element.show(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            break;
        default:
            luncheon_element.hide(500);
            reunion_reservations_element.hide(500);
            seventieth_lunch_element.hide(500);
            toggle_50_year_options('hide');
            // toggle_billing('hide');
    }
}

function same_billing() {
    if ($("#checkbox_same_billing").is(':checked')){
        alert(add());
        $("#billingstreetaddressItem").hide(500);
        $("#billingcityItem").hide(500);
        $("#billingstateprovinceItem").hide(500);
        $("#billingzipItem").hide(500);
        $("#billingcountryItem").hide(500);
        // $("#billingstreetaddressItem").find('textarea').val('');
    } else {
        $("#billingstreetaddressItem").show(500);
        $("#billingcityItem").show(500);
        $("#billingstateprovinceItem").show(500);
        $("#billingzipItem").show(500);
        $("#billingcountryItem").show(500);
    }
}

function cleanup_cost(coststring){
    return parseInt(coststring.replace("$",""));
}

function add(){
    var alumni_dinner_cost = 0;
    var alumni_dinner_quantity = 0;
    var alumni_dinner_cost = cleanup_cost($(".words:contains('Alumni Dinner Cost')").next().find('input').val());
    var alumni_dinner_quantity = parseInt($(".words:contains('Friday\'s')").next().find('select').val());
    
    var reunion_dinner_cost = 0;
    var reunion_dinner_quantity = 0;
    // var reunion_dinner_cost = cleanup_cost($(".words:contains('Reunion Dinner Cost')").next().find('input').val());
    var reunion_dinner_cost = 15;
    var reunion_dinner_quantity = parseInt($(".words:contains('Reunion Dinner/Reception')").next().find('select').val());

    var booklet_cost = 0;
    var booklet_quantity = 0;
    booklet_cost = cleanup_cost($(".words:contains('Booklet cost')").next().find('input').val());
    booklet_quantity = parseInt($(".words:contains('50th Reunion Booklet')").next().find('select').val());

    var total_cost = 0;
    total_cost = (alumni_dinner_cost * alumni_dinner_quantity) + (booklet_cost * booklet_quantity) + (reunion_dinner_cost * reunion_dinner_quantity);
    return total_cost;
}

function setTotal(){
    payment_amountElement.val('$' + add().toFixed(2));
    payment_amountElement.effect( 'highlight');
}

function hide_initial_items(){
    toggle_50_year_options('hide');
    $(".words:contains('Dining Restrictions')").parent().hide();
    $(".words:contains('Attended Luther')").parent().hide();
    $(".words:contains('Guest Class Year')").parent().hide();
    $("#classreunionreservationsheaderItem").hide();
    // $(".words:contains('Reunion Reservations')").parent().hide();
    $(".words:contains('Saturday Luncheon')").parent().hide();
    $(".words:contains('70th Reunion Dinner')").parent().hide();
    $(".words:contains('Reunion Dinner/Reception')").parent().hide();
    $(".words:contains('Payment Amount')").parent().hide();
    toggle_billing();
    hide_prices();
}

function hide_prices(){
    $(".words:contains('cost')").parent().find('input').attr('readonly', 'readonly');
    $(".words:contains('cost')").parent().hide();
}
