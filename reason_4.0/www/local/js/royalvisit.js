/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){

    toggle_guest();    
  
    $(".words:contains('Do you want an extra guest ticket for $20?')").parent().change(function(){
        toggle_guest();  
    })  
    
    special_seating(); 
    $(".words:contains('Accessibility Issues?')").parent().change(function(){
        special_seating();  
    })   

          
    function toggle_guest(){      
    
    
        if ($("#radio_id_l10625431x_0:checked").val() == 'Yes' || $("#radio_id_3Qd3ihH0m2_0:checked").val() == 'Yes'){
            
            $(".words:contains('If yes, for security measures, please fill in the form below')").parent().show();
            $(".words:contains('Guest First Name')").parent().show();
            $(".words:contains('Guest Last Name')").parent().show();
            $(".words:contains('Guest Address')").parent().show();
            $(".words:contains('Guest City')").parent().show();
            $(".words:contains('Guest State')").parent().show();
            $(".words:contains('Guest Zip code')").parent().show();
            $(".words:contains('Guest Phone')").parent().show();
            $(".words:contains('Guest Email')").parent().show();
            
            
        }
        else{
            
            $(".words:contains('If yes, for security measures, please fill in the form below')").parent().hide();
            $(".words:contains('Guest First Name')").parent().hide();
            $(".words:contains('Guest Last Name')").parent().hide();
            $(".words:contains('Guest Address')").parent().hide();
            $(".words:contains('Guest City')").parent().hide();
            $(".words:contains('Guest State')").parent().hide();
            $(".words:contains('Guest Zip code')").parent().hide();
            $(".words:contains('Guest Phone')").parent().hide();
            $(".words:contains('Guest Email')").parent().hide();

            
        }
    }
    function special_seating(){
        
        if ($("#radio_id_U3231m4642_0:checked").val() == 'Yes' || $("#radio_id_e6q047vCk4_0:checked").val() == 'Yes'|| $("#radio_id_7Ru69383O6_0:checked").val() == 'Yes'){ 
            
            $(".words:contains('Please describe')").parent().show();
            
        }else{
            
            $(".words:contains('Please describe')").parent().hide();
        }
    }
    
    
})
       




