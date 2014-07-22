$(document).ready(function() {

    year = (new Date).getFullYear();

    var tablesorteropts = {
      theme: 'blue',
      widthFixed: false,
      sortList: [[1,1]],
      widgets : ["zebra", "columns", "filter", "resizeable"],
      widgetOptions : {
        columns : [ "primary", "secondary", "tertiary" ],
        columns_thead : true,
        filter_childRows : false,
        filter_columnFilters : true,
        filter_cssFilter : "tablesorter-filter",
        filter_formatter : null,
        filter_functions : {
          3 : {
            "1930s"      : function(e, n, f, i) { return n >= 1930 && n <= 1939; },
            "1940s"      : function(e, n, f, i) { return n >= 1940 && n <= 1949; },
            "1950s"      : function(e, n, f, i) { return n >= 1950 && n <= 1959; },
            "1960s"      : function(e, n, f, i) { return n >= 1960 && n <= 1969; },
            "1970s"      : function(e, n, f, i) { return n >= 1970 && n <= 1979; },
            "1980s"      : function(e, n, f, i) { return n >= 1980 && n <= 1989; },
            "1990s"      : function(e, n, f, i) { return n >= 1990 && n <= 1999; },
            "2000s"      : function(e, n, f, i) { return n >= 2000 && n <= 2009; },
            "2010s"      : function(e, n, f, i) { return n >= 2010 && n <= year; },
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

    
    $("#attendees")
        .tablesorter(tablesorteropts)
        .tablesorterPager({
            container: $("#page-results-pager")
        });
});
