/** 
 * jump_navigation.js performs various operations on forms on a page with class "jumpNavigation."
 *
 * - Binds to on_change event of select element "jumpDestination"
 * - Hides input element with class "jump_navigation_go" (typically would be a go button)
 * - Submits the form upon the onChange event
 *
 * @author Nathan White
 * @todo Improve accessibility by disabling automatic redirect and showing Go button in IE when keyboard navigation is being used
 * @requires jQuery
 */

$(document).ready(function()
{
	$("form.jumpNavigation select.jumpDestination").change(function()
	{
		$(this).parent().submit();
	});
	$("form.jumpNavigation input.jumpNavigationGo").hide();
});