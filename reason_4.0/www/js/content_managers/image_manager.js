
/**
 * image_manager.js 
 * Displays thumbnail options only once user has uploaded main image
 * Default is for "Default Thumbnail" to be checked; user must uncheck in order to upload custom thumbnail
 * 
 * @author Nick Jones
 * @requires jQuery
 */
$(document).ready(function()
{
	
	/* 
	 *   Hides thumbnail row, sets thumbnail to be default, displays option to upload full sized image and ignore minimum size check for the image.
	 *   Should be triggered by the uploading of a new image 
	*/
	function display_new_image_upload_options()
	{
	    $("#defaultthumbnailRow").removeClass('nonVisibleRow');
		$("#checkbox_default_thumbnail").attr('checked', 'checked');
		$("#donotresizeRow").removeClass('nonVisibleRow');
		$("#ignoreminimgsizecheckRow").removeClass('nonVisibleRow');
		$('#thumbnailRow').addClass('nonVisibleRow');
	}
	/* 
	 *   Default is to hide these two rows 
	 */
	$("#donotresizeRow").addClass('nonVisibleRow');
	$("#ignoreminimgsizecheckRow").addClass('nonVisibleRow');	
	
	// Case where new image is being uploaded
	if($("#imageRow img.representation").attr('src') == '')
	{
		$("#thumbnailRow").addClass('nonVisibleRow');
		$("#donotresizeRow").addClass('nonVisibleRow');
		$("#ignoreminimgsizecheckRow").addClass('nonVisibleRow');
		$('#defaultthumbnailRow').addClass('nonVisibleRow');
		// should be checked to start with
		$("#checkbox_default_thumbnail").attr('checked', 'checked');
	}
	
	/* 
	 *	Whenever a new main image is uploaded, check the "default thumbnail" box,
	 *	hide the actual thumbnail row
	*/
/*
	$("#imageRow .file_upload").bind('uploadSuccess', display_new_image_upload_options);
	// For browsers without Flash
    $('#imageRow .file_upload').change(display_new_image_upload_options);
*/

	$(document).on("backgroundUploadComplete", function(e, elementName) {
		if (elementName == "image") {
			display_new_image_upload_options();
		}
	});
	
	/* 
	 *	If a new image is uploaded, but the user decides to reset the main image,
	 *	display what was displayed beforehand (i.e. show the thumbnail, checkbox unchecked,
	 *	hide the do not resize etc.)
	*/
	
	$('#imageRow .file_upload a.reset').click(function(){
		$("#checkbox_default_thumbnail").attr('checked', false);
		$("#defaultthumbnailRow").removeClass('nonVisibleRow');
		$('#thumbnailRow').removeClass('nonVisibleRow');
		$("#donotresizeRow").addClass('nonVisibleRow');
		$("#ignoreminimgsizecheckRow").addClass('nonVisibleRow');
	});
	
	/*
	 *	When the default thumbnail is checked, no need to display actual thumbnail;
	 *	when it is not checked, show the actual thumbnail and option to give a new one
	*/
	$('#checkbox_default_thumbnail').bind('change', function(){
		var checked = $('#checkbox_default_thumbnail').attr('checked');
		if(checked || checked == 'checked')
			$('#thumbnailRow').addClass('nonVisibleRow');
		else
			$('#thumbnailRow').removeClass('nonVisibleRow');
	});
	
	$("#imageRow .file_upload").bind('uploadSuccess', function() {
        // Automatically say "yes" to regenerating the thumbnail when we pick
        // a new file.
        $('input[name=default_thumbnail]').attr('checked', 'checked');
        
    });
    
    $("#assetRow .file_upload").bind('uploadSuccess', function() {
        // Hide the filename changing row when we pick a new file; it won't
        // have any effect if changed when received along with a new file.
        $('#filenameRow').fadeOut('normal', function() {
            $("#assetRow a.upload").repositionUploadButton();
        });
    });
    
});
