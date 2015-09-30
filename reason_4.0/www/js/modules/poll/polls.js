/**
 * Seems like right now we can only call $.plot once per page. I'm not sure why this is but when we call it twice it
 * doesn't work so we make sure to call it just once.
 */
$(document).ready(function()
{
	function graph(colors, label, dataset)
	{
		var data = new Array();
		
		for (var i = 0; i <= (label.length - 1); i++) 
		data[i] = { color: colors[i], label: label[i],  data: dataset[i] };
		
		// MAIN GRAPH
		if ($("#graph1").length > 0)
		{
			$.plot($("#graph1"), data,
			{
				series: {
					pie: {
						show: true,
						label: {
							show: true,
							radius: 1,
							formatter: function(label, series){
								return '<div style="font-size:11pt;text-align:center;padding:3px;color:white;">'+label+'<br/>'+Math.round(series.percent)+'%</div>';
							},
							background: { opacity: 1 }
						}
					}
				},
				legend: {
					show: false
				}
			});
		}
		else if ($("#interactive").length > 0)
		{
			// INTERACTIVE graph
			$.plot($("#interactive"), data, 
			{
				series: {
					pie: { 
						show: true,
						offset: {
							top: 50
						}
					}
				},
				grid: {
					hoverable: true,
					clickable: true
				},
				legend: {
					position: "nw"
				}
			});
			$("#interactive").bind("plothover", pieHover);
			$("#interactive").bind("plotclick", pieClick);
		}
	
		function pieHover(event, pos, obj) 
		{
			if (!obj)
						return;
			percent = parseFloat(obj.series.percent).toFixed(2);
			$("#hover").html('<span style="font-weight: bold; color: '+obj.series.color+'">'+obj.series.label+' ('+percent+'%)</span>');
		}
		
		function pieClick(event, pos, obj) 
		{
			if (!obj)
						return;
			percent = parseFloat(obj.series.percent).toFixed(2);
			alert(''+obj.series.label+': '+percent+'%');
		}
	}
	graph(color, lbl, data);
});