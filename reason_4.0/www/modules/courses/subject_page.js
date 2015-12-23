/** Subject Page support for the catalog
  * @requires JQuery UI
  * @author Mark Heiman
  */
$(document).ready(function()
{
	var module_id = $.reasonAjax.get_module_identifier($('#subjectPageModule'));

	// Look at all the lists that aren't designated as courseList and see if they appear
	// to be lists of courses. If they are, add the appropriate classes so that they'll 
	// get picked up by the linking process below.
	var course_regex = /\b([A-Z]{2,4} [0-9]{2,3}\w?)\b/g;
	$("ul:not(.courseList) li").each(function(){
		if ($(this).html().match(course_regex))
		{
			$(this).html($(this).html().replace(course_regex, function(match, p1){
				return '<span class="courseNumber">'+p1+'</span>';
			}));
			$(this).parent().addClass('courseList');
		}
	});

	// For each course number in a list of titles, make the number clickable and 
	// fire off a request for the course description to be opened in a modal dialog.
	$("ul.courseList span.courseNumber")
		.addClass("clickable")
		.click(function(){

			if ( $(this).attr('course') )
				var course = $(this).attr('course');
			else
				var course = $(this).text();

			$.getJSON(document.URL, {
				module_identifier: module_id,
				module_api: "standalone",
				get_course: course,
			})
			.done(function(response){
				var courseDialog = $('<div id="courseDialog">' + response.description + '</div>');
				courseDialog.dialog({
					title: response.title,
					modal: true
				});
			})
			
		});
	
	// Close open modal dialogs when the background is clicked.
    $(document.body).on("click", ".ui-widget-overlay", function()
    {
        $.each($(".ui-dialog"), function()
        {
            var $dialog;
            $dialog = $(this).children(".ui-dialog-content");
            if($dialog.dialog("option", "modal"))
            {
                $dialog.dialog("close");
            }
        });
    });;
    		
});
