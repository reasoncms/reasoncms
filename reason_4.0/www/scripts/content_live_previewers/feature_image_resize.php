<?php
/**
 * This is code that supports the live preview feature of the features content manager, and shouldn't be out loose in
 * the scripts directly like this. Instead, the feature content manager should define an API within the feature.php
 * content manager file. For now, this is in use but we should deprecate this soon.
 *
 * @package reason
 * @subpackage content_managers
 * @todo deprecate me and move functionality into the feature.php content manager.
 */
 
/**
 * Include dependencies.
 */
include_once("reason_header.php");
include_once(CARL_UTIL_INC.'basic/cleanup_funcs.php');
include_once(CARL_UTIL_INC . 'basic/image_funcs.php');
reason_include_once('function_libraries/image_tools.php');
reason_include_once( 'classes/sized_image.php' );
reason_include_once( 'classes/feature_helper.php' );

/**
 * @author Frank McQuarry
 */
//collect the data passed into the script
$d['id']=clean_up_data($_GET['id']);
$d['w']=clean_up_data( $_GET['w'] );
$d['h']=clean_up_data( $_GET['h'] );
$d['crop_style']=clean_up_data( $_GET['crop_style'] );
$d['image_id']=clean_up_data( $_GET['image_id'] );
$d['av_image_id']=clean_up_data( $_GET['av_image_id']);
$d['av_type']=clean_up_data( $_GET['av_type'] );
$d['image_func']=clean_up_data($_GET['image_func']);

//pray($_GET);
//pray($d);
/**
* make sure there are images to be resized
* the conditional is used for testing gd and imagemagick functions
* but normally resize_images() is the only function call you need.
*/
$img_urls="";
$av_img_urls="";

$img_urls=resize_images($d['image_id']);
$av_img_urls=resize_av_images($d['av_image_id']);
$str=" img_urls: [".$img_urls."]";
// echo $str;
echo $img_urls.":".$av_img_urls;

echo ":";
pray($_GET);

/**
* removes any tags that might have been passed in
*/
function clean_up_data($val)
{
	$clean_val =strip_tags( $val );
	return $clean_val;
}

/**
* resizes images using values stored in the global $d array
*/
function resize_images($image_id_str)
{
	
	global $d;
	$img_ids=explode(",",$image_id_str);

	$width=(int)$d['w'];
	$height=(int)$d['h'];
	$crop_style=$d['crop_style'];
	$url=array();
	foreach($img_ids as $id)
	{
		if($id != "none")
		{
			$rsi = new reasonSizedImage();
			$rsi->set_id($id);
			$rsi->set_width($width);
			$rsi->set_height($height);
			$rsi->set_crop_style($crop_style);
//			$rsi->_make();
	 		$url[] = $rsi->get_url();
		}
	}

	$str="";
	for($i=0;$i<count($url);$i++)
	{
		$str.="".$url[$i].",";
	}
	$str=trim($str,",");

	return $str;

}

function resize_av_images($image_id_str)
{
	
	global $d;
	$img_ids=explode(",",$image_id_str);
	$types=explode(",",$d['av_type']);

	$width=(int)$d['w'];
	$height=(int)$d['h'];
	$crop_style=$d['crop_style'];
	$url=array();
	$fh=new Feature_Helper();
	$curr=0;
	
	foreach($img_ids as $id)
	{
		if($id != "none")
		{
			$rsi = new reasonSizedImage();
			$rsi->set_id($id);
			$rsi->set_width($width);
			$rsi->set_height($height);
			$rsi->set_crop_style($crop_style);
			$watermark=$fh->get_watermark_absolute_path($types[$curr]);
			//$options=array('horizontal'=>'center','vertical'=>'center');
			$options=array();
			$options['horizontal']="center";
			$options['vertical']="center";			
			$rsi->set_blit($watermark,$options);
//			$rsi->_make();
	 		$url[] = $rsi->get_url();
			
//			$path=$rsi->get_file_system_path_and_file_of_dest();
//			blit_image($path,$path,$watermark,$options);
			
			$curr++;
	
		}
	}

	$str="";
	for($i=0;$i<count($url);$i++)
	{
		$str.="".$url[$i].",";
	}
	$str=trim($str,",");

	return $str;

}

/**
* A test function for testing _gd_crop image.  It allows 
* you to test several cases quickly
*/
function gd_resize_images()
{
	global $d;
	$img_ids=explode(",",$d['image_id']);

	$width=(int)$d['w'];
	$height=(int)$d['h'];
	$crop_style=$d['crop_style'];
	$target=array();
	$source=array();
	$url=array();
	foreach($img_ids as $id)
	{
		$rsi = new reasonSizedImage();
		$rsi->set_id($id);
		$rsi->set_width($width);
		$rsi->set_height($height);
		$rsi->set_crop_style($crop_style);
	 	$target[] = $rsi->_get_path();
	
		$entity = new entity($id);
		$source[] = reason_get_image_path($entity, 'standard');
		$url[]=$rsi->_get_url();
	}
	$i=0;
	reset($img_ids);
	foreach($img_ids as $id)
	{
		_gd_crop_image($width, $height, $source[$i],$target[$i],false);
		$i++;
	}
	$str="";
	for($i=0;$i<count($url);$i++)
	{
		$str.="".$url[$i].",";
	}
	$str=trim($str,",");

	echo $str;
}

/**
* A test function for testing _imagemagick_crop_image.  It allows
* you to test several cases quickly.
*/
function im_resize_images()
{
		global $d;
		$img_ids=explode(",",$d['image_id']);

		$width=(int)$d['w'];
		$height=(int)$d['h'];
		$crop_style=$d['crop_style'];
		$target=array();
		$source=array();
		$url=array();
		foreach($img_ids as $id)
		{
			$rsi = new reasonSizedImage();
			$rsi->set_id($id);
			$rsi->set_width($width);
			$rsi->set_height($height);
			$rsi->set_crop_style($crop_style);
		 	$target[] = $rsi->_get_path();

			$entity = new entity($id);
			$source[] = reason_get_image_path($entity, 'standard');
			
			$url[]=$rsi->_get_url();
		}
	//	print_r($target);
	//	pray($source);
	//	pray($url);
		for($i=0;$i<count($source);$i++)
		{
			_imagemagick_crop_image($width, $height, $source[$i],$target[$i],false);
		}
		$str="";
		for($i=0;$i<count($url);$i++)
		{
			$str.="".$url[$i].",";
		}
		$str=trim($str,",");

		echo $str;
	
	//		_imagemagick_crop_image($nw,$nh,$source,$target,false);

//	resize_images();
}


?>