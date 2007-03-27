Util.Image = function()
{
};

var image_i = 0;

// Rescales an image such that its width equals the given width, while
// preserving the image's aspect ratio.
Util.Image.set_width = function(img_elem, width)
{
	var ratio = width / img_elem.width;
	img_elem.height = Math.floor( img_elem.height * ratio );
	img_elem.width = width;
};

// Rescales an image such that its height equals the given height,
// while preserving the image's aspect ratio.
Util.Image.set_height = function(img_elem, height)
{
	var ratio = height / img_elem.height;
	img_elem.width = Math.floor( img_elem.width * ratio );
	img_elem.height = height;
};

// Rescales an image to fit within max_width and max_height, while
// preserving the image's aspect ratio.
Util.Image.set_max_size = function(img_elem, max_width, max_height)
{
	// If only the image's width is greater than max_width, rescale
	// based on width
	if ( img_elem.width > max_width && !(img_elem.height > max_height) )
	{
		Util.Image.set_width(img_elem, max_width);
	}
	// If only the image's height is greater than max_height, rescale
	// based on height
	else if ( img_elem.height > max_height && !(img_elem.width > max_width) )
	{
		Util.Image.set_height(img_elem, max_height);
	}
	// If both are greater than their correspondant, ...
	else if ( img_elem.width > max_width && img_elem.height > max_height )
	{
		// If the difference between the image's width and max_width
		// is greater than the difference between the image's height
		// and max_height, rescale based on width
		if ( img_elem.width - max_width > img_elem.height - max_height )
		{
			Util.Image.set_width(img_elem, max_width);
		}
		// Else, rescale based on height
		else
		{
			Util.Image.set_height(img_elem, max_height);
		}
	}
	// Else (if the image's width and height are both less than their
	// correspondants), do nothing
};

// N.B.: I would not offer my life as pledge that this function works.
// (It's never used in Loki as of now, but made sense to write it up
// while writing the above.)
//
// Rescales an image to fit within max_width and max_height, while
// preserving the image's aspect ratio.
Util.Image.set_min_size = function(img_elem, max_width, max_height)
{
	// If only the image's width is less than max_width, rescale
	// based on width
	if ( img_elem.width < max_width && !(img_elem.height < max_height) )
	{
		Util.Image.set_width(img_elem, max_width);
	}
	// If only the image's height is less than max_height, rescale
	// based on height
	else if ( img_elem.height < max_height && !(img_elem.width < max_width) )
	{
		Util.Image.set_height(img_elem, max_height);
	}
	// If both are less than their correspondant, ...
	else if ( img_elem.width < max_width && img_elem.height < max_height )
	{
		// If the difference between the image's width and max_width
		// is greater than the difference between the image's height
		// and max_height, rescale based on width
		if (  max_width - img_elem.width >  max_height - img_elem.height )
		{
			Util.Image.set_width(img_elem, max_width);
		}
		// Else, rescale based on height
		else
		{
			Util.Image.set_height(img_elem, max_height);
		}
	}
	// Else (if the image's width and height are both greater than their
	// correspondants), do nothing
};



// SET MAX SIZE

// If only the image's width is greater than max_width, rescale based on width

// If only the image's height is greater than max_height, rescale based on height

// If both are greater than their correspondant,
//     if ( image's width - max_width > image's height - max_height ), rescale based on width
//     else, rescale based on height

// SET MIN SIZE

// same as for max size, but change "greater" to "less"

// SET SIZE

// If only the image's width is not equal to max_width, rescale based on width.
// If only the image's height is not equal to max_height, rescale based on height.
// If neither is equal to either,
//     if max_width is greater than max_height, rescale based on max_width;
//     else, rescale based on max_height.
