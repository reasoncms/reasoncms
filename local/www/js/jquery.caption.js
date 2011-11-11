/* Caption jQuery Plugin, v1
 *
 * Copyright (c) 2009, Isaac Niebeling
 */
 
(function($) {	
    $.fn.caption = function(options) {		
        return this.each(function() {
        	$(this).wrap('<div></div>');
			var imgWidth = $(this).outerWidth();
			var imgAlign = $(this).attr('align');
			imgAlign == "" ? imgAlign = $(this).css('float') : false;
			imgAlign != "" ? $(this).removeAttr('align').parent().addClass('figure align-' + imgAlign).css('width',imgWidth) : false;
			$(this).attr('alt') != null ? $(this).after('<span class="legend">' + $(this).attr('alt') + '</span>') : false;
        });
    };
	
})(jQuery);