$(document).ready(function () {
	$('#checkbox_lock_all_fields').click (function ()
	{
		if ( $(this).is (':checked'))
		{
			$('#lockspecificfieldsRow input').attr('checked', true);
		}
		else
		{
			$('#lockspecificfieldsRow input').attr('checked', false);
		}
	});
	$('#checkbox_lock_all_right_relationships').click (function ()
	{
		if ( $(this).is (':checked'))
		{
			$('#lockspecificrightrelationshipsRow input').attr('checked', true);
		}
		else
		{
			$('#lockspecificrightrelationshipsRow input').attr('checked', false);
		}
	});
	$('#checkbox_lock_all_left_relationships').click (function ()
	{
		if ( $(this).is (':checked'))
		{
			$('#lockspecificleftrelationshipsRow input').attr('checked', true);
		}
		else
		{
			$('#lockspecificleftrelationshipsRow input').attr('checked', false);
		}
	});
});