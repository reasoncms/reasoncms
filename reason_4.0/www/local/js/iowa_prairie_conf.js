$(document).ready(function() {
  // var registration = $('radio_FhDfvRqA6y_id_0');
  // var singlenights = $('checkbox_1CQACscwtW_id_0');
  // var doublenights = $('checkbox_5RayWnEd4k_id_0');
  var payment_amountElement = $('#b67q3FbJaF_idElement');

  $(payment_amountElement).attr('readonly', 'readonly');
  setTotal();

  // registration
  $("input[name='fJBO51KmHw_id']").change(function(){
    setTotal()
  });

  // single bed nights
  $("input[name='25bRwTOtgL_id[0]']").change(function(){
      setTotal()
    });
  $("input[name='25bRwTOtgL_id[1]']").change(function(){
      setTotal()
    });
  $("input[name='25bRwTOtgL_id[2]']").change(function(){
      setTotal()
    });

  // double bed nights
  $("input[name='ZUWSohgyAy_id[0]']").change(function(){
      setTotal()
    });
  $("input[name='ZUWSohgyAy_id[1]']").change(function(){
      setTotal()
    });
  $("input[name='ZUWSohgyAy_id[2]']").change(function(){
      setTotal()
    });

  function getRegistrationAmount(){
    // get the radio button amount
    var regint = 0;
    if ($("input:radio[name='fJBO51KmHw_id']:checked").val()){
      var str = $("input:radio[name='fJBO51KmHw_id']:checked").val();
      var sstr = str.split( ' - ' );
      var reg = sstr[0].split('$');
      
      regint = parseInt(reg[1], 10);
      return regint;
    } else {
      return 0;
    }
  }

  function getSingleNights(){
    // checkboxes * 42.50;
    var nights = 0;
    if ($("input[name='25bRwTOtgL_id[0]']").is(':checked')){
      nights ++;
    }
    if ($("input[name='25bRwTOtgL_id[1]']").is(':checked')){
      nights ++;
    }
    if ($("input[name='25bRwTOtgL_id[2]']").is(':checked')){
      nights ++;
    }
    return nights * 42.50;
  }

  function getDoubleNights(){
    // checkboxes * 79;
    var nights = 0;
    if ($("input[name='ZUWSohgyAy_id[0]']").is(':checked')){
      nights ++;
    }
    if ($("input[name='ZUWSohgyAy_id[1]']").is(':checked')){
      nights ++;
    }
    if ($("input[name='ZUWSohgyAy_id[2]']").is(':checked')){
      nights ++;
    }
    return nights * 79;
  }

  function add(){
    return getRegistrationAmount() + getSingleNights() + getDoubleNights();
  }

  function setTotal(){
    payment_amountElement.val('$' + add().toFixed(2));
    payment_amountElement.effect( 'highlight');
  }
});