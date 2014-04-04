$(document).ready(function() {

  var total = 0;

  $('#payment_amountElement').attr('readonly','readonly');

    $("input[type=checkbox]").change(function() {
        var amount = 0;
        var string_amount = '';

        $("input[type=checkbox]:checked").each(function(){
            string_amount = this.value.replace('$','');
            amount += parseFloat(string_amount,10);
        });

        $('#payment_amountElement').val("$"+amount);
        $('#payment_amountElement').effect( 'highlight');
    });

});
