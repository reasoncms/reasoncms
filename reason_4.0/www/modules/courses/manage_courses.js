/** Course Manager support for the catalog
  * @author Mark Heiman
  */
$(document).ready(function()
{
	//var module_id = $.reasonAjax.get_module_identifier($('#coursesList'));

	$('select#courseSubjects, select#courseYears').change(function(){
		if ($(this).val())
		{
			var origin = window.location.protocol.replace(/\:/g, '') + '://' + window.location.host + window.location.pathname;
			var params = '?subject=' + $('select#courseSubjects').val() + '&year=' + $('select#courseYears').val();
			if ($(this).attr('id') == 'courseYears' && $('input#courseId').length) params = params + '&course=' + $('input#courseId').val();
			window.location.href = origin + params;
		}
	})
	
	$('li.courseListRow').hover(function() {
		$('a.activateCourse, a.deactivateCourse', $(this)).toggle();
	});
	
});
