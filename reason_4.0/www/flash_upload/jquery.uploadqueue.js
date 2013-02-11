/*
 * jQuery Upload Queue Plugin
 * Copyright © 2009 Carleton College.
 *
 * author: Eric Naeseth <enaeseth+reason@gmail.com>
 * requires: jQuery "swfupload" plugin (jquery.swfupload.js)
 * requires: upload support tools (upload_support.js)
 */

(function upload_queue_plugin($) {
    function _create_upload_queue(target, uploader) {
        var self = $(this);
        
        var list = $('<ul class="upload_queue"></ul>');
        var file_items = {};
        
        function set_action(file, text, handler) {
            var id = (typeof(file) != 'object') ? file : file.id;
            var item = file_items[id];
            
            if (!item)
                return;
            
            var action = item.children('div.action');
            action.empty();
            var slug = text.replace(/\W+/, '-').toLowerCase() + '-' + id;
            var link = $('<a href="#' + slug + '">' + text + '</a>');
            link.click(function() { 
                handler();
                return false;
            });
            action.append(link);
            return link;
        }
        
        function clear_action(file) {
            var id = (typeof(file) == 'string') ? file : file.id;
            var item = file_items[id];
            
            if (!item)
                return;
            item.children('div.action').empty();
        }
        
        var reload_verb = ($.browser.msie ? 'refresh' : 'reload');
        var http_errors = {
            400: "Session error; please " + reload_verb + " this page and " +
                "try again.",
            403: "Permission denied to upload this file.",
            413: "File is unacceptably large; upload rejected.",
            415: "File is not a permitted type; upload rejected.",
            500: "An internal server error occurred. Please try again later.",
            501: "Unable to convert the uploaded file to a web-friendly image. Please try saving in a different format.",
            503: "The upload service is temporarily unavailable. Please try " +
                "again later.",
            422: "The uploaded image's dimensions are too large for the server to process. Try a smaller image."
        };
        
        var event_handlers = {
            fileQueued: function(event, file) {
                var html = '<li><div class="file">' +
                    '<span class="filename">{file.name}</span>' +
                    '<span class="size">{formatted_size}</span></div>' +
                    '<div class="queued progress">' +
                    '<div class="amount">&nbsp;</div></div>' +
                    '<div class="action"></div>' +
                    '<div class="message"></div></li>';
                var item = $(html.interpolate({
                    file: file,
                    formatted_size: format_size(file.size)
                }));
                item.find('.amount').css('width', '0%');
                
                file_items[file.id] = item;
                set_action(file, 'Start', function uq_start_file() {
                    uploader.swfupload('startUpload', file.id);
                });
                list.append(item);
            },
            
            fileQueueError: function(event, file, errorCode, message) {
                var html = '<li class="unqueued"><div class="file">' +
                    '<span class="filename">{file.name}</span>' +
                    '<span class="size">{formatted_size}</span></div>' +
                    '<div class="failure message"></div>' +
                    '<div class="action"></div></li>';
                var item = $(html.interpolate({
                    file: file,
                    formatted_size: format_size(file.size)
                }));
                item.find('.failure').text(message);
                file_items[file.id] = item;
                set_action(file, 'OK', function hide_failed_file() {
                    item.remove();
                    delete file_items[file.id];
                });
                list.append(item);
            },
            
            queuedFileError: function(event, file, message) {
                var item = file_items[file.id];
                if (!item)
                    return;
                item.addClass('unqueued');
                item.find('.progress, .action').remove();
                item.find('.message').addClass('failure').text(message);
                item.append('<div class="action"></div>');
                set_action(file, 'OK', function hide_failed_file() {
                    item.remove();
                    delete file_items[file.id];
                });
            },
            
            uploadStart: function(event, file) {
                var item = file_items[file.id];
                
                item.find('.message').empty().removeClass('failure');
                item.find('.progress').removeClass('queued');
                item.find('.progress > .amount').css('width', '0%');
                
                set_action(file, 'Cancel', function cancel_upload() {
                    uploader.swfupload('cancelUpload', file.id, true);
                });
            },
            
            uploadProgress: function(event, file, bytesLoaded, bytesTotal) {
                var item = file_items[file.id];
                
                var width = parseInt(100 * (bytesLoaded / bytesTotal)) + '%';
                item.find('.progress > .amount').css('width', width);
            },
            
            uploadSuccess: function(event, file, serverData) {
                var item = file_items[file.id];
                clear_action(file);
                
                item.find('.progress > .amount').addClass('done');
            },
            
            uploadError: function(event, file, errorCode, message) {
            	var item = file_items[file.id];
                clear_action(file);
                
                var http_code;
                if (errorCode == SWFUpload.UPLOAD_ERROR.HTTP_ERROR) {
                    http_code = /[1-6]\d{2}/.exec(message);
                    if (http_code) {
                        http_code = http_code[0];
                        if (http_code in http_errors)
                            message = http_errors[http_code];
                    }
                }
                
                item.find('.progress > .amount').addClass('failed');
                item.find('.message').addClass('failure').text(message);
            }
        };
        
        var name;
        for (name in event_handlers) {
            uploader.bind(name, event_handlers[name]);
        }
        
        target.append(list);
        return this;
    }
    
    $.fn.uploadQueue = function swf_upload_queue(upload_element) {
        upload_element = $(upload_element);
        
        this.each(function create_upload_queue() {
            _create_upload_queue($(this), upload_element);
        });
        return this;
    };
})(jQuery);
