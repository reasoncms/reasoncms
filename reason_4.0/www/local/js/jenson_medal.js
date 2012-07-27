$(document).ready(function() {

//    $.noConflict();
    $("#votes").tablesorter();
    
    //autocomplete first choice name
    $('#first_choiceElement').autocomplete({
        source: "/reason/autocomplete/jenson_medal.php",
        minLength: 1,
        select: function( event, ui ) {
            $('#first_choice_usernameElement').val(ui.item.id);
        }
    });
    //autocomplete second choice name
    $('#second_choiceElement').autocomplete({
        source: "/reason/autocomplete/jenson_medal.php",
        minLength: 1,
        select: function( event, ui ) {
            $('#second_choice_usernameElement').val(ui.item.id);
        }
    });
    //autocomplete third choice name
    $('#third_choiceElement').autocomplete({
        source: "/reason/autocomplete/jenson_medal.php",
        minLength: 1,
        select: function( event, ui ) {
            $('#third_choice_usernameElement').val(ui.item.id);
        }
    });
    
});