$(document).ready(function() {
	
//	init();
//	alert($('.panel_container.tabs ul.tabs').height());
	$('.tab_container').css( 'min-height', $('.panel_container.tabs ul.tabs').height() +30 + 'px' );
//	alert($("ul.tabs li").length);

	$("ul.tabs li").click(function(event) {
		event.preventDefault();
		$("ul.tabs li").removeClass("active_tab"); //Remove any "active" class
		$(this).addClass("active_tab"); //Add "active" class to selected tab
		$(".tab_content").hide(); //Hide all tab content

		var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
		$(activeTab).fadeIn(); //Fade in the active ID content
		return false;
	});
	
	function init()
	{
		var n=$("ul.tabs li").length;
		var h=$("ul.tabs li").height();
		var tab_h=$('.tab_container').height();
		if(n*h > tab_h)
		{
			var dh=(n*h)*0.05;
			var new_h=n*h+dh+40;
			$('.tab_container').height( new_h );
		}
	}

});