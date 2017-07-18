function initializeFormbuilder($, Formbuilder, thorContentItemId) {
	$targetTextarea = $('#' + thorContentItemId + ' textarea');
	var nameSpace = $targetTextarea.attr('name');
	//add the formbuilder div
	$targetTextarea.after('<div id="' + nameSpace + '_form-builder" />');
	
	$targetTextarea.hide();

	var json = JSON.parse($targetTextarea.val());

	var options = {
		bootstrapData: json,
		selector: '#' + nameSpace + '_form-builder'
	};

	var userInfo = getUserFlags();

	Formbuilder.config({
	  UNLISTED_FIELDS: ['submit_button',
	                    'number',
	                    'website',
	                    'email',
	                    'price',
	                    'address',
	                    'section_break'],
	  SHOW_SAVE_BUTTON: false,
	  WARN_IF_UNSAVED: false,
	  ALLOW_TYPE_CHANGE: true,
	  FORCE_BOTTOM_SUBMIT: true,
	  REQUIRED_DEFAULT: false,
	  IS_REASON_ADMIN: userInfo.user_has_advanced_options
	});

	var fb = new Formbuilder(options);

	// attaching formBuilderForm to window for debug purposes
	window.formbuilderInstance = fb;

	fb.on('save', function(payload) {
		$targetTextarea.val(payload);
	});

	//Is this futureproofish? Race condition here?
	$("#disco_form").submit(function () {
		fb.saveForm()
	});

	// $('.fb-op-buttons-wrapper').css("background-color", "blue");
	// $('.fb-left').css("background-color", "red");
}

// Returns an JS object with user info from the server
function getUserFlags() {
	var node = $("#user_options_json"), jsonString;
	if (node.text()) {
		jsonString = node.text() || "";
	}
	return jsonString ? JSON.parse(jsonString) : {};
}

// initializeFormbuilder($, Formbuilder, "thorcontentItem");

/*
(function($, Formbuilder) {
	$targetTextarea = $('#thorcontentItem textarea');
	var nameSpace = $targetTextarea.attr('name');
	//add the formbuilder div
	$targetTextarea.after('<div style="width:800px" id="' + nameSpace + '_form-builder" />');
	
	$targetTextarea.hide();

	var json = JSON.parse($targetTextarea.val());

	var options = {
		bootstrapData: json,
		selector: '#' + nameSpace + '_form-builder'
	};

	Formbuilder.config({
	  UNLISTED_FIELDS: ['submit_button',
	                    'date',
	                    'time',
	                    'number',
	                    'website',
	                    'email',
	                    'price',
	                    'address',
	                    'file',
	                    'section_break'],
	  SHOW_SAVE_BUTTON: false,
	  WARN_IF_UNSAVED: false,
	  FORCE_BOTTOM_SUBMIT: true,
	  REQUIRED_DEFAULT: false
	});

	var fb = new Formbuilder(options);

	// attaching formBuilderForm to window for debug purposes
	window.formbuilderInstance = fb;

	fb.on('save', function(payload) {
		$targetTextarea.val(payload);
	});

	//Is this futureproofish? Race condition here?
	$("#disco_form").submit(function () {
		fb.saveForm()
	});
})($, Formbuilder);
*/
