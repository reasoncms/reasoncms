/**
 * jQuery Form Builder Plugin
 * Copyright (c) 2009 Mike Botsko, Botsko.net LLC (http://www.botsko.net)
 * http://www.botsko.net/blog/2009/04/jquery-form-builder-plugin/
 * Originally designed for AspenMSM, a CMS product from Trellis Development
 * Licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * Copyright notice and license must remain intact for legal use
 *
 * Modified for ReasonCMS in 2013 by Andrew Bacon
 *
*/ (function ($) {
$.fn.formbuilder = function (options) {
	// Extend the configuration options with user-provided
	var defaults = {
		save_url: false,
		load_url: false,
		control_box_target: false,
		useJson: true,
		serialize_prefix: 'frmb',
		messages: {
			save: "Save",
			add_new_field: "Add New Field...",
			text: "Text Field",
			title: "Title",
			comment: "Comment",
			hidden: "Hidden Field",
			paragraph: "Paragraph",
			checkboxes: "Checkboxes",
			radio: "Radio",
			select: "Select List",
			text_field: "Text Field",
			label: "Label",
			comment_field: "Comment Field",
			hidden_field: "Hidden Field",
			value: "Value",
			paragraph_field: "Paragraph Field",
			select_options: "Select Options",
			add: "Add",
			checkbox_group: "Checkbox Group",
			remove_message: "Are you sure you want to remove this element?",
			remove: "Remove",
			radio_group: "Radio Group",
			selections_message: "Allow Multiple Selections",
			hide: "Hide",
			stopEditing: "Stop Editing",
			required: "Required",
			show: "Show",
			defaultVal: "Default Value",
			submitLabel: "Submit button",
			resetLabel: "Reset button",
			submit_and_reset: "Submit button"
		}
	};
	var opts = $.extend(defaults, options);
	var frmb_id = 'frmb-' + $('ul[id^=frmb-]').length++;
	//return this.each(function () {
	var ul_obj = $(this).append('<ul id="' + frmb_id + '" class="frmb"></ul>').find('ul');
	var field = '';
	var field_type = '';
	var last_id = 1;
	var help;
	var json_data;
	// Add a unique class to the current element
	$(ul_obj).addClass(frmb_id);
	// load existing form data from an external URL

	if (opts.load_url) {
		$.ajax({
			type: "GET",
			url: opts.load_url,
			success: function (data) {
				if (opts.useJson) {
					json_data = data;
					JsonToMode(data, "preview");
				}
			}
		});
	}

	// Create form control select box and add into the editor
	var controlBox = function (target) {
		var select = '';
		var box_content = '';
		var save_button = '';
		var box_id = frmb_id + '-control-box';
		var save_id = frmb_id + '-save-button';
	// Add the available options
	select += '<option value="0">' + opts.messages.add_new_field + '</option>';
	select += '<option value="input_text">' + opts.messages.text + '</option>';
	select += '<option value="textarea">' + opts.messages.paragraph + '</option>';
	select += '<option value="checkbox">' + opts.messages.checkboxes + '</option>';
	select += '<option value="radio">' + opts.messages.radio + '</option>';
	select += '<option value="select">' + opts.messages.select + '</option>';
	select += '<option value="comment">' + opts.messages.comment + '</option>';
	select += '<option value="hidden">' + opts.messages.hidden + '</option>';
	//debug:
	// select += '<option value="submit_and_reset">' + "submit_and_reset" + '</option>';
	// Build the control box and search button content
	box_content = '<select id="' + box_id + '" class="frmb-control">' + select + '</select>';
	// Insert the control box into page
	box_content += '<button id="closeAllFields" type="button" disabled>Stop editing all fields</button>'
	if (!target) {
		$(ul_obj).before(box_content);
	} else {
		$(target).append(box_content);
	}
	// Insert the search button
	$(ul_obj).after(save_button);
	// Set the form save action
	$('#' + save_id).click(function () {
		save();
		return false;
	});
	// Add a callback to the select element
	$('#' + box_id).change(function () {
		appendNewField($(this).val());
		$(this).val(0).blur();
	// This solves the scrollTo dependency
	//	$('html, body').animate({
	//		scrollTop: $('#frm-' + (last_id - 1) + '-item').offset().top
	//	}, 500);
	return false;
});
}(opts.control_box_target);
	
	appendNewPreview = function(type, values, options, required, defaultValue, id) {
		var inputMap;
		
		rowMap = {
			'class': 'frmb_preview_row',
			name: id
		};
		thisRow = $("<div>").attr(rowMap);
		inputLabel = $("<span>").attr("class", "frmb_preview_label");
		thisRow.append(inputLabel);

		switch(type) {
			case "input_text":
			    inputLabel.html(values[0] + ( (required == "true") ? ':*' : ':') );
				element = $("<div>");
				inputElement = $("<input>");
				inputElement.attr(
				{
					type: "text",
					value: defaultValue,
					'class': "frmb_preview_inputtext_row"
				});
				element.append(inputElement);
			break;

			case "textarea":
				inputLabel.html(values[0] + ( (required == "true") ? ':*' : ':') );
				element = $("<div>");
				textareaElement = $("<textarea>");
				textareaElement.text(defaultValue);
				textareaElement.attr(
				{
					type: "text",
					rows: "6",
					cols: "40",
				});
				element.append(textareaElement);
			break;

			// hidden needs some work ... lets not show the label in the preview
			case "hidden":
				inputLabel.text(values[0] + " ");
				inputLabel.append($("<em>(hidden)</em>"));
				element = $("<div>");
				element.text(values[1]);
				element.attr("class", "frmb_preview_hidden");
			break;

			case "comment":
				inputLabel.remove();
				element = $("<p>");
				element.append(values[0]);
				element.attr("class", "frmb_preview_comment");
			break;

			case "submit_and_reset":
				inputLabel.remove();
				element = $("<button>");
				element.text(values['submit']);
				element.attr("class", "frmb_preview_submit");
				thisRow.attr("name", "submit_and_reset");
			break;

			case "select":
				element = $("<div>");
				inputLabel.html(options[0] + ( (required == "true") ? ':*' : ':') );
				element.attr("class", "frmb_preview_select");
				// Fix this.
				select = $("<select class='frmb_preview_select_row'>");
				$.each(values, function () {
					select.append("<option " + (this[1] == 'checked' ? "selected='selected' ": '') + "class='frmb_preview_select' >" + this[0] + "</option>");
				});
				element.append(select);
			break;

			case "radio":
				element = $("<div>");
				inputLabel.html(options[0] + ( (required == "true") ? ':*' : ':') );
				element.attr("class", "frmb_preview_radio");
				$.each(values, function () {
					element.append("<span class='frmb_preview_radiobutton_row'><input type='radio'" + (this[1] == 'checked' ? "checked=''": '') + "class='frmb_preview_radiobutton' /><label class='frmb_preview_radiobutton_label'>" + this[0] + "</label></span>");
				});
			break;

			case "checkbox":
				inputLabel.html(options[0] + ( (required == "true") ? ':*' : ':') );
				element = $("<div>");
				$.each(values, function() {
					element.append("<span class='frmb_preview_checkbox_row'><input type='checkbox'" + (this[1] == 'checked' ? "checked=''": '') + "class='frmb_preview_checkbox' /><label class='frmb_preview_checkbox_label'>" + this[0] + "</label></span>");
				});
				element.attr("class", "frmb_preview_checkboxgroup");
			break;
		}

		thisRow.append(element);

		if (type != 'submit_and_reset')
			oldRow = $("[name=" + id + "]");
		else {
			oldRow = $("[name=submit_and_reset]");
		}

		if (oldRow.length > 0)
			oldRow.replaceWith(thisRow);
		else
			if (type != 'submit_and_reset')
				$(ul_obj).find(".frmb_preview_row[name='submit_and_reset']").before(thisRow);
			else
				$(ul_obj).append(thisRow);
	};


	// Json parser to build the form builder
	var JsonToMode = function (json, mode) {
		var values = '';
		var options = false;
		var required = false;
		var defaultValue = false;
		var filteredJson = $.extend({}, json);
		// Parse json

		if (json['options'] != undefined) {
			if (mode == "preview") {
				appendNewPreview('submit_and_reset', json['options']);
				delete filteredJson['options'];
			} else if(mode == "edit" ) {
				appendNewField('submit_and_reset', json['options']);
				delete filteredJson['options'];
			}
		}
			$.each(filteredJson, function () {
				switch (this.cssClass) {

					// checkbox type
					case 'checkbox':
						options = [this.title];
						values = [];
						$.each(this.values, function () {
							values.push([this.value, this.baseline]);
						});
					break;

					// radio type
					case 'radio':
						options = [this.title];
						values = [];
						// lots of undefineds and falses here... that must be the issue.
						$.each(this.values, function () {
							values.push([this.value, this.baseline]);
						});
					break;

					// select type
					case 'select':
						options = [this.title, this.multiple];
					values = [];
					$.each(this.values, function () {
						values.push([this.value, this.baseline]);
					});
					break;

					case 'hidden':
						values = this.values;
					break;

					default:
						values = [this.values];
					break;
				}

				defaultValue = (this.defaultValue != false) ? this.defaultValue : '';
				if (mode === "preview")
					appendNewPreview(this.cssClass, values, options, this.required, defaultValue, this.id);
				else if (mode === "edit")
					appendNewField(this.cssClass, values, options, this.required, defaultValue, this.id);
			});
	};
	// Wrapper for adding a new field
	var appendNewField = function (type, values, options, required, defaultValue, objectID) {
		field = '';
		field_type = type;
		if (typeof (values) === 'undefined') {
			values = '';
		}
		$("#closeAllFields").removeAttr("disabled");
		if (typeof (defaultValue) === 'undefined') defaultValue = '';
		switch (type) {
			case 'input_text':
				appendTextInput(values, required, defaultValue, objectID);
			break;
			case 'textarea':
				appendTextarea(values, required, defaultValue, objectID);
			break;
			case 'checkbox':
				appendCheckboxGroup(values, options, required, objectID);
			break;
			case 'radio':
				appendRadioGroup(values, options, required, objectID);
			break;
			case 'select':
				appendSelectList(values, options, required, objectID);
			break;
			case 'comment':
				appendComment(values, objectID);
			break;
			case 'hidden':
				appendHiddenField(values, objectID);
			break;
			case 'submit_and_reset':
				appendSubmitAndReset(values, objectID);
			break;
		}
	};
	// Hook for submit and reset button labels.
	var appendSubmitAndReset = function (values) {
		buttonText = ['Submit'];
		if (values === '') values = buttonText;
		field += '<div class="frm-fld"><label>' + opts.messages.submitLabel + '</label>';
		field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + htmlspecialchars(values['submit']) + '" /></div>';
		help = '';
		options = {
			'removable': false,
			'objectID': 'submit_and_reset'
		};
		appendFieldLi(opts.messages.submit_and_reset, field, null, help, options);
	};
	// Adds a comment of some kind to the form.
	var appendComment = function (values, objectID) {
		field += '<label>' + opts.messages.comment + '</label>';
		field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + htmlspecialchars(values) + '" />';
		help = '';
		appendFieldLi(opts.messages.comment_field, field, null, help, {'objectID': objectID});
	};
	// hidden fields input type="hidden"
	var appendHiddenField = function (values, objectID) {
		if (values === '') values = ['', ''];
		field += '<div class="frm-fld"><label>' + opts.messages.label + '</label>';
		field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + htmlspecialchars(values[0]) + '" /></div>';
		field += '<div class="frm-fld"><label>' + opts.messages.value + '</label>';
		field += '<input class="fld-default" id="title-' + (last_id + 1) + '" type="text" value="' + htmlspecialchars(values[1]) + '" /></div>';
		help = '';
		appendFieldLi(opts.messages.hidden_field, field, null, help, {'objectID': objectID});
	};

	// single line input type="text"
	var appendTextInput = function (values, required, defaultValue, objectID) {
		field += '<label>' + opts.messages.label + '</label>';
		field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + htmlspecialchars(values) + '" />';
		field += '<label>' + opts.messages.defaultVal + '</label>';
		field += '<input class="fld-default" id="title-' + last_id + '_def" type="text" value="' + htmlspecialchars(defaultValue) + '" />';

		help = '';
		appendFieldLi(opts.messages.text, field, required, help, {'objectID': objectID});
	};
	// multi-line textarea
	var appendTextarea = function (values, required, defaultValue, objectID) {
		field += '<label>' + opts.messages.label + '</label>';
		field += '<input class="fld-title" id="title-' + last_id + '" type="text" value="' + htmlspecialchars(values) + '" />';
		field += '<label>' + opts.messages.defaultVal + '</label>';
		field += '<input class="fld-default" id="title-' + last_id + '_def" type="text" value="' + htmlspecialchars(defaultValue) + '" />';
		help = '';
		appendFieldLi(opts.messages.paragraph_field, field, required, help, {'objectID': objectID});
	};
	// adds a checkbox element
	var appendCheckboxGroup = function (values, options, required, objectID) {
		var title = '';
		if (typeof (options) === 'object') {
			title = options[0];
		}
		field += '<div class="chk_group">';
		field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
		field += '<input type="text" name="title" value="' + htmlspecialchars(title) + '" /></div>';
		field += '<div class="false-label">' + opts.messages.select_options + '</div>';
		field += '<div class="fields">';
		if (typeof (values) === 'object') {
			for (i = 0; i < values.length; i++) {
				field += checkboxFieldHtml(values[i]);
			}
		}
		else {
			field += checkboxFieldHtml('');
		}
		field += '<div class="add-area"><a href="#" class="add add_ck">' + opts.messages.add + '</a></div>';
		field += '</div>';
		field += '</div>';
		help = '';
		appendFieldLi(opts.messages.checkbox_group, field, required, help, {'objectID': objectID});
	};
	// Checkbox field html, since there may be multiple
	var checkboxFieldHtml = function (values, objectID) {
		var checked = false;
		var value = '';
		if (typeof (values) === 'object') {
			value = values[0];
			checked = (values[1] === 'false' || typeof(values[1]) == 'undefined') ? false : true;
		}
		field = '';
		field += '<div>';
		field += '<input type="checkbox"' + (checked ? ' checked="checked"' : '') + ' />';
		field += '<input type="text" value="' + htmlspecialchars(value) + '" />';
		field += '<a href="#" class="remove" title="' + opts.messages.remove_message + '">' + opts.messages.remove + '</a>';
		field += '</div>';
		return field;
	};
	// adds a radio element
	var appendRadioGroup = function (values, options, required, objectID) {
		var title = '';
		if (typeof (options) === 'object') {
			title = options[0];
		}
		field += '<div class="rd_group">';
		field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
		field += '<input type="text" name="title" value="' + htmlspecialchars(title) + '" /></div>';
		field += '<div class="false-label">' + opts.messages.select_options + '</div>';
		field += '<div class="fields">';
		if (typeof (values) === 'object') {
			for (i = 0; i < values.length; i++) {
				field += radioFieldHtml(values[i], 'frm-' + last_id + '-fld');
			}
		}
		else {
			field += radioFieldHtml('', 'frm-' + last_id + '-fld');
		}
		field += '<div class="add-area"><a href="#" class="add add_rd">' + opts.messages.add + '</a></div>';
		field += '</div>';
		field += '</div>';
		help = '';
		appendFieldLi(opts.messages.radio_group, field, required, help, {'objectID': objectID});
	};
	// Radio field html, since there may be multiple
	var radioFieldHtml = function (values, name) {
		var checked = false;
		var value = '';
		if (typeof (values) === 'object') {
			value = values[0];
			checked = (values[1] === 'false' || typeof(values[1]) === 'undefined') ? false : true;
		}
		field = '';
		field += '<div>';
		field += '<input type="radio"' + (checked ? ' checked="checked"' : '') + ' name="radio_' + name + '" />';
		field += '<input type="text" value="' + htmlspecialchars(value) + '" />';
		field += '<a href="#" class="remove" title="' + opts.messages.remove_message + '">' + opts.messages.remove + '</a>';
		field += '</div>';
		return field;
	};
	// adds a select/option element
	var appendSelectList = function (values, options, required, objectID) {
		var multiple = false;
		var title = '';
		if (typeof (options) === 'object') {
			title = options[0];
			multiple = options[1] === 'true' ? true : false;
		}
		field += '<div class="opt_group">';
		field += '<div class="frm-fld"><label>' + opts.messages.title + '</label>';
		field += '<input type="text" name="title" value="' + htmlspecialchars(title) + '" /></div>';
		field += '';
		field += '<div class="false-label">' + opts.messages.select_options + '</div>';
		field += '<div class="fields">';
		//field += '<input type="checkbox" name="multiple"' + (multiple ? 'checked="checked"' : '') + '>';
		//field += '<label class="auto">' + opts.messages.selections_message + '</label>';
		if (typeof (values) === 'object') {
			for (i = 0; i < values.length; i++) {
				field += selectFieldHtml(values[i], multiple);
			}
		}
		else {
			field += selectFieldHtml('', multiple);
		}
		field += '<div class="add-area"><a href="#" class="add add_opt">' + opts.messages.add + '</a></div>';
		field += '</div>';
		field += '</div>';
		help = '';
		appendFieldLi(opts.messages.select, field, required, help, {'objectID': objectID});
	};
	// Select field html, since there may be multiple
	var selectFieldHtml = function (values, multiple) {
		if (multiple) {
			return checkboxFieldHtml(values);
		}
		else {
			return radioFieldHtml(values);
		}
	};
	// Appends the new field markup to the editor
	var appendFieldLi = function (title, field_html, required, help, options) {
		options = typeof options !== 'undefined' ? options : {
			'removable': true,
			'objectID': ''
		};

		if (required) {
			required = required === 'true' ? true : false;
		}
		var li = '';
		if (!options.objectID)
			options.objectID = $().makeThorID();
		li += '<li id="frm-' + last_id + '-item" class="' + field_type + 'Type" name="' + options.objectID + '" >';
		li += '<div class="legend">';
		// li += '<a id="frm-' + last_id + '" class="toggle-form" href="#">' + opts.messages.hide + '</a> ';
		li += '<a id="frm-' + last_id + '" class="stopEditing" href="#">' + opts.messages.stopEditing + '</a> ';
		if (options.removable !== false) li += '<a id="del_' + last_id + '" class="del-button delete-confirm" href="#" title="' + opts.messages.remove_message + '"><span>' + opts.messages.remove + '</span></a>';
		li += '<strong id="txt-title-' + last_id + '">' + title + '</strong></div>';
		li += '<div id="frm-' + last_id + '-fld" class="frm-holder">';
		li += '<div class="frm-elements">';
		if (field_type != "comment" && field_type != 'hidden' && field_type != 'submit_and_reset') {
			li += '<div class="frm-fld"><label for="required-' + last_id + '">' + opts.messages.required + '</label>';
			li += '<input class="required" type="checkbox" value="1" name="required-' + last_id + '" id="required-' + last_id + '"' + (required ? ' checked="checked"' : '') + ' /></div>';
		}
		li += field;
		li += '</div>';
		li += '</div>';
		li += '</li>';
		oldRow = $("[name=" + options.objectID + "]");
		li = $(li);

		if (oldRow.length > 0)
			oldRow.replaceWith(li);
		else
			if ($(ul_obj).find(".frmb_preview_row[name='submit_and_reset']").length !== 0)
				$(ul_obj).find(".frmb_preview_row[name='submit_and_reset']").before(li);
		else {
			$(ul_obj).append(li);
		}

		//                  $('#frm-' + last_id + '-item').hide();
		//					$('#frm-' + last_id + '-item').animate({
		//						opacity: 'show',
		//						height: 'show'
		//					}, 'slow');
		last_id++;
	};

	var saveRow = function(rowToSave) {
		var item_id = $(rowToSave).attr('name');
		var newJSON = $(rowToSave).serializeFormListJSON();
		var saved = 0;
		newJSON.id = item_id;
		if (item_id == 'submit_and_reset') {
			json_data['options'] = newJSON['values'];
			return;
		}

		// Does the item exist in the data already? If so, update it.
		$.each(json_data, function(Itindex) {
			if (this.id == item_id) {
				json_data[Itindex] = newJSON;
				//this = newJSON;
				saved = true;
				return false;
			}
			else saved++;
		});
		if (saved !== true)
			json_data[saved] = newJSON;

	};
	var removeRow = function(rowToRemove) {
		var item_id = $(rowToRemove).attr('name');
		$.each(json_data, function(ItemIndex) {
			if (this.id == item_id) {
				delete json_data[ItemIndex];
			};
		})
	};
	var switchMode = function (rowToSwitch, mode)
	{
		if (mode == 'edit') {
			item_id = $(rowToSwitch).attr('name');
			$.each(json_data, function() {
				if (this.id == item_id)
					{
						JsonToMode({1: this}, "edit");
						return false;
					}
			});
			if (item_id == 'submit_and_reset')
				JsonToMode({'options': json_data['options']}, 'edit');
			return false;
		} else if (mode == 'preview') {
			item_id = $(rowToSwitch).attr('name');
			saveRow(rowToSwitch);
			$.each(json_data, function() {
				if (this.id == item_id)
					{
						JsonToMode({1: this}, "preview");
						return false;
					}
			});
			if (json_data['options'] != undefined)
				JsonToMode({'options': json_data['options']}, "preview");
			return false;
		}
	};

	reSortJSON = function(target) {
        var newArray = {};
        var i = 1;
		$(target).children().each(function () {

            var current_element_id = $(this).attr("name");
			if (current_element_id == 'submit_and_reset') {
            	newArray['options'] = json_data['options'];
            	return;
            }
             $.each(json_data, function(key, value) {
                if (this.id == current_element_id) {
                    newArray[i] = this;
                    return false;
                }
             });
             i++;
		});
		json_data = newArray;
	};


			// handle field delete links
			$(document).on('click', '.remove', function () {
				$(this).parent('div').animate({
					opacity: 'hide',
					height: 'hide',
					marginBottom: '0px'
				}, 'fast', function () {
					$(this).remove();
				});
				return false;
			});
			// handle field display/hide
			$(document).on('click', '.toggle-form', function () {
				var target = $(this).attr("id");
				if ($(this).html() === opts.messages.hide) {
					$(this).removeClass('open').addClass('closed').html(opts.messages.show);
					$('#' + target + '-fld').animate({
						opacity: 'hide',
						height: 'hide'
					}, 'slow');
					return false;
				}
				if ($(this).html() === opts.messages.show) {
					$(this).removeClass('closed').addClass('open').html(opts.messages.hide);
					$('#' + target + '-fld').animate({
						opacity: 'show',
						height: 'show'
					}, 'slow');
					return false;
				}
				return false;
			});
			// handle delete confirmation
			$(document).on('click', '.delete-confirm', function () {
				var delete_id = $(this).attr("id").replace(/del_/, '');
				if (confirm($(this).attr('title'))) {
					$('#frm-' + delete_id + '-item').animate({
						opacity: 'hide',
						height: 'hide',
						marginBottom: '0px'
					}, 'slow', 'swing', function() {removeRow(this); $(this).remove();});
				}
				return false;
			});
			// Attach a callback to add new checkboxes
			$(document).on('click', '.add_ck', function () {
				$(this).parent().before(checkboxFieldHtml());
				return false;
			});

			$(document).on('click', '.frmb_preview_row', function () {
				$("#closeAllFields").removeAttr("disabled");
				switchMode(this, 'edit');
				return false;
			});

			$(document).on('click', '.frmb li .stopEditing', function () {
				switchMode($(this).parent().parent(), 'preview');
				var openRow = $("[id^='frm-'][id$='-item']");
				if (openRow.length == 0)
					$("#closeAllFields").attr("disabled", true);
				return false;
			});

			// Attach a callback to add new options
			$(document).on('click', '.add_opt', function () {
				$(this).parent().before(selectFieldHtml('', false));
				return false;
			});
			// Attach a callback to add new radio fields
			$(document).on('click', '.add_rd', function () {
				$(this).parent().before(radioFieldHtml(false, $(this).parents('.frm-holder').attr('id')));
				return false;
			});
			$('#closeAllFields').click(function() {
					var openRow = $("[id^='frm-'][id$='-item']");
					$(openRow).each(function() {
						switchMode(this,'preview');
					});
					$("#closeAllFields").attr("disabled", "true");

			})



			//load form data from options -- used in reason integration.
			if (opts.load_data) {
				JsonToMode(opts.load_data, "preview");
				json_data = opts.load_data;
			}

			return {
				getFormJSON: function() {
					var openRow = $("[id^='frm-'][id$='-item']");
					if (openRow.length > 0) {
						$(openRow).each(function() {
							switchMode(this,'preview');
						});
					}
					reSortJSON(ul_obj);
					return json_data;
				}
			};

			//});
		};
		
		function htmlspecialchars( str )
		{
			return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g,'&gt;').replace(/"/g, '&quot;');
		}
	
	})(jQuery);

