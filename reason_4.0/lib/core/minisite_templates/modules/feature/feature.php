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

include_once(CARL_UTIL_INC . 'basic/image_funcs.php');
include_once(CARL_UTIL_INC . 'basic/url_funcs.php');

if (!defined("FEATURE_VIEW_PATH"))
{
	define("FEATURE_VIEW_PATH",'minisite_templates/modules/feature/views/');
}

$GLOBALS[ '_module_class_names' ][ module_basename( __FILE__) ] = 'FeatureModule';

/**
 * The feature module displays entities of the feature type.
 *
 * Requirements for this module:
 *
 * - Works with JavaScript on or off ... though autoplay does require javascript.
 * - Loads a CSS file that hides inactive blocks and satisfies other css requirements - see reason_package/reason_4.0/www/css/feature.css
 * - Loads a javascript file that animates the feature set and makes smooth transitions without page reloads - see reason_package/reason_4.0/www/js/feature.js
 *
 * Supports these parameters:
 *
 * - shuffle - default false. If true, we sort features randomly rather than by sort order
 * - autoplay_timer - default 0 (off). If a positive integer, indicates the time delay between switches
 * - max - default 0 (no max). If positive, designates a maximum number of features to select
 * - width - default 400. We use this value as the image width for images set to "crop to fill" and also send it into the feature_image.php script.
 * - height - default 300.  We use this value as the image height for images set to "crop to fill" and also send it into the feature_image.php script.
 * 
 * Beta parameters:
 * - view - default DefaultFeatureView. Name of a view class to use.
 *
 * @todo update sample markup
 * @todo make views extensible without a lot of fuss.
 *
 * @author Nathan White and Frank McQuarry
 */
class FeatureModule extends DefaultMinisiteModule
{
	var $acceptable_params = array(
		'shuffle' => false,
		'autoplay_timer' => 0,
		'looping' => 'on',
		'max' => 0,
		'width' => 400,
		'height' => 300,
		'view' => 'DefaultFeatureView'
	);
	
	var $cleanup_rules = array('feature'=>'turn_into_int');
	var $features_view_params;
	var $current_feature_id = 0;
	
	private $_view;
	private $_view_data;
	private $_features;
				
	function init( $args = array() )
	{
		if ($features = $this->get_features())
		{
			$head_items =& $this->get_head_items();
			$head_items->add_javascript(JQUERY_URL, true);

			$canonical_url = get_current_url();
			$head_items->add_head_item('link',array('rel'=>'canonical','href'=>$canonical_url ), '');
			//create the view layer
			$view = $this->get_view();
			$view_data = $this->_view_data;
			$view_params = $this->get_view_params();
			$current_feature_id = $this->current_feature_id;
			$view->set($view_data,$view_params,$current_feature_id,$head_items);
		}
	}
	
	/**
	 * @todo make me work properly and figure out a scheme for including custom views.
	 */
	function get_view()
	{
		if (!isset($this->_view))
		{
			$view_class = 'DefaultFeatureView';
			if ($view_class != $this->params['view'])
			{
				$view = $this->params['view'];
				$file = FEATURE_VIEW_PATH . $view . '.php';
				if (reason_file_exists($file))
				{
					reason_require_once($file);
					if(isset($GLOBALS[ '_feature_view_class_names' ][$view]) && class_exists($GLOBALS[ '_feature_view_class_names' ][$view]))
					{
						$view_class = $GLOBALS[ '_feature_view_class_names' ][$view];
					}
					else
					{
						trigger_error('Feature view class not registered in $GLOBALS[ \'_feature_view_class_names\' ].');
					}
				}
				else
				{
					trigger_error('Feature view file not found in '.FEATURE_VIEW_PATH);
				}
			}
			$this->_view = new $view_class;
		}
		return $this->_view;
	}
	
	/**
	 * Return our feature set by reference - only build the feature set once.
	 */
	function get_features()
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
	function get_view_params()
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
		$es1 = new entity_selector( $this->site_id );
		$es1->add_type( id_of('feature_type') );
		$es1->add_right_relationship( $this->page_id, relationship_id_of('page_to_feature'));
		$es1->add_rel_sort_field( $this->page_id, relationship_id_of('page_to_feature') );
		$es1->set_order('rel_sort_order ASC');
		if($this->params['max'] != 0)
		{
			$es1->set_num($this->params['max']);
		}
		$features = $es1->run_one();
		
