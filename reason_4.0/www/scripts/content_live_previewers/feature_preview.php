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
include_once("reason_header.php");
include_once(CARL_UTIL_INC.'basic/cleanup_funcs.php');


/**
 * Include dependencies.
 */
reason_include_once( 'minisite_templates/modules/feature/views/default_feature_view.php' );
reason_include_once( 'classes/feature_helper.php' );

/**
 * @author Frank McQuarry
 */
//collect the data that's been sent to the script
$d['id']=clean_up_data($_GET['id']);
$show=clean_up_data($_GET['show_text']);
if($show=="1")
{
	$d['show_text']=1;
}
else
{
	$d['show_text']=0;
}
$d['active']=clean_up_data( $_GET['active'] );
$d['bg_color']=clean_up_data( trim($_GET['bg_color'],"#") );
$d['w']=clean_up_data( $_GET['w'] );
$d['h']=clean_up_data( $_GET['h'] );
$d['crop_style']=clean_up_data( $_GET['crop_style'] );
$d['destination_url']=clean_up_data( $_GET['destination_url'] );
$d['title']=clean_up_data( $_GET['title'] );
$d['text']=clean_up_data( $_GET['text'] );

$d['current_image_index']=0;
$img_url=clean_up_data( $_GET['feature_image_url'] );
$d['feature_image_url']=array(0=>$img_url);
$img_alt=clean_up_data($_GET['feature_image_alt']);
$d['feature_image_alt']=array(0=>$img_alt);
$d['image_id']=clean_up_data( $_GET['image_id'] );

if(isset($_GET['current_object_type']) && $_GET['current_object_type']=="av" )
{
	
	$d['current_object_type']=clean_up_data($_GET['current_object_type']);
	$media_works_id=clean_up_data($_GET['media_works_id']);

	$fh=new Feature_Helper();
	$av_info=$fh->get_av_info($media_works_id,$d['w'],$d['h'],$d['crop_style']);
	if(!empty($av_info))
	{
		$d['feature_av_html']=array(0=>$av_info['av_html']);
		$d['feature_av_img_url']=array(0=>$av_info['av_img_url']);
	}
	else
	{
		$d['feature_av_html']=array(0=>"none");
		$d['feature_av_img_url']="none";
	}
}
else
{
	$d['current_object_type']="img";
	$d['feature_av_html']=array(0=>"none");
	$d['feature_av_img_url']="none";
}



//put the data in a format that the markup class expects
$features =array();
$features[$d['id']]=$d;


//pray($features);

$view_params=array();
$current_feature_id=$d['id'];

$head_items=array();

$fmg = new DefaultFeatureView();
$fmg->set($features,$view_params,$current_feature_id,$head_items);
$markup = $fmg->get_html();
echo $markup;


/**
* strip out html tags that are not allowed from incoming data.
*/
function clean_up_data($val)
{
	$allowed_tags="<a><abbr><acronym><b><bdo><cite><code><del><dfn><em><i><ins><kbd><q><samp><span><strong><sub><sup><tt><var>";
	
	$clean_val = 	carl_get_safer_html( strip_tags($val,$allowed_tags));

	return $clean_val;
}

/**
* You've always wanted a zombie baby, right?  Here's your big chance.
*/
function grow_zombie_baby($id)
{
	$baby=array();
	$baby['id']=$id;
	$baby['active']=" ";
	$baby['bg_color']=" ";
	$baby['w']=" ";
	$baby['h']=" ";
	$baby['crop_style']=" ";
	$baby['destination_url']=null;
	$baby['title']=" ";
	$baby['text']=" ";
	$baby['image_id']=" ";
	$baby['current_image_index']=0;
	$baby['feature_image_url']=array(0=>"none");
	
	return $baby;
	
}

?>