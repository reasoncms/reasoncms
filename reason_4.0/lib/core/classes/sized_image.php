<?php
/**
 * sized_image.php
 *
 * This file contains the reasonSizedImage class, which creates sized image files (if needed) and returns a filename string for them.
 *
 * @package reason
 * @subpackage classes
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'basic/image_funcs.php');
reason_include_once('function_libraries/image_tools.php');

/**
 * reasonSizedImage create sized image files (if needed) and returns a filename string for them.
 *
 * If you set only a width or height, the other dimension will be automatically picked based upon the aspect
 * ratio of the original image.
 *
 * Typical use case:
 *
 * $rsi = new reasonSizedImage();
 * $rsi->set_id(23423);
 * $rsi->set_width(400);
 * $rsi->set_crop_style('fill'); // or fit, crop_x, or crop_y
 * $image_url = $rsi->get_url();
 *
 * Understanding the crop styles:
 *
 * The image will have an aspect ratio (the ratio between its width and height). When both a width
 * and a height are provided, then that will describe a box which may have a different aspect ratio.
 * Crop styles provide different ways of handling this situation.
 *
 * fill: the resulting image will fill the box. The class will crop horizontally (if image
 * has a wider aspect ratio) or vertically (if the image has a taller aspect ratio) to ensure this.
 *
 * fit: The resulting image will fit entirely inside the box, and the class will not crop the image 
 * at all. If the image has a wider aspect ratio than the box, it will be proportionally sized so 
 * the width equals the box width. If the image has a taller aspect ratio than the box, it will be 
 * proportionally sized so the height equals the box height.
 *
 * crop_x: The vertical dimension will be proportionally sized to the height of the box. If the
 * aspect ratio of the image is greater (wider) than that of the box, the image will be cropped 
 * horizontally. If the aspect ratio of the image is less than that of the box, the image will not
 * be cropped and will simply be resized.
 *
 * crop_y: The horizontal dimension will be proportionally sized to the width of the box. If the
 * aspect ratio of the image is less (narrower) than that of the box, the image will be cropped 
 * vertically. If the aspect ratio of the image is greater than that of the box, the image will not
 * be cropped and will simply be resized.
 *
 * @todo what should we do if we want to resize to a larger size than the original
 * @todo Add a quality setting so code can choose between "fast" and "good" --
 * the main difference being that "fast" will resize from the full-sized image
 * and "good" will resize from the original image (the current behavior).
 * @todo Use the last modified time of the image on the filesystem instead of
 * the last modified time of the entity (this will reduce unneccessary
 * image regeneration when an image's metadata changes).
 *
 * @author Nathan White
 */
class reasonSizedImage
{
	/**
	 * @var int reason image id
	 */
	var $id;
	
	/**
	 * @var int width in pixels
	 */
	var $width;
	
	/**
	 * @var int height in pixels
	 */
	var $height;
	
	/**
	 * @var boolean if the image has a full-sized original associated with it
	 */
	var $orig_exists;

	/**
	 * @var string
	 */
	var $crop_style = "fill"; // fill, fit, crop_x, or crop_y
	
	/**
	* @var boolean
	*/
	var $allow_enlarge = true;
	
	/**
	 * $var array supported crop styles
	 */
	var $available_crop_styles = array('fill', 'fit','crop_x','crop_y');
	
	
	/**
	 * File system path to the directory where images are stored - apache needs write and read access to this directory, and it should probably be web accessible.
	 *
	 * You should include a trailing slash on the $image_dir - ie /tmp/
	 *
	 * @var string 
	 */
	var $image_dir = REASON_SIZED_IMAGE_DIR;
	
	/**
	 * Path from the server root directory to the image - used to construct URLs
	 *
	 * Usually dynamically determined by matching the WEB_PATH with the image_dir - if your setup is different specify this manually.
	 *
	 * @var string
	 */
	var $image_dir_web_path = REASON_SIZED_IMAGE_DIR_WEB_PATH;	
	
	/**
	* Boolean to determine if blitting will be performed on this resized image
	* @var Boolean
	*/
	var $do_blit=false;
	
	/**
	* parameters to control the blit.
	* (see the blit_image function in image_funcs.php for more details)
	* @var array
	*/
	var $blit_options=array();
	
