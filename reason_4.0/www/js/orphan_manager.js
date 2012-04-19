$(document).ready(function() {
	
	$("table.orphans thead th.option").append('<br /><span class="select_all" >Select All</span>');

	$("table.orphans thead th.option span.select_all").click(function(){
		var index = $(this).parent().index() + 1;
		$(this).closest("table.orphans").find("td:nth-child("+index+") input").attr('checked', true);
	});

	$("#radio_max_0").click(function(){
		$("tr#maxnumRow").show();
	});
	$("#radio_max_1").click(function(){
		$("tr#maxnumRow").hide();
	});

	$("tr#typesRow a#select_all").show();
	$("tr#typesRow a#select_all").click(function(){
		$("form#disco_form tr#typesRow select option").attr('selected','selected');
	});
});