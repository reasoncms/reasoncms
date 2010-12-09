<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
/**
 * Include the parent class & dependencies, and register the module with Reason
 */
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once('function_libraries/image_tools.php');
reason_include_once( 'classes/sized_image.php' );
reason_include_once( 'classes/av_display.php' );
reason_include_once( 'classes/feature_helper.php' );
reason_include_once( 'minisite_templates/modules/feature/views/default_feature_view.php' );
//reason_include_once( 'minisite_templates/modules/feature/views/av_view.php' );


include_once(CARL_UTIL_INC . 'basic/image_funcs.php');
include_once(CARL_UTIL_INC . 'basic/url_funcs.php');

if (!defined("FEATURE_VIEW_PATH"))
{
	define("FEATURE_VIEW_PATH",REASON_INC."lib/core/minisite_templates/modules/feature/views/");
}


//$GLOBALS[ '_module_class_names' ][ basename( __FILE__."/feature/feature", '.php' ) ] = 'FeatureModule';
$GLOBALS[ '_module_class_names' ][ 'feature/feature' ] = 'FeatureModule';
//$GLOBALS[ '_module_class_names' ][ 'volunteer_roles/volunteer_roles' ] = 'VolunteerRolesModule';
	
/**
 * The feature module displays entities of the feature type.
 *
 * Here is sample markup for a page with two features attached that have the following characteristics:
 *
 * - The features each have bg_color set to #ff0000
 * - The first feature has crop set to fill, the second has crop set to fit.
 * - Each feature has several attached images - one should be chosen at random to display.
 *
 * 	<div class="featuresModule autoplay-5 noscript">
 *	<ul class="features">
 *	<li class="feature active" style="background-color:#ff0000;">
 *		<a href="http://destination_url"><img src="/reason_package/reason_4.0/www/displayers/feature_image.php?id=12345&amp;w=300&amp;h=200&amp;crop=fill" /></a>
 *		<div class="featureContent">
 *		<h3 class="featureTitle"><a href="http://destination_url">Feature 1 Title</a></h3>
 *		<div class="featureText"><a href="http://destination_url">Feature 1 Text</a></div>
 *		<div class="featureNav"><a href="?feature=12345" title="Feature 1 Title" class="current num1"></a><a href="?feature=23456" title="Feature 2 Title" class="nonCurrent num2"></a></div>
 *		</div>
 *	</li>
 *	<li class="feature inactive" style="background-color:#ff0000;">
 *		<a href="http://destination_url"><img src="/reason_package/reason_4.0/www/displayers/feature_image.php?id=23456&amp;w=300&amp;h=200&amp;crop=fit" /></a>
 *		<div class="featureContent">
 *		<h3 class="featureTitle"><a href="http://destination_url">Feature 2 Title</a></h3>
 *		<div class="featureText"><a href="http://destination_url">Feature 2 Text</a></div>
 *		<div class="featureNav"><a href="?feature=12345" title="Feature 1 Title" class="nonCurrent num1"></a><a href="?feature=23456" title="Feature 2 Title" class="current num2"></a></div>
 *		</div>
 *	</li>
 *	</ul>
 *	</div>
 *
 * Requirements for this module:
 *
 * - Works with JavaScript on or off ... though autoplay does require javascript.
 * - Loads a CSS file that hides inactive blocks and satisfies other css requirements - see reason_package/reason_4.0/www/css/feature.css
 * - Loads a javascript file that animates the feature set and makes smooth transitions without page reloads - see reason_package/reason_4.0/www/js/feature.js
 *
 * Supports these parameters
 *
 * - shuffle - default false. If true, we sort features randomly rather than by sort order
 * - autoplay_timer - default 0 (off). If a positive integer, indicates the time delay between switches
 * - max - default 0 (no max). If positive, designates a maximum number of features to select
 * - width - default 400. We use this value as the image width for images set to "crop to fill" and also send it into the feature_image.php script.
 * - height - default 300.  We use this value as the image height for images set to "crop to fill" and also send it into the feature_image.php script.
 * 
 * @author Nathan White and Frank McQuarry
 */
