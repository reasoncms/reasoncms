/**
 * input_limiter.js 
 * Manages the limiting of characters a user can input for a given element in a Disco form
 * 
 * @author Nick Jones, Noah Carnahan
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
    
    function count_and_update(text_element, cur_text)
    {
        var limit_note = text_element.siblings('div.inputLimitNote');
        var over_limit_note = text_element.siblings('div.overLimitNote');
        var current_text = cur_text;
        var char_limit = parseInt(limit_note.children('span.charLimit').html());
        var chars_remaining_element = limit_note.children('span.charsRemaining');
        var auto_show_hide = limit_note.hasClass("autoShowHide");
        
        // only make the AJAX call if within what JS considers to be 20
        // characters of the limit
        if( !auto_show_hide || (current_text.length > (char_limit - 20) ))
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
                    else if( !auto_show_hide || (chars_remaining < 20) )
                    {
                        limit_note.show();
                        over_limit_note.hide();
                    }
                    // not "close" to limit, so hide everything
                    else if(auto_show_hide)
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
        
    //bind chatacter count upon keyup events in text areas
	$('div.inputLimitNote').siblings('textarea, :text').bind('keyup', function()
	{
		// if an AJAX call was in queue to be made, replace it with a new one
		if(typeof(t_out) != 'undefined')
		{
			clearTimeout(t_out);
		}
		var copy_of_this = $(this);
		t_out = setTimeout(function(){ count_and_update(copy_of_this, copy_of_this.val()) }, 700);
	});
	// trigger character count upon the first rendering of page
    $('div.inputLimitNote').siblings('textarea, :text').each(function(){
        count_and_update($(this), $(this).val());
    });
		
	function bind_loki_handler()
	{
		//alert('binding loki handlers');
		$('div.inputLimitNote').siblings('.loki').find('iframe').each(function(){
			//alert('in the each');
			var iframeNode = this;
			function handler(){
				var text_element = $(iframeNode).parents('.loki');
				var cur_text = $(this).html();
				if(typeof(t_out) != 'undefined')
				{
					clearTimeout(t_out);
				}
				t_out = setTimeout(function(){ count_and_update(text_element, cur_text) }, 700);
			}
			$(this).contents().find("body").unbind('keyup', handler);
			$(this).contents().find("body").keyup(handler);
		});
	}
	
	//bind character count upon keyup events in loki frames
	$(window).load(function(){
	
		// bind it to the current iframe
		bind_loki_handler();
		//setTimeout(bind_loki_handler, 300);
		
		//bind it to any future iframes
		$('iframe').load(function(){
			//alert('from the load');
			bind_loki_handler();
		});
		
		//bind it to current and future frames in ie. (the above methods dont work for ie8)
		$(document).on('focus click', 'iframe', function(){
			//alert('yeah!?');
			bind_loki_handler();
		});
		
		//bind it to any future textareas in loki divs (for html source view)
		$(document).on('keyup', '.loki textarea', function(){
			var text_element = $(this).parents('.loki');
			var cur_text = $(this).val();
			if(typeof(t_out) != 'undefined')
			{
				clearTimeout(t_out);
			}
			t_out = setTimeout(function(){ count_and_update(text_element, cur_text) }, 700);
		});
	});

	//bind count_and_update to the keyup event of appropriate tinyMCE editors
	if (typeof tinymce !== 'undefined') {
		tinymce.on('addEditor', function(obj) {
			obj.editor.on('init', function(ed) {
				editor = ed.target;
				container = $(editor.getContainer());
				if (container.siblings('div.inputLimitNote').size() > 0) {
					editor.on('KeyUp', function(e) {
						var elementToUpdate = container;
						var content = editor.getContent();
						if(typeof(t_out) != 'undefined')
						{
							clearTimeout(t_out);
						}
						t_out = setTimeout(function(){ count_and_update(elementToUpdate, editor.getContent()) }, 700);
					});
				}
			});
		});
	}
});


