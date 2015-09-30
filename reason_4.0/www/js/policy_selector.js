$(document).ready(function(){
	$(".policyForm input.rootMenuSubmit").hide();
	$(".policyForm select.rootMenu").change(function(){
		$(this).parent('form').submit();
	});
});
