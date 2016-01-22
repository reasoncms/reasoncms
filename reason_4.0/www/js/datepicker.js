// datepicker event type maps to an object. Each key in this object is a 
// datepicker element id, that maps to an array. Each element in that array
// is a function that should be called when the appropriate callback fires
// for that element.
//
// with our recent switch from Datepicker 4.5 to 6.3.6, the way you register for event
// callbacks has changed. Unfortunately it now has to happen at datepicker construction time,
// and that doesn't always work for us. We'll instead register generic dummy callbacks here at
// construction time, and create some methods to allow interested clients to add their own
// callbacks after construction.
// See global_stock/themes/newsportal/js/submissionForm.js for an example client that
// calls "addDatepickerCallback".
var datepickerEvents = [ "datereturned", "dateset", "redraw", "domcreate", "dombuttoncreate" ];
var datepickerCallbacks = {
	"datereturned": {},
	"dateset": {},
	"redraw": {},
	"domcreate": {},
	"dombuttoncreate": {},
};

var addDatepickerCallback = function(dpId, dpEvent, fxn) {
	var eventConfig = datepickerCallbacks[dpEvent];
	if (eventConfig == null) {
		console.log("error - attempting to add a datepicker callback for unrecognized event '" + dpEvent + "'.");
	} else {
		var idConfig = eventConfig[dpId];
		if (idConfig == null) {
			eventConfig[dpId] = [ fxn ];
		} else {
			(eventConfig[dpId]).push(fxn);
		}
	}
};

// returns a (possibly empty) array containing all registered callbacks for this field id / event type
var getCallbacks = function(dpId, dpEvent) {
	var eventConfig = datepickerCallbacks[dpEvent];
	if (eventConfig == null) {
		console.log("error - attempting to get a datepicker callback for unrecognized event '" + dpEvent + "'.");
		return [];
	} else {
		var idConfig = eventConfig[dpId];
		if (idConfig == null) {
			return [];
		} else {
			return idConfig;
		}
	}
};

var createDatepickerCallbackFunctionWrapper = function(dpId, dpEvent) {
	// console.log("creating callback wrapper for [" + dpId + "]/[" + dpEvent + "]");
	var passthruFxn = function(dpId, dpObj) {
		// console.log("in dummy callback for [" + dpId + "]/[" + dpEvent + "]"); console.log(dpObj);
		var callbacks = getCallbacks(dpId, dpEvent);
		for (var i = 0 ; i < callbacks.length ; i++) {
			var registeredCallbackFxn = callbacks[i];
			registeredCallbackFxn(dpObj);
		}
	};

	return function(dpObj) {
		passthruFxn(dpId, dpObj);
	};
};

$(document).ready( function() {

    $.each( $(".datepicker"), function() {
        yearElementID   = $(this).attr('id');
        dayElementID    = yearElementID + "-dd";
        monthElementID  = yearElementID + "-mm";

        var dateObj = {};
        dateObj[yearElementID]   = "%Y";
        dateObj[dayElementID]    = "%d";
        dateObj[monthElementID]  = "%m";

        opts = {
                formElements:       dateObj,
                statusFormat:       "%l, %d%S %F %Y",
                fillGrid:           true,
                constrainSelection: false
        };

		var callbackFxns = {};
		for (var i = 0 ; i < datepickerEvents.length ; i++) {
			var eventType = datepickerEvents[i];
			callbackFxns[eventType] = [ createDatepickerCallbackFunctionWrapper(yearElementID, eventType) ];
		}
		opts.callbackFunctions = callbackFxns;

        datePickerController.createDatePicker(opts);
    });
});