class FeatureModule extends DefaultMinisiteModule
{
	var $_features;
	var $acceptable_params = array ('shuffle' => false,
									'autoplay_timer' => 0,
									'max' => 0,
									'width' => 400,
									'height' => 300,
									'view'=>'DefaultFeatureView'
									);
	var $cleanup_rules=array('feature'=>'turn_into_int');
	
	var $_view_data=array();//an array to hold the data for the view layer.
	var $features_view_params;
	var $current_feature_id=0;
	var $markup; //an object to generate the html markup
	
				
	function init( $args = array() )
	{
		$features =& $this->get_features();
		if (!empty($features))
		{
			$this->set_default_params();
			$head_items =& $this->get_head_items();
			$head_items->add_javascript(JQUERY_URL, true);

			//create the view layer
			$this->get_view();
			$view_data =$this->_view_data;
			$view_params=& $this->get_view_params();
			$current_feature_id=$this->current_feature_id;
			$this->markup->set($view_data,$view_params,$current_feature_id,$head_items);
		}
	}
	
	function set_default_params()
	{
		$params=$this->params;
		if(!isset($params['shuffle'])){$this->params['shuffle']=false;}
		if(!isset($params['autoplay_timer'])){$this->params['autoplay_timer']=0;}
		if(!isset($params['max'])){$this->params['max']=0;}
		if(!isset($params['width'])){$this->params['width']=400;}
		if(!isset($params['height'])){$this->params['height']=300;}
		if(!isset($params['view'])){$this->params['view']="DefaultFeatureView";}
	}
	
	function get_view()
	{
		if($this->params['view']=="DefaultFeatureView")
		{
			$this->markup= new DefaultFeatureView();
		}
		else
		{
			$view=$this->params['view'];
			$file=FEATURE_VIEW_PATH.$view.'.php';
			if(is_file($file))
			{
				include $file;
				$this->markup= new $view();
			}
			else
			{
				$this->markup= new DefaultFeatureView();
			}
			//the price of using is_file
			clearstatcache();
		}
	}
	
	/**
	 * Return our feature set by reference - only build the feature set once.
	 */
	function &get_features()
	{
		if (!isset($this->_features))
		{
				$this->_build_features();
		}
		return $this->_features;
	}

