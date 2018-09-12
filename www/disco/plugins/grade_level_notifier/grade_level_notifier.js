/**
 * grade_level_notifier.js
 * Manages the notification of the reading level of text entered in a Disco form
 *
 * @author Nathaniel MacArthur-Warner
 * @requires jQuery
 */
$(document).ready(function()
{
	var determiner_src = new String();
	$('script[src$= "grade_level_notifier.js"]:first').each(function() {
		var script_src = $(this).attr('src');
		determiner_src = script_src.replace('grade_level_notifier.js', 'get_grade_level.php');
	});
	
	function determine_and_update(text_element, cur_text)
	{
		var current_text = cur_text;
		var grade_level_notification = text_element.siblings('div.gradeLevelNotification');
		var current_grade_level = grade_level_notification.children('span.currentGradeLevel');
		
		$.get(determiner_src,
			{text: current_text},
			function(returned_grade_level)
			{
				current_grade_level.html(returned_grade_level);
			});
	}
	
	// bind grade level determination upon keyup events in text areas
	$('div.gradeLevelNotification').siblings('textarea, :text').on('keyup', function(e)
	{
		// if an AJAX call was in queue to be made, replace it with a new one
		if(typeof(grade_level_timeout) != 'undefined')
		{
			clearTimeout(grade_level_timeout);
		}
		var copy_of_this = $(this);
		grade_level_timeout = setTimeout(function(){ determine_and_update(copy_of_this, copy_of_this.val()) }, 700);
	});
	
	// trigger grade level determination upon the first rendering of page
    $('div.gradeLevelNotification').siblings('textarea, :text').each(function(){
        determine_and_update($(this), $(this).val());
    });
	
	function bind_loki_handler()
	{
		//alert('binding loki handlers');
		$('div.gradeLevelNotification').siblings('.loki').find('iframe').each(function(){
			//alert('in the each');
			var iframeNode = this;
			function handler(){
				var text_element = $(iframeNode).parents('.loki');
				var cur_text = $(this).html();
				if(typeof(grade_level_timeout) != 'undefined')
				{
					clearTimeout(grade_level_timeout);
				}
				grade_level_timeout = setTimeout(function(){ determine_and_update(text_element, cur_text) }, 700);
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
			if(typeof(grade_level_timeout) != 'undefined')
			{
				clearTimeout(grade_level_timeout);
			}
			grade_level_timeout = setTimeout(function(){ determine_and_update(text_element, cur_text) }, 700);
		});
	});

	//bind determine_and_update to the keyup event of appropriate tinyMCE editors
	if (typeof tinymce !== 'undefined') {
		tinymce.on('addEditor', function(obj) {
			obj.editor.on('init', function(ed) {
				editor = ed.target;
				container = $(editor.getContainer());
				if (container.siblings('div.gradeLevelNotification').size() > 0) {
					editor.on('KeyUp', function(e) {
						var elementToUpdate = container;
						var content = editor.getContent();
						if(typeof(grade_level_timeout) != 'undefined')
						{
							clearTimeout(grade_level_timeout);
						}
						grade_level_timeout = setTimeout(function(){ determine_and_update(elementToUpdate, editor.getContent()) }, 700);
					});
				}
			});
		});
	}
});