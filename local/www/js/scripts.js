(function($) {	
	/* caption images */
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
    
    /* set padding to vertically-align text */
	$.fn.setTextPadding = function(options) {
		return this.each(function() {
			var tH = $(this).children('.text').outerHeight();
			var hH = $(this).outerHeight();
			var tP = (hH - tH) / 2;
			$(this).children('.text').css({'padding-top' : tP + 'px'});
		});
	};
	
	/* set position of highlight background element */
	$.fn.setHighlight = function(options) {
		return this.each(function() {
			var tH = $(this).children('.text').outerHeight();
			$('.body .content').css({'background-position' : '30px ' + tH / 2 + 'px'})
		});
	};

	if($.fn.cycle) {
		$.fn.cycle.transitions.fly = function($cont, $slides, opts) {
			var $el = $($slides[0]);
			var w = $el.width();
			var h = $el.height();
			opts.cssBefore = { /* left: w/6, */ display: 'block', opacity: 0, zIndex: 1 };
			opts.animIn = { left: 0, opacity: 1 };
			opts.animOut = { /* left: w/6, */ opacity: 0 };
			opts.cssAfter = { zIndex: 0, display: 'none' };
		}; 
	}
	
})(jQuery);

		
function setSidebarPadding() {
	var output = "";
	var sidBot = $('.sidebar .keydates').offset().top + $('.sidebar .keydates').outerHeight() - parseInt($('.sidebar .keydates').css('padding-bottom'));				
	var supBot = $('.body .content .supplemental.block-1').offset().top + $('.body .content .supplemental.block-1').outerHeight();				
	$('.sidebar .keydates').css({'padding-bottom' : supBot - sidBot + "px" });
}

function equalHeight(group) {
    tallest = 0;
    group.each(function() {
        thisHeight = $(this).height();
        if(thisHeight > tallest) {
            tallest = thisHeight;
        }
    });
    group.height(tallest);
}