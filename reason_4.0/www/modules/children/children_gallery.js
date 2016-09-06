$(document).ready(function(){
	equalizeHeight = function(children){
		children.css('height','auto');
		max = 0;
		children.each(function(){
			height = $(this).height();
			if(height > max)
				max = height;
		});
		if(max > 0)
			children.height(max);
	};
	
	children = $('ul.childrenList>li>a');
	equalizeHeight(children);
	
	$(window).resize(function(){
		equalizeHeight(children);
	});
});