	/**
	* server path and filename to blit onto image
	* @var string
	*/
	var $blit_file=null;
	
	/**
	* when true, setters return an error. set to true by the make() function.
	* meant to avoid setting width, height, crop, allow enlarge after resized image has already
	* been created and expecting a changed image.
	* @var boolean
	*/
	var $locked=false;
	
	protected $use_absolute_urls = false;
	
	/** public methods **/
	
	/**
	* @access public
	*/
	function set_blit($file,$options=array())
	{
		$this->blit_file=$file;
		$this->blit_options=$options;
		$this->do_blit=true;
	}
	
	/**
	 * @access public
	 * @return boolean
	 */
	function exists()
	{
		if ($this->_verify_user_params())
		{
			$path = $this->_get_path();
			return file_exists($path);
		}
		else
		{
			trigger_error('Cannot check for existence - make sure you have specified a valid entity and at least one dimension.');
			return false;
		}
	}
	
	/**
	 * @access public
	 * @return string
	 */
	function get_url()
	{
		if ($this->_verify_user_params())
		{
			if (!$this->exists()) $this->_make();
			$this->locked = true;
			return $this->_get_url();
		}
		else
		{
			trigger_error('Cannot get URL - make sure you have specified a valid entity and at least one dimension.');
			return false;
		}
	}
	
	/**
	* @access public
	* @return array containing relative url to image and image description
	*/
	function get_url_and_alt()
	{
		if ($this->_verify_user_params())
		{
			$url=$this->get_url();
			$image=$this->get_entity();
			$alt=$image->get_value('description') ? $image->get_value('description') : $image->get_value('name');
			$ret=array('url'=>$url,'alt'=>$alt);
			return $ret;
		}
		else
		{
			trigger_error('Cannot get URL and alt - make sure you have specified a valid entity and at least one dimension.');
			return false;
		}
	}

	/** private methods **/
	
	/**
	 * Are all the user params provided?
	 *
	 * @access private
	 * @todo we currently are not checking blit stuff at all ... should we?
	 * @return boolean true / false
	 */
	function _verify_user_params()
	{
		return ($this->get_id() && $this->_get_width() && $this->_get_height() && $this->get_crop_style());
	}
	
	/** 
	 * @access private
	 */
	function _get_url()
	{
		if (!isset($this->_url))
		{
			$entity = $this->get_entity();
			$filename = $this->_get_filename();
			if( $this->_orig_exists() && $entity->get_value('original_image_type') )
				$image_type = $entity->get_value('original_image_type');
			else
				$image_type = $entity->get_value('image_type');
			$this->_url = $this->get_image_dir_web_path() . $this->_get_entity_dir($entity->id()) . $filename . '.' . $image_type;
			if($this->use_absolute_urls)
				$this->_url = '//' . HTTP_HOST_NAME . $this->_url;
		}
		return $this->_url;
	}

	/** 
	 * @access private
	 */
	function _get_path()
	{
		if (!isset($this->_path))
		{
			$entity = $this->get_entity();
			$filename = $this->_get_filename();
			if( $this->_orig_exists() && $entity->get_value('original_image_type') )
				$image_type = $entity->get_value('original_image_type');
			else
				$image_type = $entity->get_value('image_type');
			$this->_path = $this->get_image_dir() . $this->_get_entity_dir($entity->id()) . $filename . '.' . $image_type;
		}
		return $this->_path;
	}
	
	function _get_filename()
	{
		if (!isset($this->_filename))
		{
			$id=$this->get_id();
			$image_type=$this->get_image_type();
			$last_modified = $this->_get_last_modified();
			$dimensions = $this->_get_dimensions();
			$width=$this->_get_width();
			$height=$this->_get_height();
			$crop_style = $this->get_crop_style();
			if ($last_modified && $dimensions && $crop_style) // if we have all the ingredients
			{
				$tmp=array($id,$image_type,$width,$height,$last_modified,$crop_style,$this->do_blit,$this->blit_file,$this->blit_options);
				$str=serialize($tmp);
				$this->_filename = md5($str);
			}
			else $this->_filename = false;
		}
		return $this->_filename;
	}
	
