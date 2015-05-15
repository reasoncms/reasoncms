/** 
 * focus.js - Focus on username field when page loads
 * 
 * Any input field receiving or losing focus will disable the script
 *
 * @author Nathan White
 * @requires jQuery
 */

$(document).ready(function()
{	
	var disable = false;
	var turnOff = function()
	{
		disable = true;
		$("input").each(function()
		{
			$(this).unbind("focus", turnOff);
			$(this).unbind("blur", turnOff);
		});
	}
		
	$("input").each(function()
	{
		$(this).bind("focus", turnOff);
		$(this).bind("blur", turnOff);
	});
	
	$("#login input[name=username]").each(function()
	{
		if (!disable)
		{
			$(this).focus();
		}
		return false;
	});
});