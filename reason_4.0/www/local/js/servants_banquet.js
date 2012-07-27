$(document).ready(function(){

         
    toggle_guest();     
    $("input[name='id_47984YA401']").change(function(){
        toggle_guest();      
        
    })    
          
    function toggle_guest(){      
    
    
        if ($("#radio_id_47984YA401_1:checked").val() == '$60 with guest'){
            
            $("#id1e83H1WmM0Row").show();
            $("#id1kCS7B75P6Row").show();
            $("#idI191dyUD00Row").show();
        }
        else{
            
            $("#id1e83H1WmM0Row").hide();
            $("#id1kCS7B75P6Row").hide();
            $("#idI191dyUD00Row").hide();
            
        }
    }
       
    
    
})
