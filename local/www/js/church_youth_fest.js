$(document).ready(function() {
  
  var dolamt = 0;
  var youth = 0;
  var adults = 0;
  var ropes = 0;

  var sub_total = 0;
  var total = 0;

  var studentNumber = $('#id_3fb4o1j748Element');
  // var studentNumber = parseInt($('#id_3fb4o1j748Element').val()) || 0;
  // var sponsorNumber = parseInt($('#id_15354l4d84Element').val()) || 0;
  var sponsorNumber = $('#id_15354l4d84Element');
  // var ropesNumber = parseInt($('#id_1HJ33534J4Element').val()) || 0;
  var ropesNumber = $('#id_1HJ33534J4Element');


  $('#payment_amountElement').attr('readonly','readonly');
  setTotal();

  if ($(studentNumber).val().length > 0  || $(sponsorNumber).val().length > 0 || $(ropesNumber).val().length > 0 ){
    alert('i');
    dolamt = getStudentAmount(parseInt(studentNumber.val())) + getSponsorAmount(sponsorNumber) + getRopesAmount(ropesNumber);
  }


 
        
        $('#id_3fb4o1j748Element').blur(function() {
          var studentvalue = parseInt($(this).val()) || 0;
          alert(studentNumber.val());
          // youth = studentvalue * 25;
          dolamt = add();
          setTotal();
        });

      $('#id_15354l4d84Element').blur(function() {
         
         var sponsorvalue = $(this).val();
         // adults = sponsorvalue * 20;
        dolamt = getStudentAmount(studentNumber) + getSponsorAmount(sponsorNumber) + getRopesAmount(ropesNumber); 
        setTotal();
      });

      $('#id_1HJ33534J4Element').blur(function() {
         var ropesvalue = $(this).val();
        // ropes = ropesvalue * 5;
        dolamt = getStudentAmount(studentNumber) + getSponsorAmount(sponsorNumber) + getRopesAmount(ropesNumber);
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
    return getStudentAmount(parseInt(studentNumber.val())) + getSponsorAmount(parseInt(sponsorNumber.val())) + getRopesAmount(parseInt(ropesNumber.val()));
  }

  function setTotal(){
    $('#payment_amountElement').val(dolamt);
    $('#payment_amountElement').effect( 'highlight');
  }
});

  