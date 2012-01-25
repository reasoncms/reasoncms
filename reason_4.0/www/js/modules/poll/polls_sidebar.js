function graph(colors, label, dataset) {
	
	
	var data = new Array();
	
	for (var i = 0; i <= (label.length - 1); i++) 
	data[i] = { color: colors[i], label: label[i],  data: dataset[i] };
	
	

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
