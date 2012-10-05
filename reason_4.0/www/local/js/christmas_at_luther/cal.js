$(document).ready(function() {
    
    // ajax to retrieve number of tickets remaining for purchase 
    var get_tickets_remaining = function () {
        var target = $(this).attr('data-target');
        jQuery.ajax({
            url: '/reason/local/js/christmas_at_luther/christmas_tickets_json.php',
            dataType: 'json',
            success: function(json){
                thursday_remaining = 5000 - json[0].thursday;
                jQuery('#thursday_count').fadeOut('slow').html(thursday_remaining).fadeIn('slow');

                friday630_remaining = 3000 - json[0].friday630;
                jQuery('#friday630_count').fadeOut('slow').html(friday630_remaining).fadeIn('slow');

                friday915_remaining = 300 - json[0].friday915;
                jQuery('#friday915_count').fadeOut('slow').html(friday915_remaining).fadeIn('slow');

                saturday_remaining = 38 - json[0].saturday;
                jQuery('#saturday_count').fadeOut('slow').html(saturday_remaining).fadeIn('slow');

                sunday_remaining = 57 - json[0].sunday;
                jQuery('#sunday_count').fadeOut('slow').html(sunday_remaining).fadeIn('slow');
            },
            error: function(xhr, ajaxOptions, thrownError){
            }
        });
    };

    // function for populating form when adequate information is provided (account and (first or last name))
    function auto_populate_form() {
        if (jQuery('#codeElement').val() != '' && (jQuery('#id_O4030a9t_7Element').val() != '' || jQuery('#id_14d4Qa4099Element').val() != '')) {
            jQuery.ajax({
                url: '/reason/local/js/christmas_at_luther/christmas_accounts.php?account=' + jQuery('#codeElement').val() + '&last=' + jQuery('#id_O4030a9t_7Element').val() + '&first=' + jQuery('#id_14d4Qa4099Element').val(),
                dataType: 'json',
                success: function(json) {
                    if (jQuery('#id_O4030a9t_7Element').val() == '') {
                        jQuery('#id_O4030a9t_7Element').val(json[0].last);
                    };
                    if (jQuery('#id_14d4Qa4099Element').val() == '') {
                        jQuery('#id_14d4Qa4099Element').val(json[0].first);
                    };
                    if (jQuery('#id_70n901i91fElement').val() == '') {
                        jQuery('#id_70n901i91fElement').val(json[0].address);
                    };
                    if (jQuery('#id_061324urr4Element').val() == '') {
                        jQuery('#id_061324urr4Element').val(json[0].city);
                    };
                    if (jQuery('#id_W4_J1396xSElement').val() == '') {
                        jQuery('#id_W4_J1396xSElement').val(json[0].state);
                    };
                    if (jQuery('#id_r139397698Element').val() == '') {
                        jQuery('#id_r139397698Element').val(json[0].zip);
                    };
                    if (jQuery('#id_12p2XFGNN3Element').val() == '') {
                        jQuery('#id_12p2XFGNN3Element').val(json[0].email);
                    };
                    if (jQuery('#id_q77f4N590kElement').val() == '') {
                        jQuery('#id_q77f4N590kElement').val(json[0].phone);
                    };
                    if (jQuery('#id_4tG58L48XdElement').val() == '') {
                        jQuery('#id_4tG58L48XdElement').val(json[0].cellphone);
                    };
                },
                error: function(xhr, ajaxOptions, thrownError){
                }
            });
        };
    }

    // initialize number of tickets remaining and set to check every 60 seconds
    get_tickets_remaining();
    setInterval(get_tickets_remaining, 1000 * 60 * .20) // every 1 minute
    // watch to pre-populate data
    jQuery('#codeElement').blur(function () {
        auto_populate_form();
    });
    jQuery('#id_O4030a9t_7Element').blur(function () {
        auto_populate_form();
    });
    jQuery('#id_14d4Qa4099Element').blur(function () {
        auto_populate_form();
    });

    // function called by alumni check box change trigger
    function hide_show_grad_year(element) {
        if (element.is(":checked")) {
            jQuery('#ideXgd4207ZcRow').show();
        } else {
            jQuery('#ideXgd4207ZcRow').hide();
        };
    };

    // if the alumni checkbox is changed call the hide/show grad year function
    jQuery('#checkbox_id_spJ91C0K77').change(function () {
        hide_show_grad_year($(this));
    });
    
    // hide/show the grad year check box on page load, if it was checked before (error page) it won't be hidden
    hide_show_grad_year(jQuery('#checkbox_id_spJ91C0K77'));

    //# of tickets
    var thursday_tix = $('#id_10p1JJ792_Element'); //# of tickets
    var friday630_tix = $('#id_2i4d1Ls_41Element'); 
    var friday915_tix = $('#id_0A0E3h183vElement'); 
    var saturday_tix = $('#id_k383n777krElement'); 
    var sunday_tix = $('#id_6hOG5v79TJElement'); 


  $('#payment_amountElement').attr('readonly','readonly');
  setTotal();

  if ($(thursday_tix).val().length > 0  || $(friday630_tix).val().length > 0 || $(friday915_tix).val().length > 0 || $(saturday_tix).val().length > 0 || $(sunday_tix).val().length > 0 ){
    setTotal();
  }
    //# of Thursday
  // $('#id_10p1JJ792_Element').blur(function() {
   thursday_tix.blur(function() {
      setTotal();
    });
  //# of Sponsors @ $20.00
  // $( ".words:contains('Sponsors @ $20.00')").next().blur(function(){
  // $('#id_15354l4d84Element').blur(function() {
  friday630_tix.blur(function() {
    setTotal();
  });
  friday915_tix.blur(function() {
    setTotal();
  });
  saturday_tix.blur(function() {
    setTotal();
  });
  sunday_tix.blur(function() {
    setTotal();
  });
  

  function getThursAmount(number){
    return number * 25;
  }
  function getFri630Amount(number){
    return number * 25;
  }

  function getFri915Amount(number){
    return number * 25;
  }

  function getSatAmount(number){
    return number * 25;
  }

  function getSunAmount(number){
    return number * 25;
  }

  function add(){
    return getThursAmount(thursday_tix.val()) + getFri630Amount(friday630_tix.val()) + getFri915Amount(friday915_tix.val()) + getSatAmount(saturday_tix.val()) + getSunAmount(sunday_tix.val());
  }

  function setTotal(){
    $('#payment_amountElement').val(add());
    $('#payment_amountElement').effect( 'highlight');
  }
});
