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
*
* Subsequently modified by Joseph Slote for Carleton College
*
* This script is different from the standard fitvids in two key respects:
* 1. It recognizes Reason media work iframes
* 2. By default, it acts equivalently to img {max-width:100%;} rather than img {width:100%;}.
*    Reverting to width:100% behavior is as simple as adding this (or more specific) CSS:
*
* .fluid-width-video-wrapper-outer {
* 	max-width:none;
* }
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
        "iframe[src*='player.vimeo.com']:not('.nofitvids, .nofitvids *')",
        "iframe[src*='www.youtube.com']:not('.nofitvids, .nofitvids *')",
        "iframe[src*='www.youtube-nocookie.com']:not('.nofitvids, .nofitvids *')",
        "iframe[src*='www.kickstarter.com']:not('.nofitvids, .nofitvids *')",
        "iframe[class*='media_work_iframe']:not('.nofitvids, .nofitvids *')",
        "iframe.video[class*='media_file_iframe']:not('.nofitvids, .nofitvids *')",
        "object:not('.nofitvids, .nofitvids *')",
        "embed:not('.nofitvids, .nofitvids *')"
      ];

      if (settings.customSelector) {
        selectors.push(settings.customSelector);
      }

      var $allVideos = $(this).find(' ' + selectors.join(',')), // search for iframes/objects/embeds
          videoIDCounter = 0;

      $allVideos.each(function(){
        var $this = $(this);

        //  |<---- deal with quicktime ------------------>|                                                                            |<------------- already been fitvidded ----------------->|
        if (!$this.not("[id*='quicktimeWidget']").length || (this.tagName.toLowerCase() === 'embed' && $this.parent('object').length) || $this.parent('.fluid-width-video-wrapper-inner').length) { return; }

        var height = ( this.tagName.toLowerCase() === 'object' || ($this.attr('height') && !isNaN(parseInt($this.attr('height'), 10))) ) ? parseInt($this.attr('height'), 10) : $this.height(),
            width = !isNaN(parseInt($this.attr('width'), 10)) ? parseInt($this.attr('width'), 10) : $this.width(),
            aspectRatio = height / width;

        //if |<--- is audio ----->| or |<-- isn't video & is short (for legacy av embeds) -->| then die
        if ($this.hasClass('audio') || (!$this.hasClass('video') && height < 100)) { return; } 
        
        var videoID = 'fitvid' + videoIDCounter++;

        $this.data('id',videoID);
        $this.wrap('<div class="fluid-width-video-wrapper-inner"></div>').parent('.fluid-width-video-wrapper-inner').css({'padding-top': (aspectRatio * 100)+"%"}); //set height?
        $this.parent('.fluid-width-video-wrapper-inner').wrap('<div class="fluid-width-video-wrapper-outer"></div>').parent('.fluid-width-video-wrapper-outer').addClass(videoID);
        //$this.removeAttr('height').removeAttr('width'); removed after extensive testing: 
        //CSS ALWAYS overrides non-css inline attributes (like height="300"). av.js uses these to store ratio data, so fitvids should leave them alone.

        $style.append("." + videoID + ' { max-width: ' + width + 'px;}');
      });
    });
  };

  $.fn.updateFitVid = function( newWidth ) {
     //if this thing has been fitvidded
     var $this = $(this);
     if ($this.parent('.fluid-width-video-wrapper-inner').length) { //check whether this iframe has been fitvidded originally.
      var id       = $this.data('id');
      var styleRE  = new RegExp("\." + id + " {(.*?)}");
      
      $this.parent('.fluid-width-video-wrapper-inner').parent('.fluid-width-video-wrapper-outer').css({'max-width': newWidth+'px'});
    }
  }

})( jQuery );