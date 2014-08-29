/** Course Manager support for the catalog
  * @author Mark Heiman
  */
$(document).ready(function()
{
	//var module_id = $.reasonAjax.get_module_identifier($('#coursesList'));

	$('select#courseSubjects').change(function(){
		if ($(this).val())
		{
			var origin = window.location.protocol.replace(/\:/g, '') + '://' + window.location.host + window.location.pathname;
			window.location.href = origin + '?subject=' + $(this).val();
		}
	})
	
	$('#courseList').on('change','input', function(){
		toggle_course_state($(this).val());
	})

	$('#courseList').on('click', 'a.courseRevealer', function(){
		$('div.description', $(this).closest('.courseListCourse')).toggleClass('closed');
		$(this).toggleClass('closed').html(($(this).hasClass('closed')) ? '&#9654;' : '&#9660;');
	});
	
	function get_courses_for_subject(subject)
	{
		$.getJSON(document.URL, {
			module_identifier: module_id,
			module_api: "standalone",
			subject: subject,
		},
		function(return_data){
			if (return_data)
			{
				$('#courseList').html('<p class="instructions">Check or uncheck a course below to add or remove it. A disabled checkbox indicates a course you can\'t add or remove.</p>');
				$.each( return_data, function( i, item ) {	
					var input_element = $('<input>')
						.attr('id','pickCourse_'+i)	
						.attr('type','checkbox')
						.attr('name','choose_course['+i+']')
						.attr('value',i)
						.attr('checked',item['selected'])
						.attr('disabled',!item['editable']);							
						
					var label_element = $('<label>')
						.attr('for','pickCourse_'+i)
						.html(item['title']);
					
					if (item['desc'])
					{
						var revealer_element = $('<a>')
							.attr('class', 'courseRevealer closed')
							.attr('title', 'Show Description')
							.html('&#9654;');
							
						var description_element = $('<div>')
							.attr('class','description closed')
							.html(item['desc']);
							
						var container_class = 'courseListCourse hasDesc';
					} else {
						var description_element = null;
						var revealer_element = null;
						var container_class = 'courseListCourse';
					}
						
					$('#courseList').append($('<div>')
						.attr('class', container_class)
						.append(input_element, 
							$('<div>')
								.attr('class','courseTitle')
								.append(label_element, revealer_element), 
							description_element)
						);
				});
			} else {
				$('#courseList').html('<p>(No courses found)</p>');	
			}
		});
	}

	function toggle_course_state(id)
	{
		$.getJSON(document.URL, {
			module_identifier: module_id,
			module_api: "standalone",
			toggle_course: id,
		},
		function(return_data){
			// If the call didn't return true, we need to undo the user's action.
			if (!return_data)
			{
				$('#pickCourse_'+id).prop('checked', !$('#pickCourse_'+id).prop('checked'));	
			}
		});
	}
});
