$(document).ready(function(){

	// Create an array of objects containing references to the different tab elements
	var tabs = new Array();
	$("#navSections li").each(function(){
		var matches = $(this).attr('id').match(/tab(.*)/);
		tabs.push( {'name':matches[1], 'tab':$(this), 'section':$("div#tagInfo div#section"+matches[1])} );	
	});
	
	// If a tab is clicked, update the tab display
	$("#navSections li a").click(function(){
		updateTabs($(this).attr('href'));
		return false;
	});
	
	// In tab mode, hide the section names
	$("#tagInfo h3").hide();
	
	updateTabs();
	
	// Figure out which tab should be shown (either the passed tab, the URL hash tab, or the first tab)
	// and change the visibility and styles appropriately.
	function updateTabs(hash) {
		if (hash) {
			var key = hash.substring(1);
		} else if (window.location.hash) {
			var key = window.location.hash.substring(1);
		} else {
			var key = tabs[0].name;
		}
		
		for (var section in tabs) {
			if (tabs[section].name == key)
			{
				tabs[section].tab.addClass('active');
				tabs[section].section.show();
			} else {
				tabs[section].tab.removeClass('active');
				tabs[section].section.hide();
			}
		}
	}
});
