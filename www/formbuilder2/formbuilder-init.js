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
		REQUIRED_DEFAULT: false,
		mappings: {
			EVENT_TICKETS_EVENT_ID: 'event_tickets_event_id',
			EVENT_TICKETS_NUM_TOTAL_AVAILABLE: 'event_tickets_num_total_available',
			EVENT_TICKETS_MAX_PER_PERSON: 'event_tickets_max_per_person',
			EVENT_TICKETS_EVENT_CLOSE_DATETIME: 'event_tickets_event_close_datetime',
		}
	});
	
	

	// Scrape Event info for events related to this form to use for 
	// the ticket slot selector
	var eventInfo = $("#event_rel_info");
	var eventArray = [];
	if (eventInfo.length > 0) {
		eventArray = JSON.parse(eventInfo[0].innerHTML);

		var htmlSelect = "";
		Formbuilder.eventInfo = {};
		$.each(eventArray, function (event) {
			// Save event obj for the method to display pretty event titles
			// in the Formbuilder view
			Formbuilder.eventInfo[event.id] = event;

			var displayStr = event.name + " [" + event.datetime_pretty + "] [ID: " + event.id + "]";
			htmlSelect += '<option value="' + event.id + '">' + displayStr + "</option>\n";
		});
		if (htmlSelect == "") {
			var displayStr = "No Event Relationships present. Select Events in left menu first.";
			htmlSelect += '<option value="">' + displayStr + "</option>\n";
		} else {
			// prefix with empty options
			htmlSelect = '<option value=""></option>' + htmlSelect;
		}
	}
	
	Formbuilder.event_id_to_name = function(eventId) {
		console.log(eventId);
		var title = "";
		if(this.eventInfo[eventId]) {
			title = this.eventInfo[eventId].name + ", " + this.eventInfo[eventId].datetime_pretty;
		}
				console.log(title);

		return title;
	}
	
	Formbuilder.registerField('event_tickets', {
		order: 0,
		type: "non_input",
		view: "Tickets for <%= Formbuilder.event_id_to_name(rf.get(Formbuilder.options.mappings.EVENT_TICKETS_EVENT_ID)) %><br><br>\n\
Event ID: <%= rf.get(Formbuilder.options.mappings.EVENT_TICKETS_EVENT_ID) %><br>\n\
Total tickets for event: <%= rf.get(Formbuilder.options.mappings.EVENT_TICKETS_NUM_TOTAL_AVAILABLE) || 'unlimited' %><br>\n\
Max tickets per submission: <%= rf.get(Formbuilder.options.mappings.EVENT_TICKETS_MAX_PER_PERSON) || '1' %><br>\n\
Ticket sales close at: <%= rf.get(Formbuilder.options.mappings.EVENT_TICKETS_EVENT_CLOSE_DATETIME) || '1hr before event' %><br>",
		edit: "\
  <div class='fb-label-description'>\n\
  <div class='fb-edit-section-header'>Tickets</div>\n\
  <div>Select Future Event:<br>\n\
  <select style='width:100%' data-rv-input='model.<%= Formbuilder.options.mappings.EVENT_TICKETS_EVENT_ID %>' >" +
				htmlSelect +
				"</select><em>non-recurring events only</em></div>\n\
  <div class='fb-edit-section-header'>Options</div><div class='fb-clear'></div>\n\
  <label>Total Tickets Available:<br><input type='number' min='0' name='' data-rv-input='model.<%= Formbuilder.options.mappings.EVENT_TICKETS_NUM_TOTAL_AVAILABLE %>' /> <em>Default: unlimited</em><br><br>\n\
  <label>Max Tickets Per Submission:<br><input type='number' min='0' name='' data-rv-input='model.<%= Formbuilder.options.mappings.EVENT_TICKETS_MAX_PER_PERSON %>' /> <em>Default: 1 per person</em><br><br>\n\
  <label>Purchase cutoff prior to event: <br><input type='text' name='' placeholder='YYYY-MM-DD HH:MM:SS' data-rv-input='model.<%= Formbuilder.options.mappings.EVENT_TICKETS_EVENT_CLOSE_DATETIME %>' /> (YYYY-MM-DD HH:MM:SS, in 24hr time)<br> <em>Default: 1hr before event</em><br>\n\
  </div>",
		addButton: "<span class='symbol'><span class='fa fa-minus'></span></span> Event Tickets",
		prettyName: "Event Tickets",	

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
