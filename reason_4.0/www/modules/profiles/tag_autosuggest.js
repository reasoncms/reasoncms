// Autocomplete for profiles module tag editing
// @author Mark Heiman
// @requires JQuery UI
// @requires tag-it.js

$(document).ready(function()
{
	var module_id = $.reasonAjax.get_module_identifier($('#profilesModule'));
	var edit_section = document.URL.match(/edit_section=([^&]+)/);
	var allowCommas = (edit_section[1] == 'travel_tags');	
	var deleteConfirmed = false;
	
	$("#tagsItem input").tagit({
		singleField: true,
		allowSpaces: true,
		allowCommas: allowCommas,
		singleFieldDelimiter: ';',
		beforeTagRemoved: function(event, ui) {
			if (ui.tag.hasClass("confirmDelete")) {
				return deleteConfirmed;
			}
		},
		tagSource: function(search, showChoices) {
			if (search.term.length < 2) return;
			var that = this;
			url = document.URL + '&module_identifier=' + module_id + '&module_api=standalone&term='+search.term+'&edit_section='+edit_section[1];
			//pass request to server
			$.getJSON(url + '&callback=?', function(data) {
				showChoices(that._subtractArray(data, that.assignedTags()));
			});
	      	},
	});
	
	$("input.ui-widget-content").focus();	
});