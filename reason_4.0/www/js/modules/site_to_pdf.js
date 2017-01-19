/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function() {
	
	var $toggle = $("<input type='checkbox' checked='checked' id='toggleControl' />");

	$toggle.click(function()
	{
		$("ul.pageTree input").prop('checked', this.checked);
	});
	
	$("div.contentArea form").prepend($toggle, "<label for='toggleControl'>Toggle selection</label>");

});