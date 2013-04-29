/**
 * ArrayMaker JQuery Plugin 
 * @version 1.0
 *
 * This plugin allows developers to create an array editor and attach it
 * to a form field. Defaults are at the bottom of the file.
 * Usage: $(selector).arrayMaker({"multiple_types":"false", "display_JSON":"sometimes"})
 * 
 * You may also set options by calling $.fn.arrayMaker.opts['multiple_types'] = "false".
 * 
 * Options:
 * "multiple_types" allows use of non-string values (i.e. bool, null, integer)
 * 		Values: "true" or "false"
 * 		Default: "true"
 * "visibility_toggle" determines whether or not the show/hide editor buttons
 * are visible.
 * 		Values: "true" or "false"
 * 		Default: "true"
 * "display_JSON" determines whether or not the raw JSON for an array is shown.
 * 		Value: "always", "sometimes", "never"
 * 		Default: "sometimes"
 * "initially_visible" shows the editor as soon as the script loads if set to true
 * 		Values: "true" or "false"
 * 		Default: "false"
 * "default_JSON" allows the array editor to be prepopulated with some JSON.  
 * 		Values: any valid JSON string
 * 		Default: ""
 * "class" determines the name of the class to be assigned to the array container.  
 * 		Values: any string
 * 		Default: "_arrayRows"
 * "allow_nesting" allows for multidimensional (nested) JSON objects.
 * 		Values: "true" or "false"
 * 		Default: "true"
 * 
 */
 
 (function($) {

	$.fn.arrayMaker = function(options) {
	
		// build main options before element iteration
		$.fn.arrayMaker.opts = $.extend({}, $.fn.arrayMaker.defaults, options);
		// These functions are in an if statement that overwrites their meaningful
		// counterparts in the case that allow_multiple_types is false.
		if ($.fn.arrayMaker.opts["allow_multiple_types"] == "false"){
			currentType = new Function;
			lastSelectedType = new Function;
			makeTypeSelect = new Function;
			removeTypeSelect = new Function;
			handleType = new Function;
			lastType = new Function;
			setSelectedType = new Function;
		}

		candidates = this.filter(":not([class*=_arrayTarget])").filter("input[type='text'], textarea");
		candidates.each(function()
		{
			addButtons(this);
		})

		if (candidates.length != 0) {bindButtons();}
		candidates.each(function()
			{
				if ($.fn.arrayMaker.opts["initially_visible"] == "true")
				{
					$(this).parent().find(".showEditorButton").click();
				}
			}
		)
		return candidates;
	};

	var indices;
	indices = {};
	unuseable = [];



/////////////////////////////////////////////////////////////////
/////////////////////// BUTTON FUNCTIONS ////////////////////////


	// This function appends the Show Editor Button to the parent 
	// of arrayTarget and adds a unique class to arrayTarget
	function addButtons(arrayTarget)
	{
		arrayID = makeArrayID();
		whatToAppend = '<div id="'+ arrayID + $.fn.arrayMaker.opts['class'] + '" class="' + $.fn.arrayMaker.opts['class'] + '">';
		whatToAppend += '<a id="' + arrayID + '_showEditorButton" class="showEditorButton" style="display: none;">show array editor</a></div> <a id="' + arrayID + '_collapseButton" class="collapseButton" style="display: none;">hide array editor</a>';
		$(arrayTarget).parent().append(whatToAppend);
		$(arrayTarget).addClass(arrayID + "_arrayTarget");
		if ($.fn.arrayMaker.opts['visibility_toggle'] == "true")
			$(".showEditorButton").show();
		if ($.fn.arrayMaker.opts['display_JSON'] == "never")
		{
			$(arrayTarget).hide();
		}
	}


	// Does what it sounds like. Binds all of the buttons that will
	// exist to their handlers: show editor, hide editor, nest a 
	// child row, add a row, remove a row, make JSON, and check for
	// type changes.
	function bindButtons(context) 
	{
		showEditorButton = $(".showEditorButton")
		showEditorButton.attr("href","javascript:void(0)");
		showEditorButton.click(
			function(objEvent)
			{
				var containerID;
				containerID = $(this).attr("id").replace("_showEditorButton", "");
				currentArrayContainer = getArrayContainer(containerID);
				arrayTarget = getArrayTarget(containerID);
				currentArrayContainer.find(".badJSON").remove();
				if (arrayTarget.val() == "") {
					if ($.fn.arrayMaker.opts['default_JSON'] == "") 
					{
						addArrayRow(null, null, currentArrayContainer);
						makeJSON(containerID);
					} else
					{
						arrayObject = JSON.parse($.fn.arrayMaker.opts['default_JSON']);
						recurseJSON(arrayObject, currentArrayContainer);
						handleType(containerID);
						makeJSON(containerID);
					}
				} else {
					try {
						arrayObject = JSON.parse(arrayTarget.val());
						if (typeof(arrayObject) != 'object')
						{
							throw("You entered a number.");
						}
					} catch(err) {
						var errormessage = $('<span class="badJSON">The JSON you entered is invalid. Double-check your syntax and try again.</span>');
						currentArrayContainer.append(errormessage);
						currentArrayContainer.find(".badJSON").delay(2000).fadeOut(1200);
						return false;
					}
						recurseJSON(arrayObject, currentArrayContainer);
						handleType(containerID);
						makeJSON(containerID);
				}
				if ($.fn.arrayMaker.opts['visibility_toggle'] == "true")
				{
					collapseButton = getButton("collapseEditor", containerID, null)
					collapseButton.show();
				}
				// Hides current showEditorButton
				$(this).hide();
				arrayTarget.attr("readonly", "readonly");
				if ($.fn.arrayMaker.opts['display_JSON'] == "never" || $.fn.arrayMaker.opts['display_JSON'] == "sometimes" )
				{
					arrayTarget.attr("style", "display: none;")
				}
				objEvent.preventDefault();
				return false;
			}
		);
		$(".collapseButton").attr("href","javascript:void(0)");
		$(".collapseButton").click(
			function(objEvent)
			{
				var containerID;
				var currentArrayContainer;
				containerID = $(this).attr("id").replace("_collapseButton","")
				currentArrayContainer = getArrayContainer(containerID);
				currentArrayContainer.children(".row").remove();
				if ($.fn.arrayMaker.opts['display_JSON'] == "always" || $.fn.arrayMaker.opts['display_JSON'] == "sometimes" )
				{	
					arrayTarget = getArrayTarget(containerID);
					arrayTarget.show();
				}
				showEditorButton = getButton("showEditor", containerID);
				showEditorButton.show();
				collapseButton = getButton("collapseEditor", containerID);
				collapseButton.hide();
				arrayTarget.attr("readonly", "");
				objEvent.preventDefault();
			}
		);
		$(document).on('click', '.nest', function(objEvent)
		{
			var containerID;
			currentRow = $(this).parent();
			nestArray(currentRow);
			containerID = $(this).parent().attr("id").replace(new RegExp($.fn.arrayMaker.opts['class'] + ".*$", ""), "");
			makeJSON(containerID);
			objEvent.preventDefault();
		});
		$(document).on('click', '.addRow', function(objEvent)
		{
			var containerID;
			addArrayRow(null, null, $(this).parent().parent());
			containerID = $(this).parent().attr("id").replace(new RegExp($.fn.arrayMaker.opts['class'] + ".*$", ""), "");
			makeJSON(containerID);
			objEvent.preventDefault();
		});
		$(document).on('click', '.removeRow', function(objEvent)
		{
			var containerID;
			removeArrayRow($(this).parent());
			containerID = $(this).parent().attr("id").replace(new RegExp($.fn.arrayMaker.opts['class'] + ".*$", ""), "");
			makeJSON(containerID);
			objEvent.preventDefault();
			return false;
		});
		$(document).on('click', '.removeRowDisabled', function(objEvent)
		{
			objEvent.preventDefault();
			return false;
		});
		$(document).on('blur', 'input.key, input.value, select', function()
		{
			var containerID;
			containerID = $(this).parent().attr("id").replace(new RegExp($.fn.arrayMaker.opts['class'] + ".*$", ""), "");
			makeJSON(containerID);
		});
		$(document).on('keyup', 'input.value', function()
		{
			var containerID;
			containerID = $(this).parent().attr("id").split($.fn.arrayMaker.opts['class']);
			containerID =  containerID[0];
			handleType(containerID);
		});
	}

	// checkButtons() makes certain that only the appropriate buttons
	// are present on a given row. For instance, parent rows shouldn't
	// have the option to nest, and the remove button shouldn't work 
	// for the last row on a level. 
	function checkButtons(rowContainer) {
		// We need to figure out which buttons to add.
		if (rowContainer.children(".row").length == 1) {
			rowContainer.children(".row").children(".removeRow").attr("class", $(".removeRow").attr("class") + "Disabled");
		} else {
			rowContainer.children(".row").children(".removeRowDisabled").attr("class", "removeRow");
		}
	}	

	
/////////////////////////////////////////////////////////////////
///////////////////////// ROW FUNCTIONS /////////////////////////
// Row manipulation functions: Add, remove, nest.

	// addArrayRow adds a row at appendLocation with the supplied
	// key and value. Returns a reference to the created row.
	function addArrayRow(key, value, appendLocation)
	{
		// Clone the jQuery arrayRow exemplar, determine what its rowID will be, and set the ID of the row to that. 
		var newArray = $.fn.arrayMaker.arrayRowHTML.clone(); 
		if ($.fn.arrayMaker.opts['allow_nesting'] != true)
			$('.nest', newArray).remove();
		var arrayRowNum = appendLocation.children("div .row").length;
		var rowID = appendLocation.attr('id') + "-row" + arrayRowNum;
		newArray.attr("id", rowID);
		// Set the key and value from the function's parameters.
		newArray.children(".key").attr("value", key);
		newArray.children(".value").attr("value", value);
		appendLocation.append(newArray);
		newObjectRef = $("#" + rowID);
		checkButtons($(appendLocation));
		indices[appendLocation.attr("id")] = 0;
		unuseable[appendLocation.attr("id")] = [];
		return(newObjectRef);
	}

	// nestArray creates a new row with the supplied key and 
	// value as a child of currentRow. 
	function nestArray(key, value, currentRow)
	{
		if (arguments.length == 1) {
			currentRow = key;
			// We need to hide the [nest] button and the typeselect (if present) now.
			currentRow.children(".nest").hide();
			removeTypeSelect(currentRow.attr("id"));
			// We need to remove the value field for the current row. 
			currentRow.children(".value").remove();
			// We want to insert the new row as a child of the current row.
			newObjectRef = addArrayRow(null, null, currentRow);
			return(newObjectRef);
		} else if (arguments.length == 3) {
			// We need to hide the [nest] button and the typeselect (if present) now.
			currentRow.children(".nest").hide();
			removeTypeSelect(currentRow.attr("id"));
			// We need to remove the value field for the current row. 
			currentRow.children(".value").remove();
			// We want to insert the new row as a child of the current row.
		} else {
			return false;
		}
	}

	// removeArrayRow deletes a row!
	function removeArrayRow(row)
	{
		rowParent = row.parent();
		row.remove();
		checkButtons(rowParent);
	}


/////////////////////////////////////////////////////////////////
/////////////////////// JSON MANIPULATION ///////////////////////

	// makeJSON converts the DOM of an array container into JSON.
	// Certain meaningless strings, such as {"0":""}, are deleted.
	function makeJSON(containerID) 
	{
		var containerID;
		arrayContainer = getArrayContainer(containerID);
		arrayContainer.find(".alert").removeClass("alert");
		var myJSON = {};
		arrayContainer.find(".alertNotice").remove();
		arrayContainer.children(".row").each(
				function() {
					key = handleKey(this);
					value = handleNest(this);
					myJSON[key] = value;
				}
			);

		paramsBox = getArrayTarget(containerID);
		myJSONString = JSON.stringify(myJSON);
		myJSONString = myJSONString.replace(':{"0":""}', ':""');
		myJSONString = myJSONString.replace('{"0":""}', '');
		myJSONString = myJSONString.replace('{"0":}','');
		myJSONString = myJSONString.replace('"0":,','');
		myJSONString = myJSONString.replace('{}','');
		paramsBox.attr("value", myJSONString);
	
		// Reset the values of each member of the indices object without destroying the keys.
		for (var i in indices) {
			indices[i] = 0;
		}
		for (var i in unuseable) {
			unuseable[i] = [];
		}
	}
	
	// recurseJSON takes a javascript object (chances are you've converted
	// this from a JSON string) and turns it into an arrayMaker DOM framework.
	function recurseJSON(arrayObject, currentRow)
	{
		var className = $.fn.arrayMaker.opts['class'] + "-";
		for (var key in arrayObject) {
			if (typeof arrayObject[key] == 'string') {
				// key is already set to key.
				value = arrayObject[key];
				newRow = addArrayRow(key, value, currentRow);
				handleType(newRow.attr("id").split(className)[0]);
				setSelectedType(newRow.attr("id").split(className)[0], newRow.attr("id").split(className)[1], "str");
			} else if (typeof arrayObject[key] == 'number') {
				value = arrayObject[key];
				newRow = addArrayRow(key, value, currentRow);
				handleType(newRow.attr("id").split(className)[0]);
				setSelectedType(newRow.attr("id").split(className)[0], newRow.attr("id").split(className)[1], "int");
			} else if (typeof arrayObject[key] == 'boolean') {
				value = arrayObject[key];
				newRow = addArrayRow(key, value, currentRow);
				handleType(newRow.attr("id").split(className)[0]);
				setSelectedType(newRow.attr("id").split(className)[0], newRow.attr("id").split(className)[1], "bool");
			} else if (arrayObject[key] == null) {
				value = "null";
				newRow = addArrayRow(key, value, currentRow);
				handleType(newRow.attr("id").split(className)[0]);
				setSelectedType(newRow.attr("id").split(className)[0], newRow.attr("id").split(className)[1], "null");
			} else {
				// key is already set to key.
				newRow = addArrayRow(key, null, currentRow);
				nestArray(null, null, newRow);
				// we have to pass the remaining children of the current branch of the 
				// tree to the recurse function.
				remaining = arrayObject[key];
				recurseJSON(remaining, newRow);
			}
		}
	}

	function handleKey(currentRow) {
		fieldValue = $(currentRow).children(".key").val();
		parentName = $(currentRow).parent().attr("id");
		// if the field has a value in it, add that value to the list of unavailable keys and return it. 
		if (fieldValue != "") {
			if ($.inArray(fieldValue, unuseable[parentName]) == -1) {
				unuseable[parentName].push(fieldValue);
			} else {
				// Handle the conflict. At the moment I'm just adding a class to it.
				$(currentRow).addClass("alert");
				$(currentRow).children(".removeRow").after("<div class=\"alertNotice\"> The key you've specified has already been assigned.</div>");
			}
			return fieldValue;
		// otherwise, make a new key and 
		} else {
			return makeKey(currentRow);
		}
	}
	function makeKey(currentRow) {
		parentName = $(currentRow).parent().attr("id");
		possibleKey = indices[parentName];
		if ($.inArray(possibleKey.toString(), unuseable[parentName]) == -1) {
			unuseable[parentName].push(possibleKey.toString());
			return possibleKey;
		} else {
			indices[parentName]++;
			return makeKey(currentRow);
		}
	}
	function handleNest(currentRow) {
		// If you have a child row, you need to be handled by handleNest();.
		if ($(currentRow).children(".row").length != 0) 
		{
			var newObject;
			newObject = {};
			$(currentRow).children(".row").each(
				function () {
					var key;
					var value;
					key = handleKey(this);
					value = handleNest(this);
					newObject[key] = value;
				}
			);
			return newObject;
		} else {
			typeSelect = $(currentRow).children(".typeSelect");
			fieldContent = $(currentRow).children(".value").val();
			if (typeSelect.length != 0)
			{
				if (typeSelect.val() == "int")
				{
					value = parseInt(fieldContent,10);
				} else if (typeSelect.val() == "bool")
				{
					if (fieldContent.toLowerCase() == "true")
					{
						value = true;
					} else if (fieldContent.toLowerCase() == "false") {
						value = false;
					}
				} else if (typeSelect.val() == "null")
				{
					value = null;
				} else if (typeSelect.val() == "str")
				{
					value = $(currentRow).children(".value").val();
				}
			} else {
				value = $(currentRow).children(".value").val();
			}
			return value; 
		}
	}
	


/////////////////////////////////////////////////////////////////
//////////////////////// TYPE FUNCTIONS /////////////////////////
// Functions that handle non-string array values: was there an 
// ambiguous type formerly? What was it set to? and making of 
// form selects for handling types. 

	function currentType(containerid, rowid)
	{
		var rowid;
		var containerid;
		
		currentValue = getArrayRow(containerid, rowid).find(".value").val();
		if (!isNaN(currentValue) && currentValue != "") {
			return "int";
		}
		else if (currentValue.toLowerCase() == "false" || currentValue.toLowerCase() == "true")
		{
			return "bool";
		}
		else if (currentValue.toLowerCase() == "null")
		{
			return "null";
		} else
		{
			return "none";
		}
	}
	function lastSelectedType(containerid, rowid)
	{
		var currentRow;
		currentRow = getArrayRow(containerid, rowid);
		return currentRow.children("select").val();
	}
	function makeTypeSelect(containerid, rowid, ambiguousType, currentType)
	{
		var rowid;
		var containerid;
		var currentArrayContainer;
		var currentRow;		
		if (getButton("typeSelect", containerid, rowid).length != 0)
		{
			removeTypeSelect(containerid, rowid);
		}
		switch(ambiguousType)
		{
			case "bool":
				pickerHTML = '<select class="typeSelect" name="'+rowid+'_typeSelect">';
				pickerHTML += '	<option value="bool">Boolean</option>';
				pickerHTML += '	<option value="str">String</option>';
				pickerHTML += '</select>';
				break;

			case "int":
				pickerHTML = '<select class="typeSelect" name="'+rowid+'_typeSelect">';
				pickerHTML += '	<option value="int">Integer</option>';
				pickerHTML += '	<option value="str">String</option>';
				pickerHTML += '</select>';
				break;
			
			case "null":
				pickerHTML = '<select class="typeSelect" name="'+rowid+'_typeSelect">';
				pickerHTML += '	<option value="null">NULL</option>';
				pickerHTML += '	<option value="str">String</option>';
				pickerHTML += '</select>';
				break;
			case "none":
				pickerHTML = "";
				break;
		}
		currentRow = getArrayRow(containerid, rowid) 
		currentRow.append(pickerHTML);
	}
	function removeTypeSelect(containerid, rowid)
	{
		typeButton = getButton("typeSelect", containerid, rowid);
		typeButton.remove();
	}
	
	
	function handleType(containerid)
	{

		var containerid;
		var currentArrayContainer;
		currentArrayContainer = getArrayContainer(containerid);
		currentArrayContainer.find(".value").each(function()
			{
				var rowid;
				rowid = $(this).parent().attr("id").split($.fn.arrayMaker.opts["class"]+"-")[1];
				lastAmbiguousType = lastType(containerid, rowid);
				currentAmbiguousType = currentType(containerid, rowid);
				if (lastAmbiguousType != currentAmbiguousType)
				{
					if (lastAmbiguousType != "none")
					{
						lastTypeSelected = lastSelectedType(containerid, rowid);
					}
					makeTypeSelect(containerid, rowid, currentAmbiguousType);
					if (lastAmbiguousType != "none")
					{
						setSelectedType(containerid, rowid, lastTypeSelected);
					}

				}
			}
		)
	}
	function lastType(containerid, rowid) {
		var containerid;
		var rowid;
		var lasttType;
		if (getButton("typeSelect", containerid, rowid).length != 0)
		{
			getButton("typeSelect", containerid, rowid).children("option").each(function() 
				{
					if (this.value != "str") 
					{
						lasttType = String(this.value);
					}
				}
			)
			return lasttType;
		} else 
		{
			return "none";
		}
	}


	function setSelectedType(containerid, rowid, selectedType)
	{
		typeSelect = getButton("typeSelect", containerid, rowid);
			typeSelect.val(selectedType, true);
	}
	



/////////////////////////////////////////////////////////////////
/////////////////////// UTILITY FUNCTIONS ///////////////////////
// Getters for commonly-referenced jQuery objects; arrayID maker.
// !TODO: Implement caching scheme. 

	function makeArrayID()
	{
		$.fn.arrayMaker.arrayIDCounter++;
		return $.fn.arrayMaker.arrayIDCounter;
	}
	function getLocationInfo(jqueryObject) {}
	function getArrayContainer(containerid) 
	{
		var containerid;
		return $("#" + containerid + $.fn.arrayMaker.opts['class']);
	}

	// getArrayRow returns a jquery object of the row with 
	// rowid in the container with containerid.
	// rowid should be of the format "row0-row2-row3" etc.
	function getArrayRow(containerid, rowid)
	{
		var containerid;
		return $("#" + containerid + $.fn.arrayMaker.opts['class'] +  "-" + rowid);
	}
	function getArrayTarget(containerid) 
	{
		var containerid;
		return $("." + containerid + "_arrayTarget");
	}
	function getButton(type, containerid, rowid)
	{
		var type;
		var containerid;
		var rowid;
		switch(type)
		{
			case "collapseEditor":
				return $("#" + containerid + "_collapseButton");
			case "showEditor":
				return $("#" + containerid + "_showEditorButton");
			case "typeSelect":
				return $("#"+containerid+$.fn.arrayMaker.opts["class"]).find("[name='"+rowid+"_typeSelect']");
		}
	}

/////////////////////////////////////////////////////////////////
//////////////////////////// SETTINGS ///////////////////////////
// Settings and internal variables that must remain consistent. 

// !TODO: Implement caching scheme. 
// !TODO: Isn't there something that I need to do with the default_json?

	$.fn.arrayMaker.arrayIDCounter = 0;
	$.fn.arrayMaker.arrayRowHTML = $('<div class="row"><input type="text" class="key" /> : <input type="text" class="value" /><a href="#" class="addRow">[add row]</a> <a href="#" class="nest">[nest]</a> <a href="#" class="removeRow">[remove]</a></div>');
	$.fn.arrayMaker.defaults = {
		"multiple_types"		: "true",
		"visibility_toggle"		: "true",
		"display_JSON"			: "sometimes",
		"initially_visible"		: "false",
		"default_JSON"			: "",
		"class"					: "_arrayRows",
		"allow_nesting"			: "true"
	};
})(jQuery);