	function _get_last_modified()
	{
		$entity = $this->get_entity();
		return $entity->get_value('last_modified');
	}
	
	function get_entity()
	{
		if (!isset($this->_entity))
		{
			$id = $this->get_id();
			$this->_entity = new entity($id);
		}
		return $this->_entity;
	}
	
	/**
	 * Returns a string describing the width / height (ie 300x200).
	 *
	 * @return string
	 */
	function _get_dimensions()
	{
		if (!isset($this->_dimensions))
		{
			$this->_dimensions = ($this->_get_width() && $this->_get_height()) ? $this->_get_width() .'x'. $this->_get_height() : false;
		}
		return $this->_dimensions;
	}
	
	function _set_width_from_height()
	{
		if ( ($entity = $this->get_entity()) && ($height = $this->_get_height(false)) )
		{
			$path = reason_get_image_path($entity);
			$info = getimagesize($path);
			$ar = $info[0] / $info[1];
			$width = (int) ($ar * $height);
			$this->set_width($width);
		}
	}
	
	function _set_height_from_width()
	{
		if ( ($entity = $this->get_entity()) && ($width = $this->_get_width(false)) )
		{
			$path = reason_get_image_path($entity);
			$info = getimagesize($path);
			$ar = $info[1] / $info[0];
			$height = (int) ($ar * $width);
			$this->set_height($height);
		}
	}
	
	/**
	 * When making the image, the following are essential to the uniqueness
	 *
	 * - width
	 * - height
	 * - crop style
	 * - the entity last modified date (if it changes, we need a new image)
	 *
	 * @todo should we work with original size image if available or is that overkill given the processing time required??
	 * @return boolean success / failure
	 */
	function _make()
	{
		$entity = $this->get_entity();
		if ($entity)
		{
			if ($this->_make_sure_entity_directory_exists($entity->id()) && is_writable($this->get_image_dir() . $this->_get_entity_dir($entity->id())))
			{
				if( $this->_orig_exists())
					$path = reason_get_image_path($entity, 'original'); // we want to copy our original image to our destination and resize in place
				else
					$path = reason_get_image_path($entity);
				$newpath = $this->_get_path();
				$width = $this->_get_width();
				$height = $this->_get_height();
				$crop_style=$this->get_crop_style();
				if (empty($path)) trigger_error('reasonSizedImage could not determine the path of the original image - did not make a sized image');
				elseif (empty($newpath)) trigger_error('reasonSizedImage could not determine the proper destination for the sized image');
				elseif (empty($width) && empty($height)) trigger_error('reasonSizedImage needs to be provided a non-empty width or height value to create a sized image');
				else
				{
					
					if(!file_exists($path))
					{
						trigger_error('Image not found at '.$path.' -- unable to make resized image');
						return false;
					}
					copy($path, $newpath);
					
					//Do we need to sharpen the image?
					$sharpen=false;
					$info = getimagesize($path);
					$width_src=$info[0];
					$height_src=$info[1];
					$r= ($width*$height)/($width_src*$height_src);//r is for ratio
					if( $r >= 0.5  )
					{
						$sharpen=false;
					}
					$crop_style = $this->get_crop_style();
					
					if("crop_y" == $crop_style || "crop_x" == $crop_style)
					{
						$image_ratio = $width_src / $height_src;
						$box_ratio = $width / $height;
						
						if("crop_y" == $crop_style)
						{
							if($image_ratio < $box_ratio)
								$crop_style = "fill";
							else
								$crop_style = "fit";
						}
						else // crop_x
						{
							if($image_ratio > $box_ratio)
								$crop_style = "fill";
							else
								$crop_style = "fit";
						}
					}
					if("fit" == $crop_style)
					{
						$success = resize_image($newpath, $width, $height, $sharpen);
					}
					elseif("fill" == $crop_style)
					{
						$success = crop_image($width, $height, $path, $newpath, $sharpen);
					}
					
					if($this->do_blit)
					{
					//	blit_image($source,$dest,$watermark,$options=array())
						blit_image($newpath,$newpath,$this->blit_file,$this->blit_options);
					}
					
					clearstatcache();
					$perms = substr(sprintf('%o', fileperms($path)), -4);
    				$newperms = substr(sprintf('%o', fileperms($newpath)), -4);
    				if ($perms != $newperms) @chmod($newpath, octdec($perms));
    				return true;
    			}
    			return true;
			}
			else
			{
				trigger_error('reasonSizedImage class cannot write images to ' . $this->get_image_dir() . $this->_get_entity_dir($entity->id()));
			}
		}
		return false;
	}

