$(document).ready(function() {

  var dolamt = 0;
  var youth = 0;
  var adults = 0;
  var ropes = 0;

  var studentNumber = $('#id_3fb4o1j748Element'); //# of Youth @ $25.00
  var sponsorNumber = $('#id_15354l4d84Element'); //# of Sponsors @ $20.00
  var ropesNumber = $('#id_1HJ33534J4Element'); //# for Ropes Course @ $5.00

  $('#payment_amountElement').attr('readonly','readonly');
  setTotal();

  if ($(studentNumber).val().length > 0  || $(sponsorNumber).val().length > 0 || $(ropesNumber).val().length > 0){
    dolamt = add();
  }
        //# of Youth @ $25.00
      $('#id_3fb4o1j748Element').blur(function() {
          dolamt = add();
          setTotal();
        });
      //# of Sponsors @ $20.00
      $('#id_15354l4d84Element').blur(function() {
        var sponsorvalue = $(this).val();
        dolamt = add();
        setTotal();
      });
      //# for Ropes Course @ $5.00
      $('#id_1HJ33534J4Element').blur(function() {
        var ropesvalue = $(this).val();
        dolamt = add();
        setTotal();
      });

  function getStudentAmount(number){
    return number * 25;
  }
  function getSponsorAmount(number){
    return number * 20;
  }

  function getRopesAmount(number){
    return number * 5;
  }

  function add(){
    return getStudentAmount(studentNumber.val()) + getSponsorAmount(sponsorNumber.val()) + getRopesAmount(ropesNumber.val());
  }

  function setTotal(){
    $('#payment_amountElement').val(add());
    $('#payment_amountElement').effect( 'highlight');
  }
});