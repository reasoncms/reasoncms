$(document).ready(function(){
// This code handles the Administrator Tools section of the content manager (if present)
  if ($('[id^="uniquename"] input').length) {
			var toggler = $('<a href="#"></a>');
			var toggledElements = $('[id^="extraheadcontentstructured"], [id^="extraheadcontent"], [id^="uniquename"], [id^="classname"]');
			if($('[id^="extraheadcontentstructured"] input').length && $('[id^="extraheadcontentstructured"] input').val().length < 1 ||
				$('[id^="extraheadcontent"] textarea').length && $('[id^="extraheadcontent"] textarea').val().length < 1 ||
				$('[id^="uniquename"] input').val().length < 1 ||
				$('[id^="classname"] input').length && $('[id^="classname"] input').val().length < 1 )
			{
				toggledElements.hide();
				toggler.addClass('closed');
			}
			else
			{
				toggler.addClass('open');
			}
			toggler.click(function(e){
				e.preventDefault();
				if($(this).hasClass('closed'))
				{
					toggledElements.show();
					$(this).removeClass('closed').addClass('open');
				}
				else
				{
					toggledElements.hide();
					$(this).removeClass('open').addClass('closed');
				}
			});
			$('[id^="administratorsectionheading"] h4').wrapInner(toggler);
			$('[id^="extraheadcontentstructured"] .headItem').each(function(){
				urlVal = $(this).find('input.headItemUrl').val();
				if(urlVal.length < 1)
				{
					$(this).hide();
				}
			});
			adder = $('<div class="headItemAdder"><a href="#">Add Head Item</a></div>');
			adder.find('a').click(function(e){
				e.preventDefault();
				var shown = 0;
				$('[id^="extraheadcontentstructured"] .headItem').each(function(){
					urlVal = $(this).find('input.headItemUrl').val();
					if(shown < 1 && urlVal.length < 1)
					{
						$(this).show();
						shown++;
					}
				});
			});
			$('[id^="extraheadcontentstructured"] .headItem:last-child').after(adder);
	}
});