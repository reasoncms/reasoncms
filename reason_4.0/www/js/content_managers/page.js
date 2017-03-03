$(document).ready(function(){

	// Select the Other radio button when something is chosen from the page type menu.
	$('select[name="custom_page_other"]').change(function(){
		$('input[name="custom_page"]').prop('checked', true);
	});

	// If the page title and nav title are the same, we should keep them in sync
	var nav_title_sync = ($("input#nameElement").val() == $("input#link_nameElement").val());

	// When the title changes, update the nav title if we're keeping them in sync
	$("input#nameElement").on("keyup change", function(){
		if (nav_title_sync) $("input#link_nameElement").val($("input#nameElement").val());
	});

	// When the nav title changes, redetermine whether we should keep in sync with the page title
	$("input#link_nameElement").on("keyup change", function(){
		nav_title_sync = ($("input#nameElement").val() == $("input#link_nameElement").val());
	});
});
