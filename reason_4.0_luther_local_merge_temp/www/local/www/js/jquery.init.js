var carousel = {
    init : function(settings) {
        carousel.config = { };
		carousel.config.$wrapper = $('#carousel');
		carousel.config.$slider = carousel.config.$wrapper.find('> ol');
		carousel.config.sliderWidth = carousel.config.$slider.innerWidth();		
		carousel.config.$items = carousel.config.$slider.find('> li');
		carousel.config.itemsTotal = carousel.config.$items.length;
		carousel.config.$single = carousel.config.$items.filter(':first');
		carousel.config.singleWidth = carousel.config.$single.outerWidth();
		carousel.config.leftIncreament = 48;
		carousel.config.leftIncreament = 48;
		carousel.config.initialRightOffset = carousel.config.sliderWidth - carousel.config.singleWidth;

        // allow overriding the default config
        $.extend(carousel.config, settings);
		if(carousel.config.$wrapper.length < 1)  {
			return false;
		}
        carousel.setup();        
    },

    setup : function() {
		carousel.config.$slider.detach();
	  
		if(typeof images !== "undefined") {
 			var slideTemplate = '<li class="slide open"><img alt="" height="288" src="${src}" width="475" /><span></span></li> ';
			$.tmpl( slideTemplate, images ).appendTo(carousel.config.$slider);
		}



 		carousel.config.$items = carousel.config.$slider.find('> li');


		carousel.config.$items.filter(':last').after(carousel.config.$items.slice(0, 6).clone().addClass('cloned').removeClass('active'));
		carousel.config.$items = carousel.config.$slider.find('> li');
		carousel.config.itemsTotal = carousel.config.$items.length;
 
 		carousel.config.$items
            .each(function(index) {
				var $this = $(this);
				$this
				 .removeAttr('id')
				 .css({zIndex: carousel.getZIndex(index), left: carousel.getLeftOffset(index)})
				 .addClass('slide-' + ++index);
				 $.data(this, 'index', index);
				 $.data(this, 'left', index * carousel.config.leftIncreament);
			});
		carousel.config.$clone = carousel.config.$items.clone(true);
		carousel.config.$slider.appendTo(carousel.config.$wrapper[0]);					
    },
	
	
	getZIndex: function(index) {
		return 10 * (carousel.config.itemsTotal - index); 
	},
	
	getLeftOffset: function(index) {
		return carousel.config.leftIncreament * index; 
	},	

	getRightOffset: function(index) {
		return carousel.config.initialRightOffset -  carousel.config.leftIncreament * index; 
	},		
	
	
	openSlide: function(slide, duration) {		
		duration = typeof(duration) != 'undefined' ? duration : 600;
		var $this = slide;
		if(! $this.hasClass('open')){
			
			carousel.openSlide($this.next());
			$this.addClass('open');
			$this.animate({left: "+=431px"}, duration, 'easeOutQuart');

		}
		else{				
			carousel.closeSlide($this.prev());						
		}		
		$this.siblings().removeClass('active');
		$this.addClass('active');	
	},

	closeSlide: function(slide, duration) {
		 duration = typeof(duration) != 'undefined' ? duration : 600;		
		var $this = slide;		
		if($this.hasClass('open')){
			$this.removeClass('open');
			$this.animate({left: "-=431px"}, 0, 'easeOutQuart');	
			carousel.config.$wrapper.stop().animate({ scrollLeft : $this.data('left')}, 150, function () {
   if (carousel.config.$wrapper.scrollLeft() >= (carousel.config.itemsTotal-6)*48) {

var $clone = carousel.config.$clone.clone(true);
      carousel.config.$wrapper.scrollLeft(0);
carousel.config.$items.remove();
carousel.config.$slider.append($clone);
		carousel.config.$items = carousel.config.$slider.find('> li');
		carousel.config.itemsTotal = carousel.config.$items.length;
//  carousel.openSlide(carousel.config.$items.filter(':first')[0], 0);


    } 
																												 }
);				
			carousel.closeSlide($this.prev());
		}	
	}
	
	
}

		
/* Binding Events to the Slide Contents */

  $('#carousel').click(function(event) {
    var $thisSlide, $tgt = $(event.target);
	$thisSlide = $('#carousel li.active');
/*	
    if ($tgt.is('li.slide')) {
      $thisSlide = $tgt;
    } else if ($tgt.parents('li.slide').length) {
      $thisSlide = $tgt.parents('li.slide:first');
    }
 */
	if ($thisSlide.is('.active')) {
     carousel.openSlide($thisSlide.next());
	}

  });

