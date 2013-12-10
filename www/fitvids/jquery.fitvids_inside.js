/*global jQuery */
/*jshint multistr:true browser:true */
/*!
* FitVids 1.0
*
* Copyright 2011, Chris Coyier - http://css-tricks.com + Dave Rupert - http://daverupert.com
* Credit to Thierry Koblentz - http://www.alistapart.com/articles/creating-intrinsic-ratios-for-video/
* Released under the WTFPL license - http://sam.zoy.org/wtfpl/
*
* Date: Thu Sept 01 18:00:00 2011 -0500
*/
(function($){

  "use strict";
  $.fn.fitVids = function( options ) {
    var settings = { customSelector: null },
        div = document.createElement('div'),
        ref = document.getElementsByTagName('base')[0] || document.getElementsByTagName('script')[0];

    div.className = 'fit-vids-style';
    div.innerHTML = '&shy;<style>               \
      .fluid-width-video-wrapper-inner {        \
         width: 100%;                           \
         position: relative;                    \
         padding: 0;                            \
      }                                         \
                                                \
      .fluid-width-video-wrapper-inner iframe,  \
      .fluid-width-video-wrapper-inner object,  \
      .fluid-width-video-wrapper-inner embed {  \
         position: absolute;                    \
         top: 0;                                \
         left: 0;                               \
         width: 100%;                           \
         height: 100%;                          \
      }                                         \
    </style>';

    ref.parentNode.insertBefore(div,ref);

    var $style = $(div.getElementsByTagName('style')[0]);


    if ( options ) {
      $.extend( settings, options );
    }

    return this.each(function(){
      var selectors = [
        "iframe[src*='player.vimeo.com']",
        "iframe[src*='www.youtube.com']",
        "iframe[src*='www.youtube-nocookie.com']",
        "iframe[src*='www.kickstarter.com']",
        "iframe[src*='/scripts/media/media_iframe.php']",
        "iframe[class*='media_work_frame']",
        "object",
        "embed"
      ];

      if (settings.customSelector) {
        selectors.push(settings.customSelector);
      }

      var $allVideos = $(this).find(selectors.join(',')); // search for iframes/objects/embeds

      $allVideos.each(function(){
        var $this = $(this);

        if (!$this.not("[id*='quicktimeWidget']").length) { return; } // deal with quicktime

        if (this.tagName.toLowerCase() === 'embed' && $this.parent('object').length || $this.parent('.fluid-width-video-wrapper-inner').length) { return; }

        var height = ( this.tagName.toLowerCase() === 'object' || ($this.attr('height') && !isNaN(parseInt($this.attr('height'), 10))) ) ? parseInt($this.attr('height'), 10) : $this.height(),
            width = !isNaN(parseInt($this.attr('width'), 10)) ? parseInt($this.attr('width'), 10) : $this.width(),
            aspectRatio = height / width;

        $this.wrap('<div class="fluid-width-video-wrapper-inner"></div>').parent('.fluid-width-video-wrapper-inner').css('padding-top', (aspectRatio * 100)+"%"); //set height?
        $this.removeAttr('height').removeAttr('width');
      });

    });
  };
})( jQuery );
