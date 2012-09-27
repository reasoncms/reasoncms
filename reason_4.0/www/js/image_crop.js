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
		
		setTimeout(function() {
			// Figure out the size of the image. If the original proportions are set on the
			// form, use those, otherwise, use the size of the displayed image.
			if ($('input[name="_reason_upload_orig_h"]').val()) {
				true_h = $('input[name="_reason_upload_orig_h"]').val();
			} else {
				true_h = $('img.representation').height();
			}
			if ($('input[name="_reason_upload_orig_w"]').val()) {
				true_w = $('input[name="_reason_upload_orig_w"]').val();
			} else {
				true_w = $('img.representation').width();
			}

			var ratio = parseFloat($('input[name="_reason_upload_crop_ratio"]').val());
			
			$('img.representation').Jcrop({
				aspectRatio: ratio,
				onSelect: updateCoords,
				bgOpacity: .4,
				trueSize: [true_w, true_h]
			}, function(){
				jcrop_api = this;	
			});

			// Preselect a cropped region if called for.
			if ($('input[name="_reason_upload_crop_preselect"]').val()) {
				
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
	
	function updateCoords(c)
	{
		$('input[name="_reason_upload_crop_x"]').val(c.x);
		$('input[name="_reason_upload_crop_y"]').val(c.y);
		$('input[name="_reason_upload_crop_w"]').val(c.w);
		$('input[name="_reason_upload_crop_h"]').val(c.h);
	};

	function checkCoords()
	{
		if ($('input[name="_reason_upload_crop_required"]').val()) {
			if (parseInt($('input[name="_reason_upload_crop_w"]').val())) return true;
			alert('Please select a crop region before submitting your image.');
			return false;
		}
	};	
});