	/**
	* return the features view params
	*/
	function &get_view_params()
	{
		if(!isset($this->features_view_params))
		{
			$this->_build_view_params();
		}
		return $this->features_view_params; 
	}
	/**
	 * Build the feature set
	 */
	function _build_features()
	{
		$max=0;
		if(isset($this->params['max']))
		{
			$max=$this->params['max'];
		}
		
		$es1 = new entity_selector( $this->site_id );
		$es1->add_type( id_of('feature_type') );
		$es1->add_right_relationship( $this->page_id, relationship_id_of('page_to_feature'));
		if($max !=0)
		{
			$es1->set_num($max);
		}
		$results_array1 = $es1->run_one();
		
		if(empty($results_array1))
		{
			return null;
		}
	
		$es2 = new entity_selector( $this->site_id );
		$es2->add_type( id_of('feature_type') );
		$es2->add_left_relationship_field( 'feature_to_image','entity','id','image_id');
		$es2->enable_multivalue_results();
		$es2->add_relation('entity.id IN ('.implode(',',array_keys($results_array1)).')');
		$results_array2 = $es2->run_one();

		$es3 = new entity_selector( $this->site_id );
		$es3->add_type( id_of('feature_type') );
		$es3->add_left_relationship_field( 'feature_to_media_work','entity','id','av_id');
		$es3->enable_multivalue_results();
		$es3->add_relation('entity.id IN ('.implode(',',array_keys($results_array1)).')');
		$results_array3 = $es3->run_one();
		
		foreach($results_array1 as $res1)
		{
			$id=$res1->get_value('id');
			if(array_key_exists($id,$results_array2))
			{
				$image_id=$results_array2[$id]->get_value('image_id');
				$results_array1[$id]->set_value('image_id',$image_id);
			}
			else
			{
				$results_array1[$id]->set_value('image_id',"none");
			}
			if(array_key_exists($id,$results_array3))
			{
				$av_id=$results_array3[$id]->get_value('av_id');
				$results_array1[$id]->set_value('av_id',$av_id);
			}
			else
			{
				$results_array1[$id]->set_value('av_id',"none");
			}


		}
		
		
		$results_array=$results_array1;
		
		//shuffle the features if set to true
		//note that keys are preserved in the new
		//shuffled feature array
		if($this->params['shuffle']==true)
		{
			$shuffled_results=array();
			$temps=array();
			$temps=$results_array;
			shuffle($temps);
			foreach($temps as $temp)
			{
				$id=$temp->get_value('id');
				$shuffled_results[$id]=$temp;
			}
			$results_array=$shuffled_results;
		}
		//pick a random media work or image from each features list of images.
		foreach($results_array as $id=>$r)
		{
			$num_imgs=0;
			$num_mdia=0;
			$i=0;
			if($results_array[$id]->has_value('image_id'))
			{
				$img_id=$results_array[$id]->get_value('image_id');
				if($img_id!="none")
				{
					$num_imgs=count($img_id);
				}
			}
			else
			{
				$results_array[$id]->set_value('image_id','none');
			}
			if($results_array[$id]->has_value('av_id'))
			{
				$av_id=$results_array[$id]->get_value('av_id');
				if($av_id !="none")
				{
					$num_mdia=count($av_id);
				}
			}
			else
			{
				$results_array[$id]->set_value('av_id','none');
			}

			$num_objects=$num_imgs+$num_mdia;

			$i=rand(0, $num_objects-1 );
			if($i<$num_mdia)
			{
				$results_array[$id]->set_value('current_object_type','av');
				$results_array[$id]->set_value('current_image_index',$i);
			}
			else
			{
				$results_array[$id]->set_value('current_object_type','img');
				$results_array[$id]->set_value('current_image_index',$i-$num_mdia);
			}
		}

		//set default values for items that are not set
		foreach($results_array as $id=>$r)
		{
			$cp=$results_array[$id]->get_value('crop_style');
			if($cp==null){ $results_array[$id]->set_value('crop_style','fill');}
			
		}
		$this->_features=$results_array;
		
		//if feature id is on this page then display it
		//else redirect to same page but this feature not set
		if(isset($this->request['feature']))
		{
			$feature_id=$this->request['feature'];
			$is_a_feature_on_this_page=false;
			foreach($this->_features as $feat)
			{
				if($feature_id==$feat->get_value('id'))
				{
					$is_a_feature_on_this_page=true;
					break;
				}
			}
			if($is_a_feature_on_this_page)
			{
				$this->current_feature_id=$this->request['feature'];
			}
			else
			{
				$url=carl_make_redirect(array('feature'=>''),'');
				header("Location: ".$url);
			}
			
			
		}
		
		$fh= new Feature_Helper();
		
		$this->_build_view_data();
	}



	
	/**
	* set the features_view_parms array with the details
	* needed by the view layer
	*/
	function _build_view_params()
	{
		$this->features_view_params['autoplay_timer']=$this->params['autoplay_timer'];
		$this->features_view_params['width']=$this->params['width'];
		$this->features_view_params['height']=$this->params['height'];
		$this->features_view_params['condensed_nav']=false;
		$this->features_view_params['looping']='on';//or off
	}

