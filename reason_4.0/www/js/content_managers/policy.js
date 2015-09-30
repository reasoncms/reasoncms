function do_policy_element_hiding() {
	selector = '#descriptionRow, #keywordsRow, #lastreviseddateRow, #lastrevieweddateRow, #limitaccessRow, #audiencesRow';
	if( $('option:selected', '#parent_idElement').index() > 1)
		$(selector).hide();
	else
		$(selector).show();
}
$(document).ready(function(){
	do_policy_element_hiding();
	$('#parent_idElement').change(function(){
		do_policy_element_hiding();
	});
});