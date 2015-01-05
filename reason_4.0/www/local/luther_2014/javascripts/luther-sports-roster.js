// custom tablesorter script for sports roster pages

// tablesorter.js is used to sort tables. See http://tablesorter.com/
// add parser through the tablesorter addParser method to sort class year
$(document).ready(function() {

 //   var sport_pathname = window.location.pathname;
    	var tablesorteropts ={
      		theme: 'ice',
      		tabIndex: true,
      		widthFixed: false,
      		sortList: [[1,1]],
      		widgets : ["zebra", "columns", "filter", "resizeable"],
      		widgetOptions : 
		{
        		columns : [ "primary", "secondary", "tertiary" ],
        		columns_thead : true,
        		filter_childRows : false,
        		filter_columnFilters : true,
        		filter_cssFilter : "tablesorter-filter",
        		filter_formatter : null,
        		filter_functions : 
			{
				'.athlete_position_event' : true,
				'.athlete_height' : true,
          			'.athlete_class_year' : true,
				'.athlete_hometown_state' : true,
				'.athlete_weight' :
				{
					"<200"	  : function(e, n, f, i, $r) { return n < 200;},
					"200-250"	  : function(e, n, f, i, $r) { return n >=200 && n<=250;},
					"250-300"	  : function(e, n, f, i, $r) { return n >=250 && n<=300;},
					">300"         : function(e, n, f, i, $r) { return n<300;}
				},
			},  
        		filter_hideFilters : false,
        		filter_ignoreCase : true,
        		filter_liveSearch : true,
        		filter_searchDelay : 300,
        		filter_serversideFiltering: false,
        		filter_startsWith : false,
        		filter_useParsedData : false
      		}
	};

	$("table").tablesorter(tablesorteropts);

});