	function _make_sure_entity_directory_exists($id)
	{
		$image_dir = $this->get_image_dir();
		if (is_dir($image_dir))
		{
			$entity_dir = $this->get_image_dir() . $this->_get_entity_dir($id);
			if (!is_dir($entity_dir))
			{
				$perms = substr(sprintf('%o', fileperms($this->get_image_dir())), -4);
				if (mkdir($entity_dir, octdec($perms), true))
				{
					clearstatcache();
					$newperms = substr(sprintf('%o', fileperms($entity_dir)), -4);
					if ($perms != $newperms) 
					{
						@chmod($entity_dir, octdec($perms));
						@chmod(dirname($entity_dir), octdec($perms));
					}
				}
				else
				{
					trigger_error('Unable to create sized image directory '.$entity_dir);
					return false; 
				}
			}
			return true;
		}
		else
		{
			trigger_error('Make sure the image directory ('.$this->get_image_dir().') references a directory that is web accessible and writable by apache'); 
		}
		return false;
	}
	
	/** Images are stored in a tree structure to prevent too many directories at one level.
	  * The last three digits of the image id determine its parent directory, so e.g. the
	  * image id 12345 finds its sized images in /set345/12345/
	  */
	function _get_entity_dir($id)
	{
		return sprintf('set%03s/%s/', substr($id, -3), $id);
	}
	
	/** Getters **/
	function get_id() { return $this->id; }
	
	function _get_width( $lookup_from_aspect_ratio = true )
	{
		if (!isset($this->width) && $lookup_from_aspect_ratio) $this->_set_width_from_height();
		if (!$this->allow_enlarge && !empty($this->width))
		{
			$this->width = min($this->width, $this->_get_entity_max_width());
		}
		return $this->width;
	}
	
	function _get_height( $lookup_from_aspect_ratio = true )
	{
		if (!isset($this->height) && $lookup_from_aspect_ratio) $this->_set_height_from_width();
		if (!$this->allow_enlarge && !empty($this->height))
		{
			$this->height = min($this->height, $this->_get_entity_max_height());
		}
		return $this->height;
	}

	function _get_entity_max_width()
	{
		$dimensions = $this->_get_entity_max_dimensions();
		return $dimensions[0];
	}

	function _get_entity_max_height()
	{
		$dimensions = $this->_get_entity_max_dimensions();
		return $dimensions[1];
	}
	
	function _get_entity_max_dimensions()
	{
		if (!isset($this->entity_max_dimensions))
		{
			$entity = $this->get_entity();
			if( $this->_orig_exists())
			{
				$path = reason_get_image_path($entity, 'original');
			}
			else
			{
				$path = reason_get_image_path($entity);
			}
			$this->entity_max_dimensions = getimagesize($path);
		}
		return $this->entity_max_dimensions;
	}
	
	function _orig_exists()
	{
		if(!isset($this->orig_exists))
		{
			$entity = $this->get_entity();
			$this->orig_exists = file_exists(reason_get_image_path($entity, 'original'));
		}
		return $this->orig_exists;
	}
	
	function get_image_dimensions()
	{
		if(!isset($this->image_dimensions))
		{
			$this->get_url();
			$path = $this->_get_path();
			$this->image_dimensions = getimagesize($path);
			return $this->image_dimensions;
		}
		else
		{
			return $this->image_dimensions;
		}
	}
	
	function get_image_height()
	{
		$image_dimensions = $this->get_image_dimensions();
		return $image_dimensions['1'];
	}
	
	function get_image_width()
	{
		$image_dimensions = $this->get_image_dimensions();
		return $image_dimensions['0'];
	}
	
