/**
 * input_limiter.js 
 * Manages the limiting of characters a user can input for a given element in a Disco form
 * 
 * @author Nick Jones
 * @requires jQuery
 */
$(document).ready(function()
{

    /* 
        - Triggers an AJAX call to count the characters (for consistency on the server-side) 
        - Displays how many characters remain to be used once user gets within 20 characters of limit (hidden otherwise). 
        - Warns user when over the character limit 
    */
    
    // figure out where the counter php script is located -- should be in same directory as this script
    var counter_src = new String();
    $('script[src$= "input_limiter.js"]:first').each(function() {
        var script_src = $(this).attr('src');
        counter_src = script_src.replace('input_limiter.js', 'get_char_count.php');
    });
    
    function count_and_update(text_element)
    {
        var limit_note = text_element.siblings('div.inputLimitNote');
        var over_limit_note = text_element.siblings('div.overLimitNote');
        var current_text = text_element.val();
        var char_limit = parseInt(limit_note.children('span.charLimit').html());
        var chars_remaining_element = limit_note.children('span.charsRemaining');
        
        // only make the AJAX call if within what JS considers to be 20
        // characters of the limit
        if( current_text.length > (char_limit - 20) )
        {
            $.get(counter_src, 
                {text: current_text},
                function(returned_count)
                {
                    chars_remaining = char_limit - returned_count;
                    chars_remaining_element.html(chars_remaining);
                    var over_limit = -chars_remaining;
                    // display over-limit warning
                    if(chars_remaining < 0)
                    {
                        limit_note.hide();
                        over_limit_note.children('span.numCharsOver').html(over_limit);
                        over_limit_note.show();
                        
                    }
                    // display caution
                    else if(chars_remaining < 20)
                    {
                        limit_note.show();
                        over_limit_note.hide();
                    }
                    // not "close" to limit, so hide everything
                    else
                    {
                        limit_note.hide();
                        over_limit_note.hide();   
                    }
                });
        }
        // without AJAX call made, we should be hiding the warnings anyway (since not close/over limit)
        else
        {
            limit_note.hide();
            over_limit_note.hide();
        }
    }
    $('div.inputLimitNote').siblings('textarea, :text').bind('keyup', function()
    {
        // if an AJAX call was in queue to be made, replace it with a new one
        if(typeof(t_out) != 'undefined')
        {
            clearTimeout(t_out);
        }
        var copy_of_this = $(this);
        t_out = setTimeout(function(){ count_and_update(copy_of_this) }, 700);
    });
    
    // trigger character count upon the first rendering of page
    $('div.inputLimitNote').siblings('textarea, :text').each(function(){
        count_and_update($(this));
    });
});