// custom scripts for the magazine theme

$(document).ready(function() {

	// initialize Isotope
	var $container = $('.sections').isotope({
	   itemSelector: '.post',
		masonry: {
			columnWidth: '.grid-sizer'
		}
	});
	// layout Isotope again after all images have loaded
	$container.imagesLoaded( function() {
	  $container.isotope('layout');
	});

});