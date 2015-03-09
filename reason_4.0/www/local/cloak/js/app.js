$(document).foundation({
	reveal : {animation: 'fade',animation_speed: 50},
	equalizer : { equalize_on_stack: false }
});

$(document).ready(function() {

	// SANS IMAGELOADED
	// var $container = $('#imageGalleryItemList');
	// $container.isotope({
	// 	itemSelector: '.item',
	// 	//layoutMode: 'cellsByRow' 
	// });

	// initialize Isotope after all images have loaded
	// var $container = $('.isotope').imagesLoaded( function() {
	//   $container.isotope({
	//     itemSelector: '.isotope-item',
	//   });
	// });

	// initialize Isotope
	// var $container = $('.isotope').isotope({
	//    itemSelector: '.isotope-item',
	// });
	// // layout Isotope again after all images have loaded
	// $container.imagesLoaded( function() {
	//   $container.isotope('layout');
	// });
});