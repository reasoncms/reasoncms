function handleLinkness() {
	var linkRadioStatus = $("input[name='is_link_story']:checked").val(); // 0 means "no, not a link"
	// console.log("radio: [" + (linkRadioStatus == 0 ? "NO" : "YES") + "]");

	$("tr#linkposturlRow").css("display", linkRadioStatus == 0 ? "none" : "table-row");
	$("tr#linkorgRow").css("display", linkRadioStatus == 0 ? "none" : "table-row");
	// $("tr#descriptionRow").css("display", linkRadioStatus == 1 ? "none" : "table-row");
	$("tr#contentRow").css("display", linkRadioStatus == 1 ? "none" : "table-row");
	$("tr#embedhandlerRow").css("display", linkRadioStatus == 1 ? "none" : "table-row");
}

$(document).ready(function() {
	// the "is this story a link" radio button needs to show/hide certain UI elements...
	handleLinkness();
	$("input[name='is_link_story']").change(function(evt) { handleLinkness(); });
});
