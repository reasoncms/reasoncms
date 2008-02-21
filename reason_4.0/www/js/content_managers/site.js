/** 
 * site.js - Javascript for site content manager
 *
 * @author Nathan White
 * @requires jQuery
 */

$(document).ready(function()
{	
	var footer_row = $("tr#customfooterRow");
	var select_elem = $("select#use_custom_footerElement");
	
	if ( $(select_elem).val() != 'yes' ) $(footer_row).hide(); // hide initially if necessary
	
	$(select_elem).change(function() // conditionally hide/show
	{
		if ($(this).val() == 'yes') $(footer_row).show(); 
		else $(footer_row).hide();
	});
});