$(document).ready(function(){
	var toggler = $('<a href="#"></a>');
	var toggledElements = $('#extraheadcontentstructuredRow, #extraheadcontentRow, #uniquenameRow');
	if($('#extraheadcontentstructuredRow input').val().length < 1 &&
		$('#extraheadcontentRow textarea').val().length < 1 &&
		$('#uniquenameRow input').val().length < 1 )
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
	$('#administratorsectionheadingRow h3').wrapInner(toggler);
	$('#extraheadcontentstructuredRow .headItem').each(function(){
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
		$('#extraheadcontentstructuredRow .headItem').each(function(){
			urlVal = $(this).find('input.headItemUrl').val();
			if(shown < 1 && urlVal.length < 1)
			{
				$(this).show();
				shown++;
			}
		});
	});
	$('#extraheadcontentstructuredRow .headItem:last-child').after(adder);
});