function handleLinkness() {
	var linkRadioStatus = $("input[name='is_link_story']:checked").val(); // 0 means "no, not a link"
	// console.log("radio: [" + (linkRadioStatus == 0 ? "NO" : "YES") + "]");

	$("#linkposturlItem").css("display", linkRadioStatus == 0 ? "none" : "block");
	$("#linkorgItem").css("display", linkRadioStatus == 0 ? "none" : "block");
	// $("tr#descriptionRow").css("display", linkRadioStatus == 1 ? "none" : "block");
	$("#contentItem").css("display", linkRadioStatus == 1 ? "none" : "block");
	$("#embedhandlerItem").css("display", linkRadioStatus == 1 ? "none" : "block");
}

$(document).ready(function() {
	// the "is this story a link" radio button needs to show/hide certain UI elements...
	handleLinkness();
	$("input[name='is_link_story']").change(function(evt) { handleLinkness(); });
});
