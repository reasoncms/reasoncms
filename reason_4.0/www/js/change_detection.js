/**
 * Notifies user if form has changed when user navigates away from form.
 *
 * 1) Wait 3 seconds to hope other javascript modifying form has finished
 * 2) serialize initial form data
 * 3) bind click to left nav links, which upon click will prompt user if
 *    serialized data differs.
 *
 * @author Benjamin Wilbur
 * @author Lucas Welper 
 *
 * @requires jQuery
*/

var initial_serialized_form = '';
var next_page = '';

// serialized form data and non-form data (ie...WYSIWYG)
function change_detection_serialize_form() {
    var serialized_data = $('#disco_form').serialize();
    // for loki instance serialization
    $("div.loki").each(function(){
        serialized_data += $(this).find("iframe").contents().find("body.contentMain").html();
    });
    return serialized_data;
}

function initialize_change_detection() {
    initial_serialized_form = change_detection_serialize_form();

    $('ul.leftList > li.navItem > a.nav').click(function(e){
        var click_serialized = change_detection_serialize_form();
        if (click_serialized != initial_serialized_form) {
            // create hidden input for clicked <a href... for the purpose of 
            // where_to when form is saved
            $('<input>').attr({
                type: 'hidden',
                id: 'change_detection_redirectElement',
                name: 'change_detection_redirect',
            }).appendTo('#disco_form');
            next_page = $(this).attr('href');
            $('#change_detection_redirectElement').val(next_page);
            $('#dialog_confirm').dialog('open');
            e.preventDefault();
        }
    });
}

$(document).ready(function(){
    $('#change_detection_redirectElement').remove();
    $( "#dialog_confirm" ).dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        width: 'auto',
        buttons: {
            "Save": function() {
                $("#disco_form").submit();
            },
            "Discard": function() {
                $( this ).dialog( "close" );
                window.location = next_page; 
            },
            "Continue Editing": function() {
                $('#change_detection_redirectElement').remove();
                $( this ).dialog( "close" );
            }
        }
    });
    // wait 5 seconds to try insure other javascript files are finished
    // queue and dequeue to utilize jquery's delay
    $(this).delay(5000).queue(function() {
        initialize_change_detection();
        $(this).dequeue(); 
    })
});