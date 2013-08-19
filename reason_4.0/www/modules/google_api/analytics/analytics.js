$(document).ready(function() {
    
    $( document ).tooltip({
        position: {
            my: 'left-20 bottom', 
            at: 'top' 
        }
    });

    toggle_content_fields();
    $('input[name="content_type"]').change(function(e){
        e.preventDefault();
        toggle_content_fields();
    });

    var tablesorteropts = {
      theme: 'blue',
      widthFixed: true,
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
          5 : {
            "< 25%"      : function(e, n, f, i) { return n < 25; },
            "25% - 50%" : function(e, n, f, i) { return n >= 25 && n <=50; },
            "50% - 75%" : function(e, n, f, i) { return n >= 50 && n <=75; },
            "> 75%"     : function(e, n, f, i) { return n > 75; }
          },
          6 : {
            "< 25%"      : function(e, n, f, i) { return n < 25; },
            "25% - 50%" : function(e, n, f, i) { return n >= 25 && n <=50; },
            "50% - 75%" : function(e, n, f, i) { return n >= 50 && n <=75; },
            "> 75%"     : function(e, n, f, i) { return n > 75; }
          }
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

    
    $("#page-results-table")
        .tablesorter(tablesorteropts)
        .tablesorterPager({
            container: $("#page-results-pager")
        });

    $("#event-results-table")
        .tablesorter(tablesorteropts)
        .tablesorterPager({
            container: $("#event-results-pager")
        });

    $("#faq-results-table")
        .tablesorter(tablesorteropts)
        .tablesorterPager({
            container: $("#faq-results-pager")
        });

    $("#news-results-table")
        .tablesorter(tablesorteropts)
        .tablesorterPager({
            container: $("#news-results-pager")
        });

    $("#policy-results-table")
        .tablesorter(tablesorteropts)
        .tablesorterPager({
            container: $("#policy-results-pager")
        });
});

function toggle_content_fields() {
    switch($('input[name="content_type"]:checked').val())
        {
          case '3317': //page
            $('#urlgroupRow').show(500);
            $('#eventsRow').hide();
            $('#faqRow').hide();
            $('#newsRow').hide();
            $('#policiesRow').hide();
            break;
          case '31512': //event
            $('#urlgroupRow').hide();
            $('#eventsRow').show(500);
            $('#faqRow').hide();
            $('#newsRow').hide();
            $('#policiesRow').hide();
            break;
          case '60241': //faq
            $('#urlgroupRow').hide();
            $('#eventsRow').hide();
            $('#faqRow').show(500);
            $('#newsRow').hide();
            $('#policiesRow').hide();
            break;
          case '88': //news
            $('#urlgroupRow').hide();
            $('#eventsRow').hide();
            $('#faqRow').hide();
            $('#newsRow').show(500);
            $('#policiesRow').hide();
            break;
          case '5890': //policies
            $('#urlgroupRow').hide();
            $('#eventsRow').hide();
            $('#faqRow').hide();
            $('#newsRow').hide();
            $('#policiesRow').show(500);
            break;
          default:
            $('#urlgroupRow').show(500);
            $('#eventsRow').hide();
            $('#faqRow').hide();
            $('#newsRow').hide();
            $('#policiesRow').hide();
        }
}