// tablesorter.js is used to sort tables. See http://tablesorter.com/
// add parser through the tablesorter addParser method to sort class year
    $.tablesorter.addParser({ 
        // set a unique id 
        id: 'athlete_class_year', 
        is: function(s) { 
            // return false so this parser is not auto detected 
            return false; 
        }, 
        format: function(s) { 
            // format your data for normalization 
            return s.replace(/Fy/,0).replace(/So/,1).replace(/Jr/,2).replace(/Sr/,3).replace(/Gr/,4); 
        }, 
        // set type, either numeric or text 
        type: 'numeric' 
    }); 
// add parser through the tablesorter addParser method to sort height 
// need to insert 0 before single digit inch measurements (e.g. 9 -> 09)
    $.tablesorter.addParser({ 
        // set a unique id 
        id: 'athlete_height', 
        is: function(s) { 
            // return false so this parser is not auto detected 
            return false; 
        }, 
        format: function(s) { 
            // format your data for normalization 
            return s.replace(/(\d+\'\s)(\d\")/, "$10$2");
        }, 
        // set type, either numeric or text 
        type: 'text' 
    }); 
     

$(document).ready(function() {
	carousel.init();
	$("#search label").overlabel();
	$(".emergency").effect("pulsate", { times:3 }, 2000);
        $("table").tablesorter();
        $("a.cluetip_athlete").cluetip({
                arrows: true,
                cursor: 'pointer',
                local: true,
                clickThrough: true,
                waitImage: false,
                cluetipClass: 'jtip'
        });
});

( function( $ ) {
 
    // plugin definition
    $.fn.overlabel = function( options ) {
 
        // build main options before element iteration
        var opts = $.extend( {}, $.fn.overlabel.defaults, options );
 
        var selection = this.filter( 'label[for]' ).map( function() {
 
            var label = $( this );
            var id = label.attr( 'for' );
            var field = document.getElementById( id );
 
            if ( !field ) return;
 
            // build element specific options
            var o = $.meta ? $.extend( {}, opts, label.data() ) : opts;
 
            label.addClass( o.label_class );
 
            var hide_label = function() { label.css( o.hide_css ) };
            var show_label = function() { this.value || label.css( o.show_css ) };
 
            $( field )
                 .parent().addClass( o.wrapper_class ).end()
                 .focus( hide_label ).blur( show_label ).each( hide_label ).each( show_label );
 
            return this;
 
        } );
 
        return opts.filter ? selection : selection.end();
    };
 
    // publicly accessible defaults
    $.fn.overlabel.defaults = {
 
        label_class:   'overlabel-apply',
        wrapper_class: 'overlabel-wrapper',
        hide_css:      { 'text-indent': '-10000px' },
        show_css:      { 'text-indent': '0px', 'cursor': 'text' },
        filter:        false
 
    };
 
} )( jQuery );


/*
 * marcomm functions
 * Faust Gertz
 */
(function($){
	jQuery.luther = {
		columnize:
			function() { 
				
				var $leftColumn = $('<div class="column1" />')
									.insertBefore('.two-column > div.section:first')
									.append($('.two-column div.section')),
					$rightColumn = $('<div class="column2" />')
									.insertAfter($leftColumn),
					$sections = $leftColumn
								 .find('> div.section'),
					targetHeight = $leftColumn
									.height() / 2 + $sections.position().top;
				$sections
				 .each(
					   function(i) {
							if(($(this).position().top)>=targetHeight) {
								$rightColumn.append($sections.slice(i));
								return false;
							}
						}
				 );
			},
	}
})(jQuery);


jQuery(function($){ 
  var $twocolumn = $('.two-column');  
  if ($twocolumn.length > 0) {
   $.luther.columnize();
  } 
}); 