	function get_image_dir() { return $this->image_dir; }
	function get_image_dir_web_path() { return $this->image_dir_web_path; }
	function get_crop_style() { return $this->crop_style; }
	function get_file_system_path_and_file_of_dest(){ return ($this->_get_path());}
	function get_image_type(){
		$entity=$this->get_entity();
		if( $this->_orig_exists() && $entity->get_value('original_image_type') )
			return $entity->get_value('original_image_type');
		else
			return $entity->get_value('image_type');
	}
	
	/** Setters **/
	function set_id($id)
	{
		$my_id = (int) $id;
		if ( empty($id) || ( $my_id != $id) || (strlen($my_id) != strlen($id)))
		{
			trigger_error('ID parameter provided ('.$id.') is invalid. Please provide the id as a postive integer.');
		}
		elseif (!reason_is_entity(new entity($my_id), 'image'))
		{
			trigger_error('ID parameter provided ('.$id.') does not correspond to a reason image.');
		}
		elseif($this->locked)
		{
			trigger_error('Image ID already defined. Create a new sized image object to use a different image.');
		}
		else $this->id = $my_id;
	}
	
	function set_width($width)
	{
		$width_int = (int) $width;	
		if ( empty($width) || ( $width_int != $width) || (strlen($width_int) != strlen($width)) )
		{
			trigger_error('Width parameter provided ('.$width.') is invalid. Please provide the width as a positive number of pixels in integer format.');
		}
		elseif($this->locked)
		{
			trigger_error('Image box width already defined. Create a new sized image object to use a different width.');
		}
		else $this->width = (int) $width;
	}
	
	function set_height($height)
	{
		$height_int = (int) $height;	
		if ( empty($height) || ( $height_int != $height) || (strlen($height_int) != strlen($height)) )
		{
			trigger_error('Height parameter provided ('.$height.') is invalid. Please provide the height as a positive number of pixels in integer format.');
		}
		elseif($this->locked)
		{
			trigger_error('Image box height already defined. Create a new sized image object to use a different height.');
		}
		else $this->height = (int) $height;
	}
	
	function set_crop_style($crop_style)
	{
		if (!in_array($crop_style, $this->available_crop_styles))
		{
			trigger_error('Crop style parameter provided ('.$crop_style.') is invalid. Valid styles are ('.implode(", ",$this->available_crop_styles).').');
		}
		elseif($this->locked)
		{
			trigger_error('Crop style already defined. Create a new sized image object to use a different crop style.');
		}
		else $this->crop_style = $crop_style;
	}
	
	function allow_enlarge($allow)
	{
		if (!is_bool($allow))
		{
			trigger_error('Allow enlarge parameter provided ('.$allow.') is invalid.  Must be true or false.');
		}
		elseif($this->locked)
		{
			trigger_error('Allow enlarge already defined. Create a new sized image object to use a different allow enlarge setting');
		}
		else $this->allow_enlarge = $allow;
	}
	
	/**
	 * Provide the file system and web path to use - the file system path is used to output images and the web path is used to construct URLs to those images.
	 *
	 * @param string image_dir - the absolute file system path to the output directory - should begin and end with a "/"
	 * @param string web_path - the path from the web server root directory to the directory where images can be accessed via http - should begin and end with a "/"
	 */
	function set_paths($image_dir, $web_path)
	{
		if (!empty($image_dir) && !empty($web_path))
		{
			if (!is_writable($image_dir))
			{
				trigger_error('The method set_paths failed - the image directory provided ('.$image_dir.') is not writable by apache.');
			}
			elseif($this->locked)
			{
				trigger_error('Image path already defined. Create a new sized image object to use a different path.');
			}
			else
			{	
				$this->_set_image_dir($image_dir);
				$this->_set_image_dir_web_path($web_path);
			}
		}
		else trigger_error('The method set_paths requires an absolute file system path AND the web path to setup the sized image output directory');
	}
	
	function use_absolute_urls($use_absolute_urls = true)
	{
		$this->use_absolute_urls = $use_absolute_urls;
	}
	
	/** Private Setters **/
	function _set_image_dir($image_dir) { $this->image_dir = $image_dir; }
	function _set_image_dir_web_path($image_dir_web_path) { $this->image_dir_web_path = $image_dir_web_path; }
}
?>
