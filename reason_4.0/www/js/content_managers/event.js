var path = window.location.pathname;
var params = window.location.search;
var event_date;

function get_date()
{
	if ($("input#datetime").val() && $("input#datetime-mm").val() && $("input#datetime-dd").val())
	{
		return $("input#datetime").val() + "-" +  $("input#datetime-mm").val() + "-" + $("input#datetime-dd").val();
	} else {
		return false;
	}
}

function populate_calendar_preview()
{
	var new_date = get_date();
	if (new_date && new_date != event_date)
	{
		event_date = new_date;
		// If the preview div already exists, use it; otherwise, create it.
		if ($("div#event_preview").length)
			$("div#event_preview").html('<img src="/global_stock/images/activity.gif" class="activityInd" />');
		else
			$("div.managerNav").append('<div id="event_preview"><img src="/global_stock/images/activity.gif" class="activityInd" /></div>');
		$("div#event_preview").load("/reason_package/reason_4.0/www/displayers/calendar_preview.php",
			{
			date: event_date,
			site: $("div.sites > strong").first().text(),
			path: path,
			params: params,
			});
		
	}
}

function borrow_confirm(url, id)
{
	$("div#borrow_confirm div#event_detail").load("/reason_package/reason_4.0/www/displayers/calendar_preview.php",
			{
			event_id: id
			});
	
	$("div#borrow_confirm a.confirm").attr('href', url);
	$("div#borrow_confirm").css('display','block');	
	$("div#borrow_confirm_shade").css('display','block');	
}

function borrow_confirm_cancel()
{
	$("div#borrow_confirm").css('display','none');	
	$("div#borrow_confirm_shade").css('display','none');	
}

$(document).ready(function() {	
  	
	$("input#datetime").change(function(){
		populate_calendar_preview()	
	});

	$("input#datetime-mm").change(function(){
		populate_calendar_preview()	
	});
	
	$("input#datetime-dd").change(function(){
		populate_calendar_preview()	
	});
		
	$("input#datetime").focus(function(){
		populate_calendar_preview()	
	});
		
	populate_calendar_preview();
	borrow_confirm_cancel();
});
