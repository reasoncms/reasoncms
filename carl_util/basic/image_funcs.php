<?php

/**
 * Functions for manipulating images.
 * @package carl_util
 * @subpackage basic
 * @author Eric Naeseth <enaeseth+reason@gmail.com>
 */

require_once 'filesystem.php';
require_once 'misc.php';

/**
 * Scale an image to meet a maximum width and height.
 * 
 * The image will be resized in place; to avoid this, make a {@link copy} of
 * the original image first, and resize the copy.
 *
 * Raises a {@link WARNING} if no image resize method is available.
 * 
 * @param string $path filesystem path to the image to be resized
 * @param int $width desired width
 * @param int $height desired height
 * @param boolean $sharpen if true, the resized image will be sharpened
 * @return boolean true if the image was resized successfully; false if not
 */
function resize_image($path, $width, $height, $sharpen=true)
{
    if (!is_file($path) || !is_readable($path)) {
        trigger_error('cannot resize image; no file exists at the given path '.
            '('.var_export($path, true).')', WARNING);
        return false;
    }

    $perms = substr(sprintf('%o', fileperms($path)), -4);
    if (imagemagick_available()) {
        $result = _imagemagick_resize($path, $width, $height, $sharpen);
    } else if (function_exists('imagecreatetruecolor')) {
        $result = _gd_resize($path, $width, $height, $sharpen);
    } else {
	trigger_error('neither ImageMagick nor GD are available; cannot '.
            'resize image', WARNING);
        return false;
    }

    // Prevent the transformation from changing the file permissions.
    clearstatcache();
    $newperms = substr(sprintf('%o', fileperms($path)), -4);
    if ($perms != $newperms) @chmod($path, $perms);
    return $result;
}

/**
 * Checks to see if ImageMagick is available on the system.
 *
 * This function requires the {@link IMAGEMAGICK_PATH} constant to have been
 * defined; it only checks for the command-line ImageMagick utilities, not any
 * of the library's PHP bindings.
 *
 * @param string $utility the specific ImageMagick utility to check for
 * @return boolean true if the utility is available, false if otherwise
 * @link http://www.imagemagick.org/ ImageMagick
 */
function imagemagick_available($utility='mogrify')
{
    static $cache = array();
    
    if (!isset($cache[$utility])) {
        $file = (server_is_windows()) ? "$utility.exe" : $utility;
        
        if (!@constant('IMAGEMAGICK_PATH')) {
            $result = false;
        } else if (!is_executable(IMAGEMAGICK_PATH.$file)) {
            $result = false;
        } else {
            $result = true;
        }
        
        $cache[$utility] = $result;
    }
    
    return $cache[$utility];
}

/**
 * @access private
 */
function _imagemagick_resize($path, $width, $height, $sharpen)
{
    if (defined(IMAGEMAGICK_PATH)) {
		$exec = (substr(IMAGEMAGICK_PATH, -1) == DIRECTORY_SEPARATOR)
			? IMAGEMAGICK_PATH.'mogrify'
			: IMAGEMAGICK_PATH.DIRECTORY_SEPARATOR."mogrify";
	} else {
		$exec = "mogrify";
	}
	
	$args = array(
		$exec,
		'-geometry',
		"{$width}x{$height}",
	);
	if ($sharpen)
	    $args = array_merge($args, array('-sharpen', '1'));
	$args[] = escapeshellarg($path);
	
	$output = array();
	$exit_status = -1;
	exec(implode(' ', $args), $output, $exit_status);
	if ($exit_status != 0) {
		trigger_error('image resize failed: '.implode('; ', $output), WARNING);
		return false;
	}
	
	return true;
}

/**
 * @access private
 */
function _gd_resize($path, $width, $height, $sharpen)
{
    static $image_types = array(
        1 => 'gif',
        2 => 'jpeg',
        3 => 'png'
    );
    
    $info = getimagesize($path);
    if (!$info || !isset($image_types[$info[2]])) {
        trigger_error('image resize failed: image '.var_export($path, true).
            ' is not an image or is an image in an unsupported format');
    }
    list($src_width, $src_height, $src_type) = $info;
    $type_ext = $image_types[$src_type];
    
    if ($src_width > $width || $src_height > $height) {
        $ratio = ((float) $src_width) / $src_height;
        if (((float) $width) / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }
    }
    
    $create = "imagecreatefrom$type_ext";
    $save = "image$type_ext";
    
    // Resample
	$image_dest = imagecreatetruecolor($width, $height);
	$image_src = $create($path);
	imagecopyresampled($image_dest, $image_src, 0, 0, 0, 0, $width, $height,
	    $src_width, $src_height);
	imagedestroy($image_src);
	
	// Sharpen
	if ($sharpen) {
	    $image_dest = sharpen_image($image_dest, 80, 0.5, 3);
	}
	
	// Output
	$success = $save($image_dest, $path);
	imagedestroy($image_dest);
	return $success;
}

