// Image cropping for profiles module image editing
// @author Mark Heiman
// @requires JCrop

var jcrop_api;

$(document).ready(function()
{
	/* Attach the Jcrop function to our uploaded image after the swfupload triggers the
	   uploadSuccess event. We delay this slightly because the script that includes
	   the image in the page has a 600ms delay after receiving the event, and we want to
	   make sure the image is in place before we attach to it.
	   */	
	$("div.file_upload").bind('uploadSuccess', function(event, file, server_data) {
		// If jcrop is already loaded, destroy it so that we can start fresh
		if (jcrop_api) { jcrop_api.destroy(); }

		var true_w;
		var true_h;
		var container = $(this).parent();
		
		setTimeout(function() {
			// Figure out the size of the image. If the original proportions are set on the
			// form, use those, otherwise, use the size of the displayed image.
			if ($('input[name="_reason_upload_orig_h"]').val(), container) {
				true_h = $('input[name="_reason_upload_orig_h"]', container).val();
			} else {
				true_h = $('img.representation', container).height();
			}
			if ($('input[name="_reason_upload_orig_w"]', container).val()) {
				true_w = $('input[name="_reason_upload_orig_w"]', container).val();
			} else {
				true_w = $('img.representation', container).width();
			}

			var ratio = parseFloat($('input[name="_reason_upload_crop_ratio"]', container).val());
			
			$('img.representation', container).Jcrop({
				aspectRatio: ratio,
				onSelect: updateCoords,
				onRelease: clearCoords,
				bgOpacity: .4,
				trueSize: [true_w, true_h]
			}, function(){
				jcrop_api = this;	
			});

			// Preselect a cropped region if called for.
			if ($('input[name="_reason_upload_crop_preselect"]', container).val()) {
				
				// If we have a non-zero ratio, fit the crop shape to the image shape.
				if (ratio) {
					if (true_w/true_h <= ratio) {
						jcrop_api.setSelect([0,0,true_w,true_h/ratio]);				
					} else {
						jcrop_api.setSelect([0,0,true_w*ratio,true_h]);
					}
				// otherwise, crop the whole thing.
				} else {
					jcrop_api.setSelect([0,0,true_w,true_h]);
				}
			}
		}, 1200);
	});

	/* Called when the crop region is changed. Updates the hidden crop parameter elements
	   so that we can pass the cropping details back to the server. */
	function updateCoords(c)
	{
		// Find the containing element so we can update the correct set of
		// crop elements
		var container = this.ui.holder.parent().parent();
		
		$('input[name="_reason_upload_crop_x"]', container).val(c.x);
		$('input[name="_reason_upload_crop_y"]', container).val(c.y);
		$('input[name="_reason_upload_crop_w"]', container).val(c.w);
		$('input[name="_reason_upload_crop_h"]', container).val(c.h);
	};

	/* Called when the crop region is canceled. Updates the hidden crop parameter elements
	   to zero so we can tell that no crop is active. (You'd think we could just call
	   updateCoords() instead of this, but that doesn't work.) */
	function clearCoords()
	{
		// Find the containing element so we can update the correct set of
		// crop elements
		var container = this.ui.holder.parent().parent();
		
		$('input[name="_reason_upload_crop_x"]', container).val(0);
		$('input[name="_reason_upload_crop_y"]', container).val(0);
		$('input[name="_reason_upload_crop_w"]', container).val(0);
		$('input[name="_reason_upload_crop_h"]', container).val(0);
	}
	
	/* Called when the form is submitted. If image cropping is active, and cropping is 
	   required by the form, it won't allow submit without a crop region defined. */
	function checkCoords(form)
	{		
		if ($('div.jcrop-holder', form).length && $('input[name="_reason_upload_crop_required"]', form).val()) {
			if (parseInt($('input[name="_reason_upload_crop_w"]', form).val())) return true;
			alert('Please select a crop region before submitting your image.');
			return false;
		}
		return true;
	};
	
	$('div.file_upload').each(function(){
		$(this).closest('form').submit(function(){
			return checkCoords(this);
		});
	});
});

