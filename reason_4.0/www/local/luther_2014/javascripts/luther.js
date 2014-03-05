// custom scripts for luther.edu

$(document).ready(function() {

	// add js class
	$('body').addClass('js');
	
	// open / close global search on mobile
	$('#mobile-nav a.search').click(function( event ) {
		$(this).toggleClass('open');
		$('#search-nav').toggleClass('open');
		event.preventDefault();
	});
	
	// open / close minisite navigation on mobile
	$('#navWrap a.toggle').click(function( event ) {
		$(this).toggleClass('open');
		$('#navWrap').toggleClass('open');
		event.preventDefault();
	});
		
	// open / close global navigation on desktop	
	$('#global-nav .sections li a').click(function() {
		$(this).parent().toggleClass('open');
	});
		

});