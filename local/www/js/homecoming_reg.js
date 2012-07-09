$(document).ready(function(){

    // Highlight Description Rows
    //    $(".words h3").css("color", "#123074");
    
    //Hide Guest Class row
    if (!$("#radio_attended_luther_0").is(":checked")){
        $("#guestclassRow").css("display", "none");
    }
    
     // Guests, Vegeterians
    // hide fields
        
    $("#festivalvegetarianRow").hide();
    $("#festivalguestsnamesRow").hide();
    $("#vegetarianguestsRow").hide();
    $("#seatingpreferenceRow").hide();
    

    $('#attend_programElement').change(function() {
        if ($("select[name='attend_program']").val() >= 1){
            $("#festivalvegetarianRow").show();
            $("#seatingpreferenceRow").show();
            if ($("select[name='attend_program']").val() > 1){
                $("#festivalguestsnamesRow").show();
                $("#vegetarianguestsRow").show();
            }  
        }
        else {
            $("#festivalguestsnamesRow").hide();
            $("#vegetarianguestsRow").hide();
            $("#festivalguestsnamesRow").hide();
            $("#seatingpreferenceRow").hide();

        }
    } )
        
        
    //Hide Reservation info
    //Luncheon Header Row
    if (!$("#attend_luncheonElement").val()){
        $("#luncheonheaderRow").hide();
        // $("#luncheonheaderRow").css("display", "none");
        //Tickets for Luncheon Row
        $("#attendluncheonRow").css("display", "none");
        $("#attendluncheonRow").hide();
    }
    //Dinner Header Row
    $("#dinnerheaderRow").hide();

    //Dinner Tickets
    if (!$("#attend_dinner_50_to_25Element").val()){
        $("#attenddinner50to25Row").css("display", "none");
    }
    if (!$("#attend_dinner_20_to_10Element").val()){
        $("#attenddinner20to10Row").css("display", "none");
    }
    if (!$("#attend_dinner_5Element").val()){
        $("#attenddinner5Row").css("display", "none");
    }

    //Parade Row
    if (!$("radio[name='ride_in_parade']").val()){
        $("#rideinparadeRow").css("display", "none");
    }
    // class 1961 reception
    if (!$("radio[name='attend_1961_reception']").val()){
       $("#attend50threceptionRow").css("display", "none");
    }

    // Add onclick handler to radiobuttons with name 'attended_luther' from Guest Info
    $("input[name='attended_luther']").change(function()
    {
        // If "Yes" is checked
        if ($("#radio_attended_luther_0").is(":checked")){
            $("#guestclassRow").show();
        }
        // If "No" is checked
        if ($("#radio_attended_luther_1").is(":checked")){
            $("#guestclassRow").hide();
            $("#guest_classElement").val('').attr('selected', 'selected');
        }
    })
   
    $("#vegetarianguestsnamesRow").hide();
    //veggie guests hide
    $("input[name='vegetarian_guests']").change(function()
    {
        // If "Yes" is checked
        if ($("#radio_vegetarian_guests_0").is(":checked")){
            $("#vegetarianguestsnamesRow").show();
        }else{
            $("#vegetarianguestsnamesRow").hide();
        }
        
        
    })


    //Add select handler to Class Year select element
    //if year selected is a 5 - 75 year reunion, show options
    $('#class_yearElement').change(function() {
        var date = new Date();
        var year = date.getFullYear();
        var class_year = $("select[name='class_year']").val();

        //if 75 - 50 show Luncheon header and tickets
        if ((year - parseInt(class_year)) == 75 || (year - parseInt(class_year)) == 70 || (year - parseInt(class_year)) == 65
            || (year - parseInt(class_year)) == 60 || (year - parseInt(class_year)) == 55 || (year - parseInt(class_year)) == 50){
       
            $("#attendluncheonRow").show();
        }else{
     
            $("#attendluncheonRow").hide();
            $("#attend_luncheonElement").val('').attr('selected', 'selected');
        }

        if ((year - parseInt(class_year)) == 55 || (year - parseInt(class_year)) == 50 || (year - parseInt(class_year)) == 45
            || (year - parseInt(class_year)) == 40 || (year - parseInt(class_year)) == 35 || (year - parseInt(class_year)) == 30
            || (year - parseInt(class_year)) == 25) {
                
        
            $("#dinnerheaderRow").show();
            $("#attenddinner50to25Row").show();
        }else{
         
            $("#dinnerheaderRow").hide();
            $("#attenddinner50to25Row").hide();
            $("#attend_dinner_50_to_25Element").val('selected', 'selected');
        }
       
        // if 20 - 10  show Reception header and tickets
        if ((year - parseInt(class_year)) == 20 || (year - parseInt(class_year)) == 15 || (year - parseInt(class_year)) == 10) {
            //            alert('1991');
 
            $("#dinnerheaderRow").show();
            $("#attenddinner20to10Row").show();
        }else{
      
            $("#dinnerheaderRow").hide();
            $("#attenddinner20to10Row").hide();
            $("#attend_dinner_20_to_10Element").val('').attr('selected', 'selected');
        }

        // if 5 year show 5 Year Reception header and tickets
        if ((year - parseInt(class_year)) == 5) {
            $("#dinnerheaderRow").show();
            $("#attenddinner5Row").show();
        }else{
            $("#dinnerheaderRow").hide();
            $("#attenddinner5Row").hide();
            $("#attend_dinner_5Element").val('').attr('selected', 'selected');
        }
        
        if (((year - parseInt(class_year))%5) == 0) {
            $("#luncheonheaderRow").show();
        }else{
            $("#luncheonheaderRow").hide();
        }
         
        if ((year - parseInt(class_year)) == 50) {
            $("#rideinparadeRow").show();
            $("#attend50threceptionRow").show();

        }else{
             
            $("#rideinparadeRow").hide();
            $("#attend50threceptionRow").hide();

        }
       
    });
   
})