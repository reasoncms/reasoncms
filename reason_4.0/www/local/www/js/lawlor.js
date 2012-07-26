		$(document).ready(function() {
			$('.main-nav ul.nav').superfish({
				hoverClass: 'hover',
				autoArrows: false
				});
				
			$('.highlight').cycle({
				fx: 'fly',
				speed: 300,
				sync: 1,
				timeout: 4000			
				});
				
			equalHeight($('.supplemental'));
			$('.highlightItem:first-child').setHighlight();
			$('.highlightItem').addClass('clearfix').setTextPadding();
			
			
			$('img.caption').caption();
			
			$('.body .content .supplemental h2, .body .content .supplemental h3').each(function(){
				$(this).html($(this).text().replace(/(^\w+)/,'<strong>$1</strong>'));
				});
				
			$('.links a').wrapInner('<span></span>');
			setSidebarPadding();
		});	
