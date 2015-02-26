$(document).foundation({
	reveal : {animation: 'fade',animation_speed: 100},
	equalizer : { equalize_on_stack: false }
});

$(document).ready(function() {

	// Add classes for showing/hiding the mobile navigtaion
	var menuToggle = $('#globalNavToggle');
	var menuBlock = $('#globalNav');

	var searchToggle = $('#utilityNavToggle');
	var searchBlock = $('#utilityNav');
	
	$(menuToggle).click(function(e) {
		menuToggle.toggleClass('open');
		if(menuBlock.hasClass('open')) {
			menuBlock.removeClass('open').addClass('closed');
		}
		else {
			menuBlock.removeClass('closed').addClass('open');
		}
		e.preventDefault();
	});
	$(searchToggle).click(function(e) {
		searchToggle.toggleClass('open');
		if(searchBlock.hasClass('open')) {
			searchBlock.removeClass('open').addClass('closed');
		}
		else {
			searchBlock.removeClass('closed').addClass('open');
		}
		e.preventDefault();
	});

	// Adds "Search" text placeholder to module search filters
	$(".searchForm .search").attr("placeholder", "Search");

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