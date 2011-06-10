
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
    
   
   toggle_treat_orders();
   
    $(".words:contains('Payment Amount')").parent().change(function(){
        toggle_treat_orders();
        
    });
    
     
});

function toggle_treat_orders(){
    
    $(".words:contains('#2')").parent().hide();
    $(".words:contains('#3')").parent().hide();
    $(".words:contains('#4')").parent().hide();
    $(".words:contains('#5')").parent().hide();
    $(".words:contains('#6')").parent().hide();
    
    
    
    
         
    //occassion #1
    if ($("#radio_id_554I3v48rI_0:checked").val() == '$20 - 1 treat'){
        
        $(".words:contains('#1')").parent().show(); 
       
        
    }
    

    else if ($("#radio_id_554I3v48rI_1:checked").val() == '$40 - 2 treats') {
        
        
        $(".words:contains('#1')").parent().show();
        $(".words:contains('#2')").parent().show(); 
        
    }
    
    else if ($("#radio_id_554I3v48rI_2:checked").val() == '$60 - 3 treats'){
       
        
        $(".words:contains('#1')").parent().show();
        $(".words:contains('#2')").parent().show(); 
        $(".words:contains('#3')").parent().show();  
    }
    
    
    else if ($("#radio_id_554I3v48rI_3:checked").val() == '$80 - 4 treats'){
        
        
        $(".words:contains('#1')").parent().show();
        $(".words:contains('#2')").parent().show(); 
        $(".words:contains('#3')").parent().show();  
        $(".words:contains('#4')").parent().show();
    }
    
    else if ($("#radio_id_554I3v48rI_4:checked").val() == '$100 - 5 treats'){
        
        
        $(".words:contains('#1')").parent().show();
        $(".words:contains('#2')").parent().show(); 
        $(".words:contains('#3')").parent().show(); 
        $(".words:contains('#4')").parent().show();  
        $(".words:contains('#5')").parent().show();  
    }
    
    else if ($("#radio_id_554I3v48rI_5:checked").val() == '$120 - 6 treats'){
       
        
        $(".words:contains('#1')").parent().show();
        $(".words:contains('#2')").parent().show(); 
        $(".words:contains('#3')").parent().show(); 
        $(".words:contains('#4')").parent().show();  
        $(".words:contains('#5')").parent().show();
        $(".words:contains('#6')").parent().show(); 
    }
    
 
    
}

    
    
    


