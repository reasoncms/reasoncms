$(document).ready(function() {

 
 var dolamt = 0;
 

$('#id_3fb4o1j748Element').blur(function() {

   var studentvalue = parseInt($(this).val());
   dolamt = dolamt + studentvalue * 25;
  
  
  $('#payment_amountElement').val(dolamt);



});

$('#id_15354l4d84Element').blur(function() {
   
   var sponsorvalue = $(this).val();
   dolamt = dolamt + sponsorvalue * 20;
  

  $('#payment_amountElement').val(dolamt);
});

$('#id_1HJ33534J4Element').blur(function() {
   var ropesvalue = $(this).val();
   dolamt = dolamt + ropesvalue * 5;
  

  $('#payment_amountElement').val(dolamt);


  
});




//    $.noConflict();
    // $("#votes").tablesorter();
    
    // //autocomplete first choice name
    // $('#first_choiceElement').autocomplete({
    //     source: "/reason/autocomplete/jenson_medal.php",
    //     minLength: 1,
    //     select: function( event, ui ) {
    //         $('#first_choice_usernameElement').val(ui.item.id);
    //     }
    // });
    // //autocomplete second choice name
    // $('#second_choiceElement').autocomplete({
    //     source: "/reason/autocomplete/jenson_medal.php",
    //     minLength: 1,
    //     select: function( event, ui ) {
    //         $('#second_choice_usernameElement').val(ui.item.id);
    //     }
    // });
    // //autocomplete third choice name
    // $('#third_choiceElement').autocomplete({
    //     source: "/reason/autocomplete/jenson_medal.php",
    //     minLength: 1,
    //     select: function( event, ui ) {
    //         $('#third_choice_usernameElement').val(ui.item.id);
    //     }
    // });
    
});