$(document).ready( function() {

    $.each( $(".datepicker"), function() {
        yearElementID   = $(this).attr('id');
        dayElementID    = yearElementID + "-dd";
        monthElementID  = yearElementID + "-mm";

        dateObj = [];
        dateObj[yearElementID]   = "%Y";
        dateObj[dayElementID]    = "%d";
        dateObj[monthElementID]  = "%m";
        opts = {
                formElements:       dateObj,
                statusFormat:       "%l, %d%S %F %Y",
                fillGrid:           true,
                constrainSelection: false
        };
        datePickerController.createDatePicker(opts);
    });
});