	/**
	 * Build the data needed by the view layer
	 */
	function _build_view_data()
	{
		$features=$this->get_features();
		$params=$this->get_view_params();
		$data=array();
		$feature_num=1;
 		$show_feature=true;
		$fh= new Feature_Helper();
		$width=$this->params['width'];
		$height=$this->params['height'];	
		
		
		//build the data needed by the view layer
		foreach($features as $feature)
		{
			$d=array();

			$d['id']=$feature->get_value('id');
			$d['show_text']=$feature->get_value('show_text');
			
			if($show_feature && $this->current_feature_id==0)
			{
				$show_feature=false;
				$d['active']="active";
			}
			else if($this->current_feature_id==$d['id'])
			{
				$show_feature=true;
				$d['active']="active";
				
			}
			else
			{
				$d['active']="inactive";
			}			

			$d['bg_color']=$feature->get_value('bg_color');

			$d['w']=$params['width']; 
			$d['h']=$params['height']; 
			$d['crop_style']=$feature->get_value('crop_style');;

			$params="?id=".$d['id']."&amp;w=".$d['w']."&amp;h=".$d['h']."&amp;crop=".$d['crop_style'];
			$d['feature_image_url']=REASON_HTTP_BASE_PATH."displayers/feature_image.php".$params;
			$d['destination_url']=$feature->get_value('destination_url');

			$d['title']=$feature->get_value('title');
			$d['text']=$feature->get_value('text');
			
			$feature_num++;
			$d['feature_image_url']=array();
			$img_id=$feature->get_value('image_id');
			$d['current_image_index']=$feature->get_value('current_image_index');
			if(isset($img_id))
			{
				$crop_style=$d['crop_style'];
				if(is_array($img_id))
				{
					foreach($img_id as $i)
					{
						$img_info=$this->get_image_url_and_alt($i,$crop_style);
						$d['feature_image_url'][]= $img_info['url'];
						$d['feature_image_alt'][]= $img_info['alt'];
					}
				}
				else
				{
					if($img_id!='none')
					{
						$img_info=$this->get_image_url_and_alt($img_id,$crop_style);
						$d['feature_image_url'][]= $img_info['url'];
						$d['feature_image_alt'][]= $img_info['alt'];

					}
					else
					{
						$d['feature_image_url'][]="none";
						$d['feature_image_alt'][]= "";
					}
				}
			}
			else
			{
				$d['feature_image_url'][]="none";
				$d['feature_image_alt'][]= "";
			}
			
			$av_id=$feature->get_value('av_id');
			$d['feature_av_html']=array();
			$av_info=array();
			if(isset($av_id))
			{
				if(is_array($av_id))
				{
					foreach($av_id as $id)
					{
						$av_info=$this->get_av_info($id);
						$d['feature_av_html'][]=$av_info['av_html'];
						$d['feature_av_img_url'][]=$av_info['av_img_url'];
						$d['feature_av_img_alt'][]=$av_info['av_img_alt'];
					}
				}
				else
				{
					if($av_id !="none")
					{
						$av_info=$this->get_av_info($av_id);
						$d['feature_av_html'][]=$av_info['av_html'];
						$d['feature_av_img_url'][]=$av_info['av_img_url'];
						$d['feature_av_img_alt'][]=$av_info['av_img_alt'];
					}
					else
					{
						$d['feature_av_html'][]="none";
						$d['feature_av_img_url'][]="none";
						$d['feature_av_img_alt'][]="";
					}
				}
			}
			else
			{
				$d['feature_av_html'][]="none";
				$d['feature_av_img_url'][]="none";
				$d['feature_av_img_alt'][]="";
			}
			
			$d['current_object_type']=$feature->get_value('current_object_type');
			

			$data[$feature->get_value('id')]=$d;
		}//end foreach loop through $features
		$this->_view_data=$data;

//		pray($this->_view_data);
		
	}//end _build_view_data function
	
	/**
	* returns the url to the sized image
	* @param $id image id of image to be sized
	* @param $crop_style how to size the image in the new wxh, fill or fit
	* @return The url to the sized image
	*/
	function get_image_url_and_alt($id,$crop_style="fill")
	{
		$width=$this->params['width'];
		$height=$this->params['height'];

		$rsi = new reasonSizedImage();
		$rsi->set_id($id);
		$rsi->set_width($width);
		$rsi->set_height($height);
		$rsi->set_crop_style($crop_style);
	 	$ret = $rsi->get_url_and_alt();
		return $ret;
	}
	
	
	function get_av_info($media_works_id)
	{
		$width=$this->params['width'];
		$height=$this->params['height'];
		$fh= new Feature_Helper();
		$av_info=$fh->get_av_info($media_works_id,$width,$height);
		return $av_info;
	}
	
