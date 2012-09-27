/*
 * Reason Background Uploads
 * Copyright © 2009 Carleton College.
 *
 * author: Eric Naeseth <enaeseth+reason@gmail.com>
 * requires: upload support tools (upload_support.js)
 * requires: jQuery "swfupload" plugin (jquery.swfupload.js)
 * requires: jQuery "upload button" plugin (jquery.uploadbutton.js)
 * requires: jQuery "upload queue" plugin (jquery.uploadqueue.js)
 */

var _swfupload_uri;

(function reason_background_uploads($) {
    var _setting_names = ['!receiver', '!user_session', '!transfer_session',
        'maximum_size', '!remover', 'user_id'];
    function get_upload_settings(el) {
        var form = $(el).parents("form").eq(0);
        if (form.length <= 0)
            return null;
        
        var settings = {}, name, required, storage;
        for (var i = 0; i < _setting_names.length; i++) {
            name = _setting_names[i];
            required = (name.charAt(0) == '!');
            if (required)
                name = name.substr(1);
            
            storage = form.find('input[name="_reason_upload_' + name + '"]');
            settings[name] = storage.val();
            
            if (!settings[name] && required)
                return null;
        }
        
        if (settings.maximum_size)
            settings.maximum_size = parse_file_size(settings.maximum_size);
        
        return settings;
    }
    
    function get_existing_file(upload_container) {
        var source = upload_container.prevAll('.uploaded_file').eq(0);
        var info = {};
        
        if (!source.length || source.css("display") == "none")
            return null;
        
        function get_value(class_name, attribute) {
            var check = $('.' + class_name, source);
            return (attribute) ? check.attr(attribute) : check.text();
        }
        
        function get_uri() {
            var repr = $('.representation', source).eq(0);
            if (!repr.length)
                return null;
            repr = repr.get(0);
            return repr.getAttribute("href") || repr.getAttribute("src");
        }
        
        info.filename = get_value('filename');
        info.uri = get_uri();
        
        var this_page = window.location.href;
        if (!info.filename && (!info.uri || info.uri == this_page)) {
            return null;
        }
        
        info.size = parse_file_size(get_value('filesize'));
        var dim_pattern = /^(\d+)[^\d]*(\d+)\s*$/;
        var raw_dims = get_value('dimensions');
        var match;
        if (raw_dims) {
            match = dim_pattern.exec(raw_dims);
            if (match) {
                info.dimensions = {
                    width: Number(match[1]),
                    height: Number(match[2])
                };
            }
        }
        return info;
    }
    
    function update_file_display(upload_container, info) {
        var display = upload_container.prevAll('.uploaded_file').eq(0);
        if (!display.length)
            return null;
        
        var dims = info.dimensions;
        var orig_dims = info.orig_dimensions;
        
        $('.filename', display).text(info.filename);
        $('.filesize', display).text(format_size(info.size));
        $('a.representation', display).attr('href', info.uri);
        var image = $('img.representation', display).attr('src', info.uri);
        if (dims) {
            image.css(dims);
            $('.dimensions', display).html('{width}&times;{height}'.
                interpolate(dims));
        }
        if (orig_dims) {
        	$('input[name="_reason_upload_orig_h"]').val(orig_dims.height);
                $('input[name="_reason_upload_orig_w"]').val(orig_dims.width);
        }
        display.show();
    }
    
    function create_upload_link(uploader, target, input, upload_settings) {
        var upload_name = input.attr('name') || input.attr('id');
        
        magical_upload_url = upload_settings.receiver;

        if (upload_settings.user_id != null)
        	magical_upload_url = upload_settings.receiver + "?user_id=" + upload_settings.user_id;
        
        var swfupload_settings = {
//            upload_url: upload_settings.receiver,
            upload_url: magical_upload_url,
            flash_url: _swfupload_uri,
            file_post_name: upload_name,
            file_upload_limit: "0",
            file_queue_limit: "1",
            post_params: {
                reason_sid: upload_settings.user_session,
                upload_sid: upload_settings.transfer_session
            }
        };
        
        var max_size_field = $('input[name="' + upload_name +
            '[MAX_FILE_SIZE]"]');
        var max_size = max_size_field.val();
        
        if (max_size) {
            swfupload_settings.file_size_limit = max_size + "B";
        }
        
        target.uploadButton(swfupload_settings, uploader);
    }
    
    var pending_uploads = 0;
    var on_upload_completion;
    var waiting_msg = $('<span class="submit_waiting">Waiting for files to ' +
        'finish uploading&hellip;</span>');
    function prevent_form_submission(form) {
        if (form.data('_uploader_submissions_tracked'))
            return;
        
        var target_bar = $('#discoSubmitRow td:last');
        
        function submission_attempt() {
            var submitter = this;
            
            if (pending_uploads > 0) {
                on_upload_completion = function deferred_submission() {
                    if (submitter.click)
                        submitter.click();
                    else if (submitter.submit)
                        submitter.submit();
                };
                target_bar.append(waiting_msg);
                return false;
            }
        }
        
        var submitters = $("button[type=submit], input[type=submit], " +
            "input[type=image]", form);
        submitters.click(submission_attempt);
        form.submit(submission_attempt);
        
        form.data('_uploader_submissions_tracked', true);
    }
    
    function track_uploads(upload_container) {
        upload_container.bind('fileQueued', function() {
            pending_uploads++;
        });
        
        upload_container.bind('uploadComplete', function() {
            pending_uploads--;
            if (pending_uploads <= 0 && on_upload_completion) {
                waiting_msg.html("Uploads finished. Continuing&hellip;").
                    removeClass('submit_waiting').addClass('submitting');
                on_upload_completion();
            }
        })
    }
    
    var upload_links = [];
    function reposition_buttons() {
        jQuery.each(upload_links, function() {
            $(this).repositionUploadButton();
        });
    }
    
    function replace_file_input(target) {
        if (typeof(target) != 'object' && this && this.nodeName)
            target = $(this);
        else if (!target)
            return;
        
        var input, container;
        if (target[0].nodeName == 'INPUT') {
            input = target;
            container = target.parent();
        } else {
            container = target;
            input = $('input[type=file]', container).eq(0);
        }
        if (!input.length)
            return;
        
        var upload_settings = get_upload_settings(input);
        if (!upload_settings)
            return;
            
        var form = input.parents("form").eq(0);
        
        
        var actions = $('<div class="actions">' +
            '<a class="upload" href="#choose">Choose a file&hellip;</a> ' +
            '<a class="reset" href="#reset"></a></div>');
        
        var existing = get_existing_file(container);
        var original = existing;
        if (existing) {
            actions.find('a.upload').html('Choose a new file&hellip;');
        }
        
        input.replaceWith(actions);
        var upload_name = input.attr('name') || input.attr('id');
        
        var upload_link = actions.find('a.upload');
        create_upload_link(container, upload_link, input, upload_settings);
        upload_links.push(upload_link);
        
        prevent_form_submission(form);
        track_uploads(container);
        
        container.uploadQueue(container);
        var upload_queue = container.find('.upload_queue');
        
        var file_info = null;
        
        function upload_removal_failed(req) {
            var message = "Failed to remove existing upload";
            try {
                if (req.getResponseHeader('Content-Type') == 'text/plain') {
                    message += ": " + req.responseText;
                }
            } catch (e) {
                message += ".";
            }
            
            alert(message);
        }
        
        function remove_upload(index, on_success, on_failure) {
            $.ajax({
                type: "POST",
                url: upload_settings.remover,
                data: {
                    upload_sid: upload_settings.transfer_session,
                    name: upload_name,
                    index: index
                },
                success: on_success,
                error: on_failure || upload_removal_failed
            });
        }
        
        function reset_to_original() {
            if (file_info) {
                remove_upload(file_info.index, function() {
                    file_info = null;
                    
                    if (original) {
                        update_file_display(container, original);
                    } else {
                        container.prevAll('.uploaded_file:last').hide('fast');
                    }
                    $('.reset', actions).empty();
                    
                    function reposition_button_later() {
                        setTimeout(reposition_buttons, 10);
                    }
                    
                    var filename_field = $("#filenameRow");
                    if (filename_field.length) {
                        filename_field.fadeIn('normal',
                            reposition_button_later);
                    } else {
                        reposition_button_later();
                    }
                });
            }
            
            return false;
        }
        
        container.bind('fileQueued', function(event, file) {
            if (file.size > upload_settings.maximum_size) {
                var fail_event = $.Event('queuedFileError');
                container.trigger(fail_event, [file, "File exceeds the " +
                    "maximum size of " +
                    format_size(upload_settings.maximum_size) + "."]);
                return;
            }
            
            function show_queue() {
                upload_queue.show('fast', function() {
                    //upload_queue[0].scrollIntoView();
                    container.swfupload('startUpload', file.id);
                });
            }
            
            container.hideUploadButton();
            var file_display = container.prevAll('.uploaded_file:last');
            var to_hide = [actions[0]];
            if (file_display.length)
                to_hide.push(file_display[0]);
            
            $(to_hide).hide('fast', show_queue);
        });
        
        container.bind('uploadSuccess', function(event, file, server_data) {
            var new_info;
            if (typeof(JSON) == 'object' && JSON.parse) {
                new_info = JSON.parse(server_data);
            } else {
                new_info = eval("(" + server_data + ")");
            }
            
            file_info = new_info[input.attr('name')];
            
            setTimeout(function() {
                upload_queue.hide('fast', function() {
                    upload_queue.empty();
                    update_file_display(container, file_info);
                    if (original) {
                        actions.find('a.reset').html("Reset to original.").
                            click(reset_to_original);
                    }
                    actions.find('a.upload').html('Choose a new file&hellip;');
                    actions.show('fast', function() {
                        setTimeout(reposition_buttons, 10);
                    });
                });
            }, 600);
        });
        
        container.bind('uploadError', function(event, file) {
            setTimeout(function() {
                upload_queue.hide('fast', function() {
                    upload_queue.empty();
                    var old_info = file_info || existing;
                    if (old_info)
                        update_file_display(container, old_info);
                    actions.show('fast', function() {
                        setTimeout(reposition_buttons, 10);
                    });
                })
            }, 3600);
        });
    }
    
    $.fn.richFileUpload = function() {
        return this.each(replace_file_input);
    };
})(jQuery);

jQuery(function get_swfupload_uri() {
    var tag = $('script[src*="rich_upload.js"]');
    var source = tag.attr('src');
    var match = /[?&]swf=(.*)$/.exec(source);
    if (match) {
        _swfupload_uri = unescape(match[1]);
    }
});

jQuery(function process_file_uploads() {
    var flash = get_flash_version();
    if (!flash || flash[0] < 9)
        return;
    
    $('.file_upload').richFileUpload();
});
