// custom tablesorter script for sports roster pages

// tablesorter.js is used to sort tables. See http://tablesorter.com/
// add parser through the tablesorter addParser method to sort class year
$.tablesorter.addParser({
    // set a unique id 
    id: 'athlete_class_year',
    is: function(s) {
        // return false so this parser is not auto detected 
        return false;
    },
    format: function(s) {
        // format your data for normalization 
        return s.replace(/Fr/,0).replace(/So/,1).replace(/Jr/,2).replace(/Sr/,3).replace(/Gr/,4);
    },
    // set type, either numeric or text 
    type: 'numeric'
});
// add parser through the tablesorter addParser method to sort height 
// need to insert 0 before single digit inch measurements (e.g. 9 -> 09)
$.tablesorter.addParser({
    // set a unique id 
    id: 'athlete_height',
    is: function(s) {
        // return false so this parser is not auto detected 
        return false;
    },
    format: function(s) {
        // format your data for normalization 
        return s.replace(/(\d+\'\s)(\d\")/, "$10$2");
    },
    // set type, either numeric or text 
    type: 'text'
});


$(document).ready(function() {

    $("table").tablesorter({ 
        headers: { 
            4: { 
                sorter:'athlete_height' 
            },
            6: {
            	sorter:'athlete_class_year'
            }
        } 
    });    
		
});