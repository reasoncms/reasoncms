
$(function() {
    needs_payment = false;
    payment_amountElement = $(".words:contains('Payment Amount')").next().find('input');
    payment_amountElement.attr('readonly', 'readonly');

    grouping_1_element  = $(".words:contains('Pairing/Foursome 1')").parent();
    division_1_element  = $(".words:contains('Division for Pairing/Foursome 1')").parent();
    grouping_2_element  = $(".words:contains('Pairing/Foursome 2')").parent();
    division_2_element  = $(".words:contains('Division for Pairing/Foursome 2')").parent();

    draw_buttons();
    toggle_remove_golfer_link();
    hide_initial_items();

    $(".words:contains('Please select one')").next().find('input:radio').change(function(){
        setTotal();
    });
});

function draw_buttons() {
        $("#guest8wrapperItem").after('<hr>');
        $("#guest8wrapperItem").after('<a id="add_golfer_link" href="#" class="button" onclick="add_golfer(); return false;"><i class="fa fa-plus"></i> Add another golfer/guest</a>');
        $("#guest8wrapperItem").after('<p><a id="remove_golfer_link" href="#" onclick="remove_golfer(); return false;"><i class="fa fa-minus"></i> Remove golfer/guest</a></p>');
        $(".words:contains('Other Comments/Notes')").parent().before('<a id="add_parirng_link" href="#" class="default-button" onclick="add_pairing(); return false;"><i class="fa fa-plus"></i> Add another pairing/foursome</a>');
        $(".words:contains('Other Comments/Notes')").parent().before('<a id="remove_pairing_link" href="#" onclick="remove_pairing(); return false;"><i class="fa fa-minus"></i> Remove pairing/foursome</a>');
        $(".words:contains('Other Comments/Notes')").parent().before('<hr>');
}

function add_golfer(){
    $("div[id*='wrapperItem']:hidden:first").show('500');
    toggle_remove_golfer_link();
}

function toggle_remove_golfer_link() {
    if ($("#guest2wrapperItem").is(":visible")) {
        $("#remove_golfer_link").show('500');
        division_2_element.show('500');
        grouping_2_element.show('500');
    } else {
        $("#remove_golfer_link").hide('500');
        grouping_2_element.hide('500');
        division_2_element.hide('500');
    }
}

function remove_golfer(){
    // hide the last open golfer/guest fields and reset values
    lastGolfer = $("div[id*='wrapperItem']:not(:hidden):last");
    lastGolfer.hide();
    lastGolfer.contents().find("input").val('');
    lastGolfer.contents().find("select").val('');
    lastGolfer.contents().find("input:radio").attr('checked', false);
    lastGolfer.contents().find("textarea").val('');
    toggle_remove_golfer_link();
    setTotal();
}

function add_pairing(){
    $(".words:contains('Division for Pairing/Foursome 2')").parent().show('500');
    $(".words:contains('Pairing/Foursome 2')").parent().show('500');
    toggle_pairing_links();
}

function toggle_pairing_links() {
    if ($(".words:contains('Division for Pairing/Foursome 2')").parent().is(":visible")) {
        $("#remove_pairing_link").show('500');
        division_2_element.show('500');
        grouping_2_element.show('500');
        $("#add_parirng_link").hide('500');
    } else {
        $("#remove_pairing_link").hide('500');
        grouping_2_element.hide('500');
        division_2_element.hide('500');
        $("#add_parirng_link").show('500');
    }
}

function remove_pairing(){
    // hide the second pairing/guest fields and reset values
    $(".words:contains('Division for Pairing/Foursome 2')").parent().hide();
    $(".words:contains('Pairing/Foursome 2')").parent().hide('500');
    $(".words:contains('Division for Pairing/Foursome 2')").parent().find("input:radio").attr('checked', false);
    $(".words:contains('Pairing/Foursome 2')").parent().find("textarea").val('');
    toggle_pairing_links();
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
    golf_cost           = 105;
    brunch_dinner_cost  = 45;
    dinner_cost         = 30;
    total_cost          = 0;

    // if ($(".words:contains('Please select one')").next().find('input:radio:checked')){
    package_selection = $(".words:contains('Please select one')").next().find('input:radio:checked');

    package_selection.each(function(){
        if ($(this).val() == 'golf') {
            total_cost = total_cost + golf_cost;
        };
        if ($(this).val() == 'brunch and dinner') {
            total_cost = total_cost + brunch_dinner_cost;
        };
        if ($(this).val() == 'dinner') {
            total_cost = total_cost + dinner_cost;
        };
    });


    return total_cost;
}

function setTotal(){
    payment_amountElement.val('$' + add_costs());
    payment_amountElement.effect('highlight');
}

function hide_initial_items(){
    if ( !$(".words:contains('First Name 2')").next().find("input").val() ) {
        $("#guest2wrapperItem").hide();
        division_2_element.hide();
        grouping_2_element.hide();
    }
    if ( !$(".words:contains('First Name 3')").next().find("input").val() ) {
        $("#guest3wrapperItem").hide();
    }
    if ( !$(".words:contains('First Name 4')").next().find("input").val() ) {
        $("#guest4wrapperItem").hide();
    }
    if ( !$(".words:contains('First Name 5')").next().find("input").val() ) {
        $("#guest5wrapperItem").hide();
    }
    if ( !$(".words:contains('First Name 6')").next().find("input").val() ) {
        $("#guest6wrapperItem").hide();
    }
    if ( !$(".words:contains('First Name 7')").next().find("input").val() ) {
        $("#guest7wrapperItem").hide();
    }
    if ( !$(".words:contains('First Name 8')").next().find("input").val() ) {
        $("#guest8wrapperItem").hide();
    }
    toggle_remove_golfer_link();
    toggle_pairing_links();
}