/**
 * Gets the components of an RGB color.
 * @param int $color an RGB color
 * @return array [red, green, blue]
 */
function get_color_components($color)
{
    $red = (($color >> 16) & 0xFF);
    $green = (($color >> 8) & 0xFF);
    $blue = ($color & 0xFF);
    return array($red, $green, $blue);
}


/**
 * Sharpens a GD image using an unsharp mask.
 * The given image will be sharpened in-place; this function returns the same
 * image resource as it was passed.
 * @param resource $img a GD image resource
 * @param int $amount (typically 50 - 200)
 * @param int $radius (typically 0.5 - 1)
 * @param int $threshold (typically 0 - 5)
 * @return resource the sharpened image as a GD image resource
 * @author Torsteon Hønsi <thoensi@netcom.no>
 * @version 2.1
 * @link http://en.wikipedia.org/wiki/Unsharp_masking the technique used
 * @link http://vikjavev.no/computing/ump.php the source of this code
 */
function sharpen_image($img, $amount, $radius, $threshold)
{
    // Attempt to calibrate the parameters to Photoshop:
    $amount = min($amount, 500) * 0.016;
    $radius = min($radius, 50) * 2;
    $threshold = min($threshold, 255);

    $radius = abs(round($radius));
    if ($radius == 0) {
        trigger_error('unsharp radius is 0; not performing any sharpening',
            WARNING);
        return $img;
    }

    list($width, $height) = array(imagesx($img), imagesy($img));
    $canvas = imagecreatetruecolor($width, $height);
    $blur = imagecreatetruecolor($width, $height);

    static $gaussian_blur_matrix = array(
        array(1, 2, 1),
        array(2, 4, 2),
        array(1, 2, 1)
    );
    if (function_exists('imageconvolution')) {
        imagecopy($blur, $img, 0, 0, 0, 0, $width, $height);
        imageconvolution($blur, $gaussian_blur_matrix, 16, 0);
    } else {
        // Move copes of the image around one pixel at a time and merge them
        // with weight according to the matrix. The same matrix is simply
        // repeated for higher radii.
        for ($i = 0; $i < $radius; $i++) {
            imagecopy($blur, $img, 0, 0, 1, 0, $w - 1, $h); // left
            imagecopymerge($blur, $img, 1, 0, 0, 0, $w, $h, 50); // right
            imagecopymerge($blur, $img, 0, 0, 0, 0, $w, $h, 50); // center
            imagecopy($canvas, $blur, 0, 0, 0, 0, $w, $h);

            imagecopymerge($blur, $canvas, 0, 0, 0, 1, $w, $h - 1,
                33.33333); // up
            imagecopymerge($blur, $canvas, 0, 1, 0, 0, $w, $h, 25); // down
        }
    }

    if ($threshold > 0) {
        for ($x = 0; $x < $width - 1; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($img, $x, $y);
                list($rOrig, $gOrig, $bOrig) = get_color_components($rgb);

                $rgb = imagecolorat($blur, $x, $y);
                list($rBlur, $gBlur, $bBlur) = get_color_components($rgb);

                // When the masked pixels differ less from the original than
                // the threshold specifies, they are set to their original
                // value.
                $rNew = (abs($rOrig - $rBlur) >= $threshold)
                    ? _bound(($amount * ($rOrig - $rBlur)) + $rOrig, 0, 255)
                    : $rOrig;
                $gNew = (abs($gOrig - $gBlur) >= $threshold)
                    ? _bound(($amount * ($gOrig - $gBlur)) + $gOrig, 0, 255)
                    : $gOrig;
                $bNew = (abs($bOrig - $bBlur) >= $threshold)
                    ? _bound(($amount * ($bOrig - $bBlur)) + $bOrig, 0, 255)
                    : $bOrig;

                if ($rOrig != $rNew || $gOrig != $gNew || $bOrig != $bNew) {
                    $pixCol = imagecolorallocate($img, $rNew, $gNew, $bNew);
                    imagesetpixel($img, $x, $y, $pixCol);
                }
            }
        }
    } else {
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($img, $x, $y);
                list($rOrig, $gOrig, $bOrig) = get_color_components($rgb);

                $rgb = imagecolorat($blur, $x, $y);
                list($rBlur, $gBlur, $bBlur) = get_color_components($rgb);

                $rNew = _bound(($amount * ($rOrig - $rBlur)) + $rOrig, 0, 255);
                $gNew = _bound(($amount * ($gOrig - $gBlur)) + $gOrig, 0, 255);
                $bNew = _bound(($amount * ($bOrig - $bBlur)) + $bOrig, 0, 255);

                $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
                imagesetpixel($img, $x, $y, $rgbNew);
            }
        }
    }

    imagedestroy($canvas);
    imagedestroy($blur);

    return $img;
}

/**
 * Restricts $val such that $min ≤ $val ≤ $max.
 * @access private
 */
function _bound($val, $min, $max)
{
    return max($min, min($val, $max));
}
