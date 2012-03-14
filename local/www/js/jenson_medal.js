/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function() {

    //autocomplete first choice name
    $('#first_choiceElement').autocomplete({
        source: "/reason/autocomplete/jenson_medal.php",
        minLength: 1,
        select: function( event, ui ) {
            $('#first_choiceElement').val(ui.item.id);
   	}
    });
    //autocomplete second choice name
    $('#second_choiceElement').autocomplete({
        source: "/reason/autocomplete/jenson_medal.php",
        minLength: 1,
        select: function( event, ui ) {
            $('#second_choiceElement').val(ui.item.id);
   	}
    });
    //autocomplete third choice name
    $('#third_choiceElement').autocomplete({
        source: "/reason/autocomplete/jenson_medal.php",
        minLength: 1,
        select: function( event, ui ) {
            $('#third_choiceElement').val(ui.item.id);
   	}
    });

});