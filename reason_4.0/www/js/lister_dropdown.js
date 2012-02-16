$(document).ready(function(){
	$(".listTools input.viewMenuSubmit").hide();
	$(".listTools select.viewMenu").change(function(){
		$(this).parent('form').submit();
	});
});
