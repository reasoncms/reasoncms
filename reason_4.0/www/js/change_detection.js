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
        // loki
        serialized_data += $(this).find("iframe").contents().find("body.contentMain").html();
    });
    $("td.mceIframeContainer").each(function(){
        // tincy mce
        serialized_data += $(this).find("iframe").contents().find("body").html();
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

/**
 * Lets find our wait image based on the path of this script.
 */
function get_spinner_path()
{
    base_path = $('script[src$="/js/change_detection.js"]:first').attr("src").replace("/js/change_detection.js","");
    return base_path + "/ui_images/reason_admin/wait.gif";
}

function draw_dialog(buttons_list)
{
    $( "#dialog_confirm" ).dialog({
            autoOpen: false,
            resizable: false,
            modal: true,
            width: '50%',
            buttons: buttons_list
        }
    );
}

$(document).ready(function(){
    $('#change_detection_redirectElement').remove();
    var buttons = {
        "Save": function() {
            $( this ).dialog( "close" );
            $("tr#discoSubmitRow input:first").trigger("click");
            $("#dialog_confirm p").html('Please wait... <img src="' + get_spinner_path() + '"/>');
            draw_dialog({});
            $('#dialog_confirm').dialog('open');
        },
        "Discard": function() {
            $( this ).dialog( "close" );
            window.location = next_page; 
        },
        "Continue Editing": function() {
            $('#change_detection_redirectElement').remove();
            $( this ).dialog( "close" );
        }
    };
    draw_dialog(buttons);
    // wait 5 seconds to try insure other javascript files are finished
    // queue and dequeue to utilize jquery's delay
    $(this).delay(5000).queue(function() {
        initialize_change_detection();
        $(this).dequeue(); 
    })
});