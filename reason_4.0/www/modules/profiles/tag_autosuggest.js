// Autocomplete for profiles module tag editing
// @author Mark Heiman
// @requires JQuery UI
// @requires tag-it.js

$(document).ready(function()
{
	var guide_tags = ['Arts/Museums',
			'Business/Finance/Sales',
			'Info Systems/Technology/Library',
			'Communications/Media',
			'Social Service/Advocacy',
			'Engineering',
			'Healthcare',
			'Legal Professions',
			'Government/Public Service',
			'Public Policy',
			'Science/Research',
			'Education (PreK-12)',
			'Education (Higher Ed)',
			'Environment/Agriculture',
			'PeaceCorps/AmeriCorps/Teach for America, etc.'];
	
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
	
	/* If we're a career guide editing career interest tags, attach a class to any career guide buckets so that we can throw up
	   a warning on attempted deletion. */
	if ($("div.careerGuide").length)
	{
		$("#tagsItem li.tagit-choice").each(function(){
			if (edit_section[1] == 'tags' && jQuery.inArray($(".tagit-label", $(this)).text(), guide_tags) != -1)
			{
				$(this).addClass('confirmDelete');	
			}
		});
	}
	
	/* Handle clicks to any of our career bucket tag delete boxes. */
	$("#tagsItem li.confirmDelete a.tagit-close").click(function(event){
		if (!deleteConfirmed)
		{	
			showBucketTagConfirmation($(this).parent());
		}
	});
	
	$("input.ui-widget-content").focus();
	
	function showBucketTagConfirmation(tag) {
		var text = 'The tag <strong>'+ $(".tagit-label", tag).text() + '</strong> is a Career Guide category. If you remove' +
			' it from your profile, students will no longer be able to find you in this career area.';
		$("<div id='deleteConfirm' />").html(text).dialog(
			{
				title: "Warning",
				autoOpen: true,
				modal: true,
				buttons: {
					Cancel: function() {
						$(this).dialog("close");
					},
					"Delete Tag": function() {
						deleteConfirmed = true;
						$("a.tagit-close", tag).click();
						deleteConfirmed = false;
						$(this).dialog("close");
					}
				}
			}
		);
	}
	
});

