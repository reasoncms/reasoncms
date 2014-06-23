$(document).ready(function() {
	$(".fancybox-thumb").fancybox({
		prevEffect: 'none',
		nextEffect: 'none',
		helpers: {
			title: {
				type: 'inside'
			},
			thumbs: {
				width: 50,
				height: 50
			}
		}
	});
});
