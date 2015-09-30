/*
 * jQuery Upload Button Plugin
 * Copyright © 2009 Carleton College.
 *
 * author: Eric Naeseth <enaeseth+reason@gmail.com>
 * requires: jQuery "swfupload" plugin (jquery.swfupload.js)
 */

(function upload_button_plugin($) {
    $.fn.uploadButton = function swf_upload_button(options, target) {
        this.each(function create_upload_button() {
            var self = $(this);

            var container = $('<div class="swfupload-container"><span>' +
                '</span></div>');
            $(this.ownerDocument.body).append(container);
            
            self.data('_swf_upload_actor', container);
            self.data('_swf_upload_clickable', self);
			var button_text = ( (navigator.appVersion.indexOf("Linux") != -1) ||
			       (navigator.platform.indexOf("Linux") != -1) ) ? '<span class="buttonTxt">Choose a file...</span>' : ""; 
           
           var swfupload_options = jQuery.extend({
                file_types: "*.*",
                file_types_description: "All Files",
                file_upload_limit: 0,
                flash_url: "swfupload.swf",

                button_placeholder: container.children('span')[0],
                button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
                button_width: "1000",
                button_height: "1000",
                button_text: button_text,
                button_text_style : ".buttonTxt { text-decoration: underline; color: #0000FF; font-size: 10px; }",
                button_text_left_padding: 0,
                button_text_top_padding: 0,
                button_cursor: SWFUpload.CURSOR.HAND,
                debug: false
            }, options);

            (target ? $(target) : self).swfupload(swfupload_options);
            reposition = function ()
            {
            	self.repositionUploadButton();
            	setTimeout(reposition, 500)
            }
            reposition();
        });
        return this;
    };
    
    $.fn.repositionUploadButton = function reposition_swf_upload_button() {
        var container = this.data('_swf_upload_actor');
        var target = this.data('_swf_upload_clickable');
        if (!container || !target || target.outerWidth() <= 0)
            return this;

        if (container.css('position') != 'absolute')
            container.css('position', 'absolute');
        if (container.css('overflow') != 'hidden')
            container.css('overflow', 'hidden'); 
        
        var target_pos = target.offset();
        container.offset({ top: target_pos.top, left: target_pos.left });
        container.css({
            width: target.outerWidth(),
            height: target.outerHeight()
        });
        
        container.find('object').attr({
            width: target.outerWidth(),
            height: target.outerHeight()
        });
        
        return this;
    };
    
    $.fn.hideUploadButton = function hide_swf_upload_button() {
        var button = this.data('_swf_upload_actor');
        if (!button)
            return this;
        button.hide();
        return this;
    };
})(jQuery);
