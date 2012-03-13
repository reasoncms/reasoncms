$(document).ready(function() {
	$('select[name="set_all"]').change(function(){
		var choice = $(this).val();
		$('select[name^="new_site_ids"]').each(function(){
			$(this).val(choice);
		});
	});		
});
