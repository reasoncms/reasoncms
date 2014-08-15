
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

 
$(document).ready(function() {
    toggle_treat_orders(); 
    $(".words:contains('Payment Amount')").parent().change(function(){   
     toggle_treat_orders();
    });  

    $(".words:contains('Treat Type')").parent().change(function(){   
     toggle_treat_orders();
    });
});
//function to hide/show items on page until user makes a choice
function toggle_treat(number, show_or_hide){
	if (show_or_hide == 'show'){
	    $(".words:contains('#" + number + " ')").parent().show();
	//special case for #1 which would otherwise allow #10 to show up as well
	if (number == 1){
           $("h3:contains('#" + number + "')").parent().show();
           $("h4:contains('#" + number + "')").parent().show();
	   $("h3:contains('#10')").parent().hide();
	   $("h4:contains('#10')").parent().hide();
	} //all other cases
	else{
	    $("h3:contains('#" + number + "')").parent().show();
            $("h4:contains('#" + number + "')").parent().show();
	}
	} else {
	    $(".words:contains('#" + number + " ')").parent().hide();
	    $("h3:contains('#" + number + "')").parent().hide();
	    $("h4:contains('#" + number + "')").parent().hide();
	}
}
//hide/show conditional options
function toggle_treat_options(num, show_hide){
	if (show_hide == 'show'){
		$(".words:contains('For Cake #" + num + " please specify')").parent().show();
	}
	else {
		$(".words:contains('For Cake #" + num + " please specify')").parent().hide();
	}
	} //handle action based on user's choice
function toggle_treat_cake(num){
		if (($(".words:contains('#1  Treat Type')").next().find("input:radio:checked").val() == 'Light Cake') || ($(".words:contains('#1  Treat Type')").next().find("input:radio:checked").val() == 'Chocolate Cake') || ($(".words:contains('#1  Treat Type')").next().find("input:radio:checked").val() == 'Angel Food Cake') ){
		    toggle_treat_options(1,'show');	
   		}
		if (($(".words:contains('#" + num + " Treat Type')").next().find("input:radio:checked").val() == 'Light Cake') || ($(".words:contains('#" + num + " Treat Type')").next().find("input:radio:checked").val() == 'Chocolate Cake') || ($(".words:contains('#" + num + " Treat Type')").next().find("input:radio:checked").val() == 'Angel Food Cake') )
		{
		    toggle_treat_options(num,'show');
		}
 } //'main' function to handle everything else on form page
function toggle_treat_orders(){

       	var iterator = 0;

	for (var i=1; i<=10; i++){
 	   toggle_treat(i,'hide');
	}

    if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$20 - 1 treat')) {
	 iterator = 1;
    } 
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$40 - 2 treats')) {
	iterator = 2;
    } 
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$60 - 3 treats')) {
	iterator = 3;
    }
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$80 - 4 treats')) {
	iterator = 4;
     }
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$100 - 5 treats')) {
	iterator      = 5;
    }
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$120 - 6 treats')) {
	iterator = 6;
    }
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$140 - 7 treats')) {
	iterator = 7;        	 
    }
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$160 - 8 treats')) {
	iterator = 8;  
    }
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$180 - 9 treats')) {
	iterator = 9;
    }  
    else if (($(".words:contains('Payment Amount')").next().find("input:radio:checked").val() == '$200 - 10 treats')) {
	iterator = 10;
    }
    for (var p=1; p <= iterator; p++){  
	//prevent #10 from showing up at the bottom
	if (iterator < 10){ 
	   toggle_treat(10,'hide');    
        	}
	//show each of the selected options
	     toggle_treat(p,'show'); 
	//for each shown element, hide the 'For Cake...' option
	     toggle_treat_options(p,'hide');
	//show if user clicks on one of the cake options in #..Treat Type
	     toggle_treat_cake(p);
    } 
}


