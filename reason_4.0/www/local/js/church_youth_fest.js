$(document).ready(function() {

  var studentNumber = $('#id_3fb4o1j748Element'); //# of Youth @ $25.00
  var sponsorNumber = $('#id_15354l4d84Element'); //# of Sponsors @ $20.00
  // var ropesNumber = $('#id_1HJ33534J4Element'); //# for Ropes Course @ $5.00
  var ropesNumber = $('#id_w15Hb4D3yMElement'); //# for Ropes Course @ $5.00

  $('#payment_amountElement').attr('readonly','readonly');
  setTotal();

  if ($(studentNumber).val().length > 0  || $(sponsorNumber).val().length > 0 || $(ropesNumber).val().length > 0){
    setTotal();
  }
    //# of Youth @ $25.00
  // $( ".words:contains('Youth @ $25.00')").next().blur(function(){
  $('#id_3fb4o1j748Element').blur(function() {
      setTotal();
    });
  //# of Sponsors @ $20.00
  // $( ".words:contains('Sponsors @ $20.00')").next().blur(function(){
  $('#id_15354l4d84Element').blur(function() {
    setTotal();
  });
  //# for Ropes Course @ $5.00
  // $( ".words:contains('Ropes Course @ $5.00')").next().blur(function(){
  $('#id_w15Hb4D3yMElement').blur(function() {
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