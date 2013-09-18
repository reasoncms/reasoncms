$(document).ready(function(){
	var galleryItems = $('div.publication div.fullPost .media .mediaGallery>li');
	var widest = 100;
	galleryItems.find('img.placard').show();
	galleryItems.find('img.placard').each(function(){
		width = $(this).width();
		if(width > widest)
			widest = width;
	});
	var playingMediaBlock = $('<div class="playingMedia" tabindex="-1"></div>');
	$('div.publication div.fullPost .media .mediaGallery').addClass('galleryActive').before(playingMediaBlock);
	var focus_media = function(listItem){
		galleryItems.removeClass('showing');
		listItem.addClass('showing');
		playingMediaBlock.empty();
		listItem.find('.mediaDisplay').contents().clone().prependTo(playingMediaBlock);
	}
	galleryItems.find('.mediaDisplay').hide();
	var link = $('<a href="#" title="View media" class="pickerLink"></a>');
	link.css('width', widest + 'px');
	link.click(function(e){
		e.preventDefault();
		listItem = $(this).parents('div.publication div.fullPost .media .mediaGallery>li');
		if(listItem.hasClass('showing'))
			return;
		focus_media(listItem);
		playingMediaBlock.focus();
	});
	galleryItems.find('.titleBlock').wrapInner(link);
	var tallest = 125;
	$(window).load(function(){
		galleryItems.find('a.pickerLink').each(function(){
			height = $(this).height();
			if(height > tallest)
				tallest = height;
		});
		galleryItems.find('a.pickerLink').css('height', tallest + 'px');
	});
	focus_media( galleryItems.first() );
});