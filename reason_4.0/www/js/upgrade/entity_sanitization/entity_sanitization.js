/**
 * Entity Sanitization JavaScript
 *
 * @author Nathan White
 */
$(document).ready(function()
{
	var sanitization_running = false;
	var submit_button;
	var start_button;
	var stop_button;
	var summary;
	
	function initialize_summary()
	{
		results_str = '';
		results_str += '<div id="results">';
		results_str += '<h3>Results</h3>';
		results_str += '<ul>';
		results_str += '<li><strong>Checked - </strong> <span class="num_checked">0</span></li>';
		results_str += '<li><strong>Updated - </strong> <span class="num_updated">0<span></li>';
		results_str += '<li><strong>Last ID Processed - </strong> <span class="starting_id">None<span></li>';
		results_str += '<li><strong>Progress - </strong> <span class="progress">0%<span></li>';
		results_str += '</ul>';
		results_str += '</div>';
		summary = $(results_str);
		$("#disco_form").before(summary);
	}
	
 	function initialize_form()
 	{
 		submit_button = $("#disco_form tr#discoSubmitRow input[type=submit]");
		start_button = $('<button>Start Sanitization</button>');
		stop_button = $('<button>Stop Sanitization</button>');
		submit_button.hide().before(start_button).before(stop_button);
		stop_button.hide();
		start_button.click(function(event)
 		{	
 			event.preventDefault();
 			start_sanitization();
 			return false;
 		});
 		stop_button.click(function(event)
 		{
 			event.preventDefault();
 			stop_sanitization();
 			return false;
 		});
 	}
 	
 	function start_sanitization()
 	{
 		if (!sanitization_running)
 		{
 			start_button.hide();
 			stop_button.show();
 			sanitization_running = true;
 			$("div.notice").remove();
 			ajax_submit_loop();
 		}
 	}
 	
 	/**
 	 * When next result comes in replace whole frame with the result.
 	 */
 	function stop_sanitization()
 	{
 		if (sanitization_running)
 		{
 			stop_button.hide();
 			start_button.show();
 			sanitization_running = false;
 		}
 	}
 	
 	/**
 	 * Perform a post request - update the display upon completion and then post another request.
 	 *
 	 * - Does some basic error handling, mostly of logout.
 	 */
 	function ajax_submit_loop()
 	{
 		if (sanitization_running)
 		{
 			var mypost = $.post($("#disco_form").attr("action"),
 								$("#disco_form").serialize());
 								
 			mypost.done(function( data )
 			{
 				var reason_upgrade_div = $($.parseHTML(data)).filter('div#reason_upgrade');
 				var results_div = reason_upgrade_div.find('div#results');
 				var starting_span = $("span.starting_id", results_div);
 				if (starting_span.length > 0)
 				{
 					var starting_id = starting_span.text();
 					var num_checked = $("span.num_checked", results_div).text();
 					var num_updated = $("span.num_updated", results_div).text();
 					var new_num_checked = (parseInt($(".num_checked", summary).text(), 10) + parseInt(num_checked, 10));
 					var new_num_updated = (parseInt($(".num_updated", summary).text(), 10) + parseInt(num_updated, 10));
 					var progress = (new_num_checked / $("#reason_upgrade #disco_form input#number_of_live_entitiesElement").val()) * 100;
 			
 					// update disco form
 					$("#reason_upgrade #disco_form input#starting_idElement").val(starting_id);
 					
 					// update summary display
 					$(".num_checked", summary).text(new_num_checked);
 					$(".num_updated", summary).text(new_num_updated);
 					$(".starting_id", summary).text(starting_id);
 					$(".progress", summary).text(progress.toFixed(2) + "%");
 					ajax_submit_loop();
 				}
 				else
 				{
 					stop_sanitization();
 					
 					var disco_form = $(reason_upgrade_div).find('form#disco_form');
 					
 					if (disco_form.length > 0)
 					{
 						// replace my disco form with the entire form from results which should show any errors.
 						$("#reason_upgrade #disco_form").replaceWith(disco_form);
 					
 						// show any errors (make dismissible)
 						var disco_errors = $(reason_upgrade_div).find('div#discoErrorNotice');
 						if (disco_errors.length > 0)
 						{
 							var notice = $('<div class="notice"></div>');
 							$("#reason_upgrade #disco_form").after(notice);
 							notice.prepend($('<h3>Processing stopped - disco reported an error</h3>'));
 							notice.append(disco_errors);
 						}
 						initialize_form();
 					}
 					else
 					{
 						// we could be logged out but in any case we didn't get a meaningful result.
 						var notice = $('<div class="notice"><h3>Error</h3><p>AJAX call failed - you may be logged out.</p></div>');
 						$("#reason_upgrade #disco_form").after(notice);
 					}
 				}
 			});
 		}
 	}
 	
 	// do the magic
 	initialize_summary();
	initialize_form();
});