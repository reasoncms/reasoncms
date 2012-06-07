$(document).ready(function(){
	$("form.relFilters div.filterSet input.FilterSubmit ").hide();
	$("form.relFilters div.filterSet select.filterSelect").change(function(){
		$("form.relFilters").submit();
	});
});
