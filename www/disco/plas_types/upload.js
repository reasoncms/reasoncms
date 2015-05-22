var oneTimeOnly = false; // if there are multiple upload elements on a page we want to run this script only once
$(document).ready(function() {
	var updateElementsAfterCheckboxClick = function(e) {
		var cb = $(e.currentTarget);
		var fileUploadUi = cb.parents('div.uploaded_file').siblings('div.file_upload');
		var boxIsChecked = cb.is(':checked');
		fileUploadUi.css("display", boxIsChecked ? "none" : "block");
	};

	var updateElementsAfterFileSelection = function(e) {
		var fileEl = $(e.currentTarget);
		var cbUi = fileEl.parent().siblings('div.uploaded_file').find('span.delete_existing_file');
		var fileIsSelected = fileEl.val() != "";
		cbUi.css("display", fileIsSelected ? "none" : "inline");
	};

	if (!oneTimeOnly) {
		$("span.delete_existing_file input[type='checkbox']").click(function(e) {
			updateElementsAfterCheckboxClick(e);
		});

		$("div.file_upload input[type='file']").change(function(e) {
			updateElementsAfterFileSelection(e);
		});

		oneTimeOnly = true;
	}
});
