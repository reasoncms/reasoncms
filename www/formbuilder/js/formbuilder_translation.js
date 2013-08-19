//Requires jQuery 1.5 or higher.
(function ($) {
	$.fn.attachFormBuilder = function (options) {
		nameSpace = $(this).attr('name');
		targetTextarea = this;
		$(targetTextarea).hide();

		if ($(targetTextarea).val() === '') $(targetTextarea).val('<?xml version="1.0" ?><form submit="Submit" reset="Clear"></form>');
		json = $().XMLToJSON($(targetTextarea).val());
		optns = {
			load_data: json
		};
        optns = $.extend(optns, options);
        $(this).after('<div id="' + nameSpace + '_form-builder" />');

        formBuilderForm = $('#' + nameSpace + '_form-builder').formbuilder(optns);

        // attaching formBuilderForm to window for debug purposes
        window.formBuilderForm = formBuilderForm;
        
        $('#' + nameSpace + '_form-builder ul').sortable({
			opacity: 0.6,
			cursor: 'move',
			//cancel: '[name="submit_and_reset"]'
            items: ">[name!='submit_and_reset']"

		});
		$("#frmb-0-save-button").remove();
		//Is this futureproofish? Race condition here?
		$("#disco_form").submit(function () {
			result = $().makeThorXML(formBuilderForm.getFormJSON());
			$(targetTextarea).val(result);
		});
	};


	// Takes JSON from jQuery.formBuilder and turns it into Thor XML.
	$.fn.makeThorXML = function (input) {
//!		You need to remove all of the references to makeThorID() and change them to a var
//		that equals (ul_obj[item].id != '') ? ul_obj[item].id : $().makeThorID();

		ul_obj = input;
		xmlHead = '<?xml version="1.0" ?>';
		var xmlDoc = "";
		submit = ul_obj['options']['submit'];
		xmlHead += '<form submit="' + submit + '" >';
		delete ul_obj['options'];
		var i = 1;
		for (var item in ul_obj) { 
			item = i; 
			itemVals = htmlspecialchars(decodeURIComponent(ul_obj[item].values));
			itemRequired = (decodeURIComponent(ul_obj[item].required) == 'true') ? 'required' : '';
			itemTitle = htmlspecialchars(decodeURIComponent(ul_obj[item].title));
			itemDefault = htmlspecialchars(decodeURIComponent((ul_obj[item].defaultValue) ? ul_obj[item].defaultValue : ''));
			if (ul_obj[item].cssClass == "input_text") {
				console.log(itemVals);
				console.log(itemDefault);
				xmlDoc += '<input type="text" id="' + $().makeThorID();
				if (itemRequired) xmlDoc += '" required="' + itemRequired;
				if (itemDefault) xmlDoc += '" value="' + itemDefault;
				xmlDoc += '" label="' + itemVals + '"/>';
			}
			else if (ul_obj[item].cssClass == "textarea") {
				xmlDoc += '<textarea id="' + $().makeThorID();
				if (itemRequired) xmlDoc += '" required="' + itemRequired;
				if (itemDefault) xmlDoc += '" value="' + itemDefault;
				xmlDoc += '" label="' + itemVals + '"/>';
			}
			else if (ul_obj[item].cssClass == "checkbox") {
				addString = '<checkboxgroup id="' + $().makeThorID();
				if (itemRequired !== '')
                    addString += '" required="' + itemRequired;
				addString += '" label="' + itemTitle + '">';
				for (var checkitem in ul_obj[item]['values']) {
					subVals = htmlspecialchars(decodeURIComponent(ul_obj[item]['values'][checkitem].value));
					subRequired = (decodeURIComponent(ul_obj[item]['values'][checkitem].required) == 'checked') ? 'required' : '';
					subSelected = (decodeURIComponent(ul_obj[item]['values'][checkitem].baseline) == "checked") ? 'selected' : null;
					if (typeof (ul_obj[item]['values'][checkitem]) == 'object') {
						addString += '<checkbox id="' + $().makeThorID() + '" value="' + subVals + '" label="' + subVals + '" ';
						if (subSelected) addString += 'selected="selected" ';
						addString += ' />';
					}
				}
				addString += '</checkboxgroup>';
				xmlDoc += addString;
			}
			else if (ul_obj[item].cssClass == "radio") {
				addString = '<radiogroup id="' + $().makeThorID();
				if (itemRequired !== '') addString += '" required="' + itemRequired;
				addString += '" label="' + itemTitle + '">';
				for (var radioitem in ul_obj[item]['values']) {
					subVals = htmlspecialchars(decodeURIComponent(ul_obj[item]['values'][radioitem].value));
					subRequired = (decodeURIComponent(ul_obj[item]['values'][radioitem].required) == 'checked') ? 'required' : '';
					subSelected = (decodeURIComponent(ul_obj[item]['values'][radioitem].baseline) == "checked") ? 'selected' : null;

					if (typeof (ul_obj[item]['values'][radioitem]) == 'object') {
						addString += '<radio id="' + $().makeThorID() + '" value="' + subVals + '" label="' + subVals + '" ';
						if (subSelected) addString += 'selected="selected" ';
						addString += ' />';
					}
				}
				addString += '</radiogroup>';
				xmlDoc += addString;
			}
			else if (ul_obj[item].cssClass == "select") {
				addString = '<optiongroup id="' + $().makeThorID();
				if (itemRequired != '') addString += '" required="' + itemRequired;
				addString += '" label="' + itemTitle + '">';
				for (var optionitem in ul_obj[item]['values']) {
					subVals = htmlspecialchars(decodeURIComponent(ul_obj[item]['values'][optionitem].value));
					subRequired = (decodeURIComponent(ul_obj[item]['values'][optionitem].required) == 'checked') ? 'required' : '';
					subSelected = (decodeURIComponent(ul_obj[item]['values'][optionitem].baseline) == "checked") ? 'selected' : null;

					if (typeof (ul_obj[item]['values'][optionitem]) == 'object') {
						addString += '<option id="' + $().makeThorID() + '" value="' + subVals + '" label="' + subVals + '" ';
						if (subSelected) addString += 'selected="selected" ';
						addString += ' />';
					}
				}
				addString += '</optiongroup>';
				xmlDoc += addString;
			}
			else if (ul_obj[item].cssClass == "comment") {
				xmlDoc += '<comment id="' + $().makeThorID() + '" >' + itemVals + '</comment>';
			}
			else if (ul_obj[item].cssClass == "hidden") {
				label = htmlspecialchars(ul_obj[item].values[0]);
				value = htmlspecialchars(ul_obj[item].values[1]);
				xmlDoc += '<hidden label="' + label + '" id="' + $().makeThorID() + '" value="' + value + '" />';
			}
		i++;
		}

		xmlDoc = xmlHead + xmlDoc + '</form>';
		return xmlDoc;

	};
	$.fn.makeThorID = function () {

		if ($.fn.makeThorID.idArray == undefined) $.fn.makeThorID.idArray = new Array();
		var id = "";
		var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

		for (var len = 0; len < 10; len++)
		id += possible.charAt(Math.floor(Math.random() * possible.length));

		id += "_id";
		if ($.fn.makeThorID.idArray[id] == '1') return $().makeThorID();
		else {
			$.fn.makeThorID.idArray[id] = '1';
			return id;
		}
	};

	// Changes Thor XML to JSON
	$.fn.XMLToJSON = function (input) {

		var values = false;
		var options = false;
		var required = false;
		var counter = 1;
		var stuff = {};
		obj = {};
		XMLDocument = $.parseXML(input);
		submit = $("form", XMLDocument).attr('submit');
		reset = $("form", XMLDocument).attr('reset');
		obj['options'] = {
			'submit': submit,
			'reset': reset
		};
		$("form", XMLDocument).children().each(function () {
			objectID = $(this).attr('id');
			required = ($(this).attr('required') == 'required') ? 'true' : 'false';
			if ($(this).is("input")) {
				cssClass = 'input_text';
				defaultValue = $(this).attr('value');
				label = $(this).attr('label');
				
				// Not at all sure how this is handled.
				obj[counter] = {
					'values': label,
					'required': required,
					'defaultValue': defaultValue
				};
			}
			else if ($(this).is('comment')) {
				cssClass = 'comment';
				label = $(this).text();
				obj[counter] = {
					'values': label
				};
			}
			else if ($(this).is('hidden')) {
				cssClass = 'hidden';
				label = {
					0: $(this).attr('label'),
					1: $(this).attr('value')
				};
				obj[counter] = {
					'values': label
				};
			}
			else if ($(this).is('textarea')) {
				cssClass = 'textarea';
				defaultValue = $(this).attr('value');
				label = $(this).attr('label');
				obj[counter] = {
					'values': label,
					'required': required,
					'defaultValue': defaultValue
				};
			}
			else if ($(this).is("radiogroup")) {
				cssClass = 'radio';
				// Should be passed as options[0]
				label = $(this).attr("label");
				values = {};
				$(this).children().each(function (index) {
					values[index] = {
						value: $(this).attr("label"),
						baseline: ($(this).attr('selected') == 'selected') ? 'checked' : 'false'
					};
				});
				obj[counter] = {
					'required': required,
					'title': label,
					'values': values
				};
			}
			else if ($(this).is('checkboxgroup')) {
				cssClass = 'checkbox';
				// Should be passed as options[0]
				label = $(this).attr("label");
				values = {};
				$(this).children().each(function (index) {
					values[index] = {
						value: $(this).attr("label"),
						baseline: ($(this).attr('selected') == 'selected') ? 'checked' : 'false'
					};
				});
				obj[counter] = {
					'required': required,
					'values': values,
					'title': label
				};
			}
			else if ($(this).is('optiongroup')) {
				cssClass = 'select';
				// Should be passed as options[0]
				label = $(this).attr("label");
				values = {};
				$(this).children().each(function (index) {
					values[index] = {
						value: $(this).attr("label"),
						baseline: ($(this).attr('selected') == 'selected') ? 'checked' : 'false'
					};
				});
				obj[counter] = {
					'required': required,
					'values': values,
					'title': label
				};
			}

			obj[counter] = $.extend(obj[counter], {
				'cssClass': cssClass,
				'id':objectID
			});
			counter++;
		});
		return obj;
	};

	// a helpful debug function:
	$.fn.checkTranslate = function (input) {
		json = $().XMLToJSON(input);
		json = JSON.stringify(json);
		return json;
		//	$("#thor_content_form-builder").after('<div id="monkey" />');
		//	$("#monkey").formbuilder({load_data: json});
	};
	
	function htmlspecialchars( str )
	{
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g,'&gt;').replace(/"/g, '&quot;');
	}

})(jQuery);