	function has_content()
	{
		$features =& $this->get_features();
		return (!empty($features));
	}
	
	/**
	* this whole method is for testing the new image manipulation functions
	* that were written for feature
	* I haven't thrown it away yet because I'm not sure I won't need it again
	* @author Frank McQuarry  the person to blame for its continued existence.
	*/
	function test_sized_image()
	{
		$orig=reason_get_image_url(657759);
		
		$dest="/reason_package/reason_4.0/www/tmp/657759/3c405c4880f20057c9497eb9f69249d5.jpg";
		$ow=500;
		$oh=277;
		$nw=400;
		$nh=300;
		
		$or=$ow/$oh;
		$nr=$nw/$nh;
		
		$x=0;//($ow - $nw)*0.5;
		$y=0;//($oh - $nh)*0.5;

		$rsi = new reasonSizedImage();
		$rsi->set_id(657759);
		$rsi->set_width($nw);
		$rsi->set_height($nh);
		$rsi->set_crop_style("fill");
//		$rsi->_make();
	 	$url = $rsi->get_url();


	
		$source="/usr/local/webapps/branches/mcquarry-apps/reason_package/reason_4.0/data/images/657759.jpg";
		$target="/usr/local/webapps/branches/mcquarry-apps/reason_package/reason_4.0/www/tmp/657759/3c405c4880f20057c9497eb9f69249d5.jpg";
		//		_imagemagick_crop_image($nw,$nh,$source,$target,false);
		//		_gd_crop_image($nw, $nh, $source,$target,false);
		//		crop_image($nw, $nh, $source,$target,false);

		
		
		$resize="";
		if($nw<=$nh && $or>=$nr)
		{

			$resize=" -resize x".$nh." ";

		}
		elseif($nw<=$nh && $or<$nr)
		{
			$resize=" -resize ".$nw."x ";
		}
		elseif($nw>$nh && $or<$nr)
		{
			$resize=" -resize ".$nw."x ";
		}
		elseif($nw>$nh && $or>$nr)
		{
			$resize=" -resize x".$nh." ";;
		}
		else
		{
			$resize=" -resize ".$nw."x ";

		}

//		$exec="convert ".$resize."-gravity Center -crop ".$nw."x".$nh."+".$x."+".$y." ".$source." ".$target;


		$output = array();
		$exit_status = -1;
//		exec($exec, $output, $exit_status);
		if($exit_status != 0)
		{
		//	echo "ERROR!  ERROR!";
		}
		
		//Begin testing for blit_image function
		$insert="/usr/local/webapps/branches/mcquarry-apps/reason_package/reason_4.0/www/play_button_icons/launch.png";
		$url="/reason_package/reason_4.0/www/tmp/657759/3c405c4880f20057c9497eb9f69249d5.jpg";
		$options=array();
		$options['horizontal']="center";
		$options['horizontal_offset']=30;
		$options['vertical']="bottom";
		$options['vertical_offset']=-10;
//		_gd_blit_image($source,$target,$insert,$options);
		_imagemagick_blit_image($source,$target,$insert,$options);
//		blit_image($source,$target,$insert,$options);
		//End testing for blit_image function
	//	phpinfo();
		
		$str ="<div ";
		$str.="style=\"";
		$str.="width:".$ow."px;height:".$oh."px";
		$str.=";background-color:cccccc;border:solid";
		$str.="\"";
		$str.=">";
		$str.="<img src=\"".$orig."\" />";
		$str.="</div>";

		$str.="<div ";
		$str.="style=\"";
		$str.="width:".$nw."px;height:".$nh."px";
		$str.=";background-color:cccccc;border:solid";
		$str.="\"";
		$str.=">";
		$str.="<img src=\"".$url."\" />";
		$str.="</div>";

		
		echo $str;
	}
	
	function run()
	{
		//turn  this on if you want to test image functions
		//$this->test_sized_image();

		$html_str=$this->markup->get_html();
		echo $html_str;
		//pray($this->params);

	}
}// end FeatureModule class
?>