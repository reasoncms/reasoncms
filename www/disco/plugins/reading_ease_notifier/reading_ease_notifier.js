/**
 * reading_ease_notifier.js
 * Manages the notification of the reading level of text entered in a Disco form
 *
 * @author Nathaniel MacArthur-Warner
 * @requires jQuery
 */
$(document).ready(function()
{
	var determiner_src = new String();
	$('script[src$= "reading_ease_notifier.js"]:first').each(function() {
		var script_src = $(this).attr('src');
		determiner_src = script_src.replace('reading_ease_notifier.js', 'get_reading_ease.php');
	});
	
	function determine_and_update(text_element, cur_text)
	{
		var current_text = cur_text;
		var reading_ease_notification = text_element.siblings('div.readingEaseNotification');
		var current_reading_ease = reading_ease_notification.children('span.currentReadingEase');
		var current_reading_ease_label = reading_ease_notification.children('span.currentReadingEaseLabel');
		
		$.getJSON(determiner_src,
			{text: current_text},
			function(returned_reading_ease)
			{
				current_reading_ease.html(returned_reading_ease.score);
				current_reading_ease_label.html(returned_reading_ease.label);
			});
	}
	
	// bind grade level determination upon keyup events in text areas
	$('div.readingEaseNotification').siblings('textarea, :text').on('keyup', function(e)
	{
		// if an AJAX call was in queue to be made, replace it with a new one
		if(typeof(reading_ease_timeout) != 'undefined')
		{
			clearTimeout(reading_ease_timeout);
		}
		var copy_of_this = $(this);
		reading_ease_timeout = setTimeout(function(){ determine_and_update(copy_of_this, copy_of_this.val()) }, 700);
	});
	
	// trigger grade level determination upon the first rendering of page
    $('div.readingEaseNotification').siblings('textarea, :text').each(function(){
        determine_and_update($(this), $(this).val());
    });
	
	function bind_loki_handler()
	{
		//alert('binding loki handlers');
		$('div.readingEaseNotification').siblings('.loki').find('iframe').each(function(){
			//alert('in the each');
			var iframeNode = this;
			function handler(){
				var text_element = $(iframeNode).parents('.loki');
				var cur_text = $(this).html();
				if(typeof(reading_ease_timeout) != 'undefined')
				{
					clearTimeout(reading_ease_timeout);
				}
				reading_ease_timeout = setTimeout(function(){ determine_and_update(text_element, cur_text) }, 700);
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
			if(typeof(reading_ease_timeout) != 'undefined')
			{
				clearTimeout(reading_ease_timeout);
			}
			reading_ease_timeout = setTimeout(function(){ determine_and_update(text_element, cur_text) }, 700);
		});
	});

	//bind determine_and_update to the keyup event of appropriate tinyMCE editors
	if (typeof tinymce !== 'undefined') {
		tinymce.on('addEditor', function(obj) {
			obj.editor.on('init', function(ed) {
				editor = ed.target;
				container = $(editor.getContainer());
				if (container.siblings('div.readingEaseNotification').size() > 0) {
					editor.on('KeyUp', function(e) {
						var elementToUpdate = container;
						var content = editor.getContent();
						if(typeof(reading_ease_timeout) != 'undefined')
						{
							clearTimeout(reading_ease_timeout);
						}
						reading_ease_timeout = setTimeout(function(){ determine_and_update(elementToUpdate, editor.getContent()) }, 700);
					});
				}
			});
		});
	}
});