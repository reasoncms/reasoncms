$(document).ready(function(){
	var box = $('.featureTypeVideo a.anchor');
	if(box.length > 0)
	{
		// lets find the reason_package_http_base path and thus, our cross icon by looking at the nyroModal location
		var silk_icon_src = new String();
		$("script[src*='/nyroModal/']:first").each(function()
		{
			var srcParts = $(this).attr("src").split("nyroModal/");
			silk_icon_src = srcParts[0]+"silk_icons/cross.png";
		});
		box.nyroModal(
		{
			bgColor:'#000000',
			closeButton: '<a href="#" class="nyroModalClose" id="closeBut" title="close"><img src="'+silk_icon_src+'" alt="Close" width="16" height="16" class="closeImage" /><span class="closeText">Close</span></a>',
			processHandler:size_modal_window,
			zIndexStart:9999,
		});
	}
	function size_modal_window(settings)
	{
		settings.height=700;
		$('#nyroModalWrapper').css('height','700px');
	}
});