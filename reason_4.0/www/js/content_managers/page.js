$(document).ready(function(){
		
	// Select the Other radio button when something is chosen from the page type menu.
	$('select[name="custom_page_other"]').change(function(){
		$('input[name="custom_page"]').prop('checked', true);	
	});
	
	// If the page title and nav title are the same, we should keep them in sync
	var nav_title_sync = ($("input#nameElement").val() == $("input#link_nameElement").val());
	
	// When the title changes, update the nav title if we're keeping them in sync
	$("input#nameElement").on("keyup change", function(){
		if (nav_title_sync) $("input#link_nameElement").val($("input#nameElement").val());		
	});
	
	// When the nav title changes, redetermine whether we should keep in sync with the page title
	$("input#link_nameElement").on("keyup change", function(){
		nav_title_sync = ($("input#nameElement").val() == $("input#link_nameElement").val());		
	});
	
	// This code handles the Administrator Tools section of the content manager (if present)
	if ($('#extraheadcontentstructuredRow input').length) {
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
		$('#administratorsectionheadingRow h4').wrapInner(toggler);
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
	}
});