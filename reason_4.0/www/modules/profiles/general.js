$(document).ready(function(){
	var body = $('body').addClass('jsOn');
	var subsections = $('#profileInfo .subsection:not(.editing)');
	if( body.width() < 700 && $('#wrapper').width() < 700)
	{
		body.addClass('smallScreenMode');
		subsections.addClass('closed').children('.textZone').hide();
		subsections.children('h3').wrapInner('<a href="#">').children('a').click(function(e){
			$(this).parents('.subsection').toggleClass('closed').toggleClass('open').children('.textZone').slideToggle(350, 'linear');
			e.preventDefault();
		});
	}
	else {
		body.addClass('largeScreenMode');
		var toggleTruncation = function(subsection) {
			subsection.toggleClass('open').toggleClass('closed');
			if(subsection.hasClass('open'))
				subsection.find('.toggler>a').html('- Less');
			else
				subsection.find('.toggler>a').html('+ More');
		};
		var setUpTruncation = function(){
			subsections.each(function(){
				var subsection = $(this);
				textZone = subsection.addClass('notTruncated').children('.textZone');
				textZone.after('<div class="toggler"><a href="#">+ More</a></div>');
				textZone.wrapInner('<div class="innerWrap"></div>');
				subsection.find('.toggler>a').click(function(e){
					toggleTruncation(subsection);
					e.preventDefault();
				});
				subsection.append('<div class="truncationExample"></div>');
				subsection.addClass('closed').addClass('setUp');
				applyTruncation($(this));
			});
		};
		var applyTruncation = function(subsection) {
			example = subsection.children('.truncationExample').first();
			textZone = subsection.find('.textZone>.innerWrap').first();
			if(textZone.height() > example.height() * 1.5 ){
				subsection.addClass('truncated').removeClass('notTruncated');
			}
			else{
				subsection.removeClass('truncated').addClass('notTruncated');
			}
		};
		setUpTruncation();
		$(window).resize(function(){
			subsections.each(function(){
				applyTruncation($(this));
			});
		});
	}
	
	/* If an editing block is opened below the center of the page, scroll it up to the middle */
	if ($('div.editing').length)
	{
		var middle = parseInt($(window).height()/2);
		var top = $('div.editing').offset().top;
		if ( top > middle)
		{
			var element_middle = parseInt( $('div.editing').height()/2);
			$('html, body').animate({scrollTop: top - middle + element_middle}, 1000);
		}		
	}
	
	/* If lists of tags have overflow items, replace them with a link to show those items */
	$('ul.profiles').each(function(){
		if ($(this).children('li.overflow').length)
		{
			var list = $(this).children('li.overflow');
			$(list).hide();
			var more = document.createElement( "a" );
			var listitem = document.createElement( "li" );
			$(listitem).addClass('moreLink');
			$(more).attr('href', '#');
			$(more).append('More...');
			$(more).click(function(event){$(list).show(); $(this).hide(); event.preventDefault(); });
			$(listitem).append($(more));
			$(this).append($(listitem));
		}
	});
	
	/* If the contact form is active and we have a templates menu, reload the page with the
	 * chosen template after verifying that we can replace existing text.
	 */
	$('select#contact_templateElement').change(function(){
		var id = $('textarea[name="contact_body"]').attr('id');
		var editorContent = tinyMCE.get(id).getContent();	
		if (!(editorContent == '' || editorContent == null))
		{
			if (! confirm('Replace existing text?')) { return; }
		}
			
		var origin = window.location.protocol.replace(/\:/g, '') + '://' + window.location.host + window.location.pathname;
		window.location.href = origin + '?contact=' + $(this).val();
	});
	
});