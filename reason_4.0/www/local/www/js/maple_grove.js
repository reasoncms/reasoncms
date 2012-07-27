$(document).ready(function() {

  var adultsNumber = $('#id_47K217Y2QLElement'); //# of Adults @ $10
  var childrenNumber = $('#id_k0g495738PElement'); //# of Children @ $5

  $('#payment_amountElement').attr('readonly','readonly');
  setTotal();

  if ( $(adultsNumber).val().length > 0  || $(childrenNumber).val().length > 0 ){
    setTotal();
  }
    //# of Adults @ $10
  $('#id_47K217Y2QLElement').blur(function() {
      setTotal();
    });
  //# of Children @ $5
  $('#id_k0g495738PElement').blur(function() {
    setTotal();
  });

  function getAdultAmount(number){
    return number * 10;
  }
  function getChildrenAmount(number){
    return number * 5;
  }

  function add(){
    return getAdultAmount(adultsNumber.val()) + getChildrenAmount(childrenNumber.val());
  }

  function setTotal(){
    $('#payment_amountElement').val(add());
    $('#payment_amountElement').effect( 'highlight');
  }
});