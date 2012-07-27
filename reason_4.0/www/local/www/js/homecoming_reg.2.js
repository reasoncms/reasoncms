
$(document).ready(function() {

    //    	$("tr#id03Tl57Y680Row").hide();   //Guest Name
    //    	$(".words").hide();   //Guest Name

    toggle_receptions();
    // Add onclick handler to radiobuttons with name
    $("input[name='class_year']").change(function(){
        toggle_receptions();
    });
});

function toggle_receptions(){
    //Add select handler to Class Year select element
    //if year selected is a 5 - 75 year reunion, show options
    $('#class_yearElement').change(function() {
        var date = new Date();
        var year = date.getFullYear();
        var class_year = $("select[name='class_year']").val();

        switch (class_year) {
            case ((year - parseInt(class_year)) == 75 || (year - parseInt(class_year)) == 70 || (year - parseInt(class_year)) == 65
            || (year - parseInt(class_year)) == 60 || (year - parseInt(class_year)) == 55 || (year - parseInt(class_year)) == 50) :
                $("#luncheonheaderRow").show();
                $("#attendluncheonRow").show();
                break;
            case ((year - parseInt(class_year)) == 55 || (year - parseInt(class_year)) == 50 || (year - parseInt(class_year)) == 45
            || (year - parseInt(class_year)) == 40 || (year - parseInt(class_year)) == 35 || (year - parseInt(class_year)) == 30
            || (year - parseInt(class_year)) == 25) :
                $("#luncheonheaderRow").show();
                $("#dinnerheaderRow").show();
                $("#attenddinner50to25Row").show();
                break;
            case ((year - parseInt(class_year)) == 20 || (year - parseInt(class_year)) == 15 || (year - parseInt(class_year)) == 10) :
                $("#luncheonheaderRow").show();
                $("#dinnerheaderRow").show();
                $("#attenddinner20to10Row").show();
            case ((year - parseInt(class_year)) == 5) :
                $("#dinnerheaderRow").show();
                $("#attenddinner5Row").show();
                break;
            default :
                $("#luncheonheaderRow").hide();
                $("#dinnerheaderRow").hide();

        }
    });
}
