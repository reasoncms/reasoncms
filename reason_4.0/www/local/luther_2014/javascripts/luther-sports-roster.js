// custom tablesorter script for sports roster pages

//used to sort tables. See http://tablesorter.com/
$(document).ready(function() {

    	var tablesorteropts ={
      		theme: 'ice',
      		tabIndex: true,
      		widthFixed: false,
		sortInitialOrder:"asc",
      		widgets : ["zebra", "columns", "resizeable"],
      		widgetOptions : 
		{
        		columns : [ "primary", "secondary", "tertiary" ],
        		columns_thead : false,
        	}
	};

	$("table").tablesorter(tablesorteropts);


});