/*
* Serializes the form list created by the formbuilder into JSON.
*
*/
	(function ($) {
		$.fn.formBuilderScrape = function (opts) {
			for (att = 0; att < opts.attributes.length; att++) {
				var current_item = {};
				current_item['values'] = {};
				current_item[(opts.attributes[att] === 'class' ? 'cssClass' : opts.attributes[att])] = $(this).attr(opts.attributes[att]).replace('Type', '');
				counter = 0;
				// append the form field values
				if (opts.attributes[att] === 'class') {
					requiredBox = $('#' + $(this).attr('id') + ' input.required').get(0);
					if (requiredBox != undefined) current_item['required'] = encodeURIComponent(requiredBox.checked);
					switch ($(this).attr(opts.attributes[att])) {
						case 'input_textType':
							current_item['values'] = $('#' + $(this).attr('id') + ' input.fld-title').val();
						current_item['defaultValue'] = $('#' + $(this).attr('id') + ' input.fld-default').val();
						break;
						case 'textareaType':
							current_item['values'] = $('#' + $(this).attr('id') + ' input.fld-title').val();
						current_item['defaultValue'] = $('#' + $(this).attr('id') + ' input.fld-default').val();
						break;
						case 'checkboxType':
							$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
							if ($(this).attr('name') === 'title') {
								current_item['title'] = $(this).val();
							}
							else {
								current_item['values'][counter] = {};
								current_item['values'][counter]['value'] = $(this).val();
								current_item['values'][counter]['baseline'] = $(this).prev().attr('checked');
								counter = counter + 1;
							}
						});
						break;
						case 'radioType':
							$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
							if ($(this).attr('name') === 'title') {
								current_item['title'] = $(this).val();
							}
							else {
								current_item['values'][counter] = {};
								current_item['values'][counter]['value'] = $(this).val();
								current_item['values'][counter]['baseline'] = $(this).prev().attr('checked');
								counter = counter + 1;
							}
						});
						break;
						case 'selectType':
							current_item['multiple'] = $('#' + $(this).attr('id') + ' input[name=multiple]').attr('checked');
						$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
							if ($(this).attr('name') === 'title') {
								current_item['title'] = $(this).val();
							}
							else {
								current_item['values'][counter] = {};
								current_item['values'][counter]['value'] = $(this).val();
								current_item['values'][counter]['baseline'] = $(this).prev().attr('checked');
								counter = counter + 1;
							}

						});
						break;
						case 'commentType':
							current_item['values'] = $('#' + $(this).attr('id') + ' input[type=text]').val();
						break;
						case 'hiddenType':
							counter = 0;
						$('#' + $(this).attr('id') + ' input[type=text]').each(function () {
							current_item['values'][counter] = $(this).val();
							counter++;
						});
						break;
						case 'submit_and_resetType':
							current_item['values']['submit'] = $('#' + $(this).attr('id') + ' input.fld-title').val();
						current_item['values']['reset'] = $('#' + $(this).attr('id') + ' input.fld-default').val();
						break;
					}
				}
				counter = counter + 1;
				return current_item;
			}
		};
		$.fn.serializeFormListJSON = function (options) {
			// Extend the configuration options with user-provided
			var defaults = {
				prepend: 'ul',
				is_child: false,
				attributes: ['class']
			};
			var opts = $.extend(defaults, options);
			//		This scares me.
			if (!opts.is_child) {

			};
			var new_obj = {};
			// Begin the core plugin
			//if ($(this).attr('class') == 'frmb')

			new_obj= $(this).formBuilderScrape(opts);
			//		serialStr = JSON.stringify(new_obj);

			return (new_obj);
		};
	})(jQuery);
