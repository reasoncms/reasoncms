$(document).ready(function(){
	var selectors = $('.builder-section select.select-module');
	selectors.change(function(e){
		var changed = $(this);
		if(changed.val() == ""){
			$(this).siblings('.documentation').remove();
			return;
		}
		selectors.not($(this)).each(function(){
			if($(this).val() != "" && $(this).val() == changed.val()){
				$(this).val("");
				if($(this).siblings('.documentation')){
					changed.siblings('.documentation').remove();
					changed.after($(this).siblings('.documentation'));
				}
			}
		});
	});
});