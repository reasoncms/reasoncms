// custom scripts for the magazine theme

$(document).ready(function() {

	// initialize Isotope
	var $container = $('.homePostsDisplay .sections').isotope({ // Let's use the Isotope effect on the home page only
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