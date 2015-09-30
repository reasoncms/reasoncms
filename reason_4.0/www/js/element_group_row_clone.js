/**
 * Finds a tabled element group within a disco form and provides a link to duplicate the last row indefinitely.
 *
 * New rows are identical to the first row with input elements in the group, but element names are incremented in each row.
 *
 * Accepts these parameters via URL
 *
 * - element_group (required) name of the disco element group
 * - clear (optional) boolean that specififes whether or not to clear the value of the row before it is cloned
 * - text (optional) custom text used in the link to create a new row
 *
 * @author Nathan White
 *
 * @todo currently only works for rows with input fields, expand to handle selects, textarea, etc
 * @requires jQuery
 */
 
$(document).ready(function()
{
	/**
	 * @var src attribute which contains script parameters
	 */
	var js_src = $('script[src*="element_group_row_clone.js"]:first').attr("src");
	
	/**
	 * @var string the name of the disco element group
	 */
	var element_group = _verifyElementGroup(_queryString('element_group', js_src));
	
	/**
	 * @var boolean whether or not to clear the value of the row before it is cloned
	 */
	var clear = _verifyClear(_queryString('clear', js_src));
	
	/**
	 * @var string the text of the link used to create a new row
	 */
	var text  = _verifyText(_queryString('text', js_src));
	/** 
	 * @var array input_name input field names from the first row
	 */
	 
	var input = new Array();
	
	createAddRowLink();
	
	/**
	 * Populates the input array from the first row in the table
	 */
	function setupBaseElementNames()
	{
		$("tr#"+element_group+"Row table tr:has(input):first input").each(function(index)
		{
			input[index] = $(this).attr('name');
		});
	}
	
	/**
	 * Adds a link to create a new row to the DOM
	 */
	function createAddRowLink()
	{
		var my_text = (text) ? text : "+ Add a Row";
		var link = $('<p><strong><a href="#">'+my_text+'</a></strong></p>');
		$("a", link).click(createRow);
		$("tr#"+element_group+"Row table").after(link);
	}
	
	/**
	 * Clone the last row in the table - increment each element name field
	 */
	function createRow()
	{
		if (!input.length) setupBaseElementNames();
		hours_table_rows = $("tr#"+element_group+"Row table tr");
		$("tr#"+element_group+"Row table tr:last").each(function(index)
		{
			row_index = $(hours_table_rows).index(this);
			new_row = $(this).clone(true).insertAfter(this);
			$("input", new_row).each(function(index)
			{
				if (clear) $(this).val("");
				$(this).attr('name', input[index] + row_index);
				$(this).prev("a[name^='"+input[index]+"']").attr('name', input[index] + row_index + "_error");
			});
		});
		return false;
	}
	
	/** 
	 * Helper function to grab the value that corresponds to a key in a url
	 */
	function _queryString( key, url )
	{
		if ( (url.search( RegExp( "[?&]" + key + "=([^&$]*)", "i" ) )) > -1 ) return RegExp.$1;
		else return null;
	}
	
	/**
	 * Helper to verify the integrity of the element_group parameter
	 */
	function _verifyElementGroup( string )
	{
		if (string)
		{
			if (string.match(/^[a-z0-9_-]*$/i))
			{
				return string;
			}
		}
		return false;
	}
	
	/**
	 * Helper to verify the integrity of the text parameter
	 *
	 * We allow alphanumeric characters, plus the _, +, -, and space characters
	 */
	function _verifyText( string )
	{
		if (string)
		{
			decoded_string = decodeURIComponent(string);
			if (decoded_string.match(/^[a-z0-9 _+-]*$/i))
			{
				return decoded_string;
			}
		}
		return false;
	}
	
	/**
	 * Helper to verify the integrity of the string parameter - must be set to true otherwise we return false
	 */
	function _verifyClear( string )
	{
		if (string)
		{
			return (string == "true");
		}
		return false;
	}
});
