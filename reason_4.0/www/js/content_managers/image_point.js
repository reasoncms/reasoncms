// Selectors
var mainImage = 'div#imageItem img.representation';
var cropStyleCenter = '#radio_crop_style_0'
var cropStyleCustom = '#radio_crop_style_1';
var cropStyleInput = 'input[type=radio][name=crop_style]';
var focusInputX = 'input#focal_point_xElement';
var focusInputY = 'input#focal_point_yElement';
var noImageDiv = 'div#noImageMessage';

var focusFormItem = 'div#focalpointItem';
var focusContainer = focusFormItem + ' div#focalPointContainer';
var focusSelector = focusContainer + ' div#focalPointSelector';
var focusClickArea = focusSelector + ' div#focalPointClickArea';
var focusImage = focusClickArea + ' img#focalPointImage';
var focusIcon = focusClickArea + ' img#focalPointIcon';
var cropSamplesDiv = focusContainer + ' div#cropSamples';
var croppedImageDiv = cropSamplesDiv + ' div.croppedImageContainer';
var croppedImage = croppedImageDiv + ' img.croppedImage';

// Constants
var SELECTOR_SCALE = 0.5;
var SAMPLE_SCALE = 0.33;

// Global variables
var imageSrc = '';

/**
 * Show or hide the focus image based on what crop style is selected.
 */
function showHideFocus() {
    if($(cropStyleCustom).is(':checked')) {
        $(focusFormItem).show();
	} else {
        $(focusFormItem).hide();
	}
}

/**
 * Set the focus image to be the same as the main image.
 */
function updateImage() {
    var width = $(mainImage).width();
    var height = $(mainImage).height();
    
    // Set focus image 
    $(focusSelector).width(width * SELECTOR_SCALE);
    $(focusImage).attr('src', $(mainImage).prop('src'));
    $(focusImage).css({'width' : width * SELECTOR_SCALE, 'height': height * SELECTOR_SCALE});
    
    // Set crop sample images
    $(croppedImageDiv).each(function(i) {
        var aspectRatio = parseFloat(this.id); 
        if (width / height > aspectRatio) {
            $(this).width(height * aspectRatio * SAMPLE_SCALE);
            $(this).height(height * SAMPLE_SCALE);
        } else {
            $(this).width(width * SAMPLE_SCALE);
            $(this).height(width / aspectRatio * SAMPLE_SCALE);
        }
        
        $('img', this).attr('src', $(mainImage).prop('src'));
        $('img', this).css({'width' : width * SAMPLE_SCALE, 'height': height * SAMPLE_SCALE});
    });
}

/**
 * Position the focus icon according to the fpx, fpy values in the form.
 */
function positionFocusIcon() { 
    var newLeft = $(focusInputX).val() * 100 + '%';
    var newTop = $(focusInputY).val() * 100 + '%';

    $(focusIcon).css({'left': newLeft, 'top': newTop});
}

/**
 * Update all crop samples based on focal point
 */
function updateCropSamples() {
    $(croppedImageDiv).each(function(i) {
        var aspectRatio = parseFloat(this.id); 
        $('img', this).css(getCropCSS(aspectRatio));
    });
}

/**
 * Calculate the correct amount to shift a crop sample in its container
 * so that it is centered on the focal point.
 * @param  aspectRatio of the crop sample
 * @return css to appropriately position the image
 */
function getCropCSS(aspectRatio) {
    var width = $(mainImage).width() * SAMPLE_SCALE;
    var height = $(mainImage).height() * SAMPLE_SCALE;
            
    var fpX = $(focusInputX).val();
    var fpY = $(focusInputY).val()
    
    // Image too wide, crop off sides
    if (width / height > aspectRatio) {
        var cropWidth = height * aspectRatio;
        
        var shift = fpX * width - cropWidth / 2;
        if (shift < 0) {
            shift = 0;
        }
        
        var overflow = (cropWidth / 2 + fpX * width) - width;
        if (overflow < 0) {
            overflow = 0;
        }
                
        return {'margin-left': (overflow - shift) + 'px', 'margin-top': 0};
    // Image too tall, crop off top/bottom    
    } else {
        var cropHeight = width / aspectRatio;
        
        var shift = fpY * height - cropHeight / 2;
        if (shift < 0) {
            shift = 0;
        }
        
        var overflow = (cropHeight / 2 + fpY * height) - height;
        if (overflow < 0) {
            overflow = 0;
        }
        
        return {'margin-top': (overflow - shift) + 'px', 'margin-left': 0};
    }
}

$(document).ready(function() {
    // Initial set up
    showHideFocus();
    $(noImageDiv).show();
    $(focusContainer).hide();
    
    // Change focus image when main image is reloaded and position the focus icon when the focus image is loaded
    $(focusImage).on('load', positionFocusIcon);
    $(focusImage).on('load', updateCropSamples);
    $(mainImage).on('load', function() {        
        $(focusContainer).show();
        $(noImageDiv).hide();
        updateImage();
        
        // Reset to center crop when new image is uploaded
        if (imageSrc !== '' && this.src != imageSrc) {
            $(focusFormItem).hide();
            $(cropStyleCenter).prop('checked', true);
        }
        imageSrc = this.src;
    }).each(function() {
        if (this.width > 0 && this.height > 0){
            $(this).load();
        }
    });
    
    // Show/hide focus image when crop style input changes
    $(cropStyleInput).change(showHideFocus);

    // Calculate new fpx & fpy, update icon and form values on focus image click
    $(focusImage).click(function(e) {
        var offset = $(this).offset();
        var focusIconRadius = $(focusIcon).width() / 2;

        var focalPointX = (e.pageX - offset.left) / this.width;
        var focalPointY = (e.pageY - offset.top) / this.height;
                
        // Constrain fpx and fpy (sometimes negative values can occur for clicks close to an edge)
        focalPointX = Math.max(Math.min(focalPointX, 1), 0);
        focalPointY = Math.max(Math.min(focalPointY, 1), 0);
        
        // Set the fpx, fpy values in the form
        $(focusInputX).val(focalPointX);
        $(focusInputY).val(focalPointY);    
        
        // Place the icon where the user clicked and update crop samples
        positionFocusIcon(); 
        updateCropSamples();   
    });
});


