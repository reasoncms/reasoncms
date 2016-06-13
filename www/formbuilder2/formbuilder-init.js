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

	Formbuilder.config({
	  UNLISTED_FIELDS: ['submit_button',
	                    'date',
	                    'time',
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
	  REQUIRED_DEFAULT: false
	});

Formbuilder.registerField('hello', {
    order: 0,
    type: 'non_input',
    view: "<label class='section-name'><%= rf.get(Formbuilder.options.mappings.LABEL) %></label>\n<p><%= rf.get(Formbuilder.options.mappings.DESCRIPTION) %></p>",
    edit: "<div class='fb-label-description'>\n  <div class='fb-edit-section-header'>Label</div>\n  <input type='text' data-rv-input='model.<%= Formbuilder.options.mappings.LABEL %>' />\n  <textarea data-rv-input='model.<%= Formbuilder.options.mappings.DESCRIPTION %>'\n    placeholder='Add a longer description to this field'></textarea>\n</div>",
    addButton: "<span class='symbol'><span class='fa fa-minus'></span></span> Section Break"
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