		if(empty($features))
		{
			return null;
		}
	
		$es2 = new entity_selector( $this->site_id );
		$es2->add_type( id_of('image') );
		$es2->add_right_relationship_field( 'feature_to_image','entity','id','feature_id', array_keys($features) );
		$es2->enable_multivalue_results();
		$images = $es2->run_one();
		if ($images)
		{
			foreach ($images as $image_id => $image)
			{
				$features_with_image = $image->get_value('feature_id');
				$features_with_image = (!is_array($features_with_image)) ? array($features_with_image) : $features_with_image; // force to array
				foreach ($features_with_image as $feature_id)
				{
					$feature_extra[$feature_id]['image_id'][] = $image_id;
				}
			}
		}

		$es3 = new entity_selector( $this->site_id );
		$es3->add_type( id_of('av') );
		$es3->add_right_relationship_field( 'feature_to_media_work','entity','id','av_id', array_keys($features) );
		$es3->enable_multivalue_results();
		$media_works = $es3->run_one();
		if ($media_works)
		{
			foreach ($media_works as $media_work_id => $media_work)
			{
				$features_with_media_work = $media_work->get_value('av_id');
				$features_with_media_work = (!is_array($features_with_media_work)) ? array($features_with_media_work) : $features_with_media_work; // force to array
				foreach ($features_with_media_work as $feature_id)
				{
					$feature_extra[$feature_id]['av_id'][] = $media_work_id;
				}
			}
		}
		
		// augment our features with images and media works
		foreach($features as $feature_id => $feature)
		{
			if (isset($feature_extra[$feature_id]['image_id']))
			{
				$value = (count($feature_extra[$feature_id]['image_id']) == 1) ? reset($feature_extra[$feature_id]['image_id']) : $feature_extra[$feature_id]['image_id'];
				$features[$feature_id]->set_value('image_id',$value);
			}
			else $features[$feature_id]->set_value('image_id',"none");
			
			if (isset($feature_extra[$feature_id]['av_id']))
			{
				$value = (count($feature_extra[$feature_id]['av_id']) == 1) ? reset($feature_extra[$feature_id]['av_id']) : $feature_extra[$feature_id]['av_id'];
				$features[$feature_id]->set_value('av_id',$value);
			}
			else $features[$feature_id]->set_value('av_id',"none");
		}
		
		//shuffle the features if set to true
		//note that keys are preserved in the new
		//shuffled feature array
		if($this->params['shuffle']==true)
		{
			$shuffled_results=array();
			$temps=array();
			$temps=$features;
			shuffle($temps);
			foreach($temps as $temp)
			{
				$id=$temp->get_value('id');
				$shuffled_results[$id]=$temp;
			}
			$features=$shuffled_results;
		}
		//pick a random media work or image from each features list of images.
		foreach($features as $id=>$r)
		{
			$num_imgs=0;
			$num_mdia=0;
			$i=0;
			if($features[$id]->has_value('image_id'))
			{
				$img_id=$features[$id]->get_value('image_id');
				if($img_id!="none")
				{
					$num_imgs=count($img_id);
				}
			}
			else
			{
				$features[$id]->set_value('image_id','none');
			}
			if($features[$id]->has_value('av_id'))
			{
				$av_id=$features[$id]->get_value('av_id');
				if($av_id !="none")
				{
					$num_mdia=count($av_id);
				}
			}
			else
			{
				$features[$id]->set_value('av_id','none');
			}

			$num_objects=$num_imgs+$num_mdia;

			$i=rand(0, $num_objects-1 );
			if($i<$num_mdia)
			{
				$features[$id]->set_value('current_object_type','av');
				$features[$id]->set_value('current_image_index',$i);
			}
			else
			{
				$features[$id]->set_value('current_object_type','img');
				$features[$id]->set_value('current_image_index',$i-$num_mdia);
			}
		}

		//set default values for items that are not set
		foreach($features as $id=>$r)
		{
			$cp=$features[$id]->get_value('crop_style');
			if($cp==null){ $features[$id]->set_value('crop_style','fill');}
			
		}
		$this->_features=$features;
		
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
		$this->features_view_params['looping']=$this->params['looping'];//or off
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
	}
	
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
		$features = $this->get_features();
		return (!empty($features));
	}
	
	function run()
	{
		$view = $this->get_view();
		$html_str = $view->get_html();
		echo $html_str;
	}
}
?>