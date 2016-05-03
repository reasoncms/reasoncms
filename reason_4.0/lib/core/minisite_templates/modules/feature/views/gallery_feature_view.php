<?php

/**
 * A feature view that displays features as a simple list rather than as a slideshow
 * @author Matt Ryan
 *
 * @package reason
 * @subpackage minisite_modules
 */

/**
 * Include the abstract class this view will inherit from
 */
reason_include_once( 'minisite_templates/modules/feature/views/default_view.php' );
$GLOBALS[ '_feature_view_class_names' ][ basename( __FILE__, '.php') ] = 'GalleryFeatureView';

/**
 * A feature view that displays features as a simple list rather than as a slideshow
 */
class GalleryFeatureView extends FeatureView
{
	var $has_av = false;
	
	function set($view_data,$view_params,$current_feature_id,&$head_items)
	{
		parent::set($view_data,$view_params,$current_feature_id,$head_items);

		//Make sure all view data have default values
		//if they haven't been set
		foreach($view_data as $id=>$data)
		{
			if(!isset($data['id'])){ $view_data[$id]['id']=0; }
			if(!isset($data['destination_url'])){ $view_data[$id]['destination_url']=null; }
			if(!isset($data['bg_color'])){ $view_data[$id]['bg_color']="ffffff"; }
			if(!isset($data['title'])){ $view_data[$id]['title']="";}
			if(!isset($data['text'])){ $view_data[$id]['text']="";}
			if(!isset($data['w'])){ $view_data[$id]['w']=$this->default_width;}
			if(!isset($data['h'])){ $view_data[$id]['h']=$this->default_height;}
			if(!isset($data['show_text'])){ $view_data[$id]['show_text']=0;}
			if(!isset($data['current_image_index'])){ $view_data[$id]['current_image_index']=0;}
			if(!isset($data['current_object_type'])){ $view_data[$id]['current_object_type']="img";}
			if(!isset($data['feature_image_url'])){ $view_data[$id]['feature_image_url']=array("none");}
			if(!isset($data['feature_image_alt'])){ $view_data[$id]['feature_image_alt']=array("");}
			if(!isset($data['feature_av_html'])){ $view_data[$id]['feature_av_html']=array("none");}
			if(!isset($data['feature_av_img_url'])){ $view_data[$id]['feature_av_img_url']=array("none");}
			if(!isset($data['feature_av_img_alt'])){ $view_data[$id]['feature_av_img_alt']=array("");}
			if ($view_data[$id]['current_object_type'] == 'av') $this->has_av = true;
		}
		$this->_view_data=$view_data;
			
		$width=$this->_view_params['width'];
		$height=$this->_view_params['height'];
		if($head_items != null)
		{
			$head_items->add_javascript(JQUERY_URL, true);
			if ($this->has_av)
			{
				$head_items->add_javascript($this->absolutify_url_if_needed(REASON_PACKAGE_HTTP_BASE_PATH."nyroModal/js/jquery.nyroModal-1.6.2.min.js"));
				$head_items->add_javascript($this->absolutify_url_if_needed(REASON_HTTP_BASE_PATH . 'js/gallery_feature.js'));
			}
			$head_items->add_stylesheet($this->absolutify_url_if_needed(REASON_HTTP_BASE_PATH . 'css/features/gallery_feature.css'));
			
			$head_items->add_head_item("style",array(),".featuresModuleGallery ul>li>a, .featuresModuleGallery ul>li>.noLink { padding-bottom:" . round($height/$width*100, 5) . "%; }",false); 
		}
		
	}// end set function
	
	/**
	 * Create the markup
	 * @return string containing html markup for features
	 */
	function get_html()
	{
		$str = "";

		$view_data = $this->get_view_data();
		$count = 'count'.count($view_data);
		$str .= '<div class="featuresModuleGallery">';
		$str.= '<ul class="features">';
		foreach($view_data as $data)
		{
			$str .= $this->get_html_list_item($data);
		}
		$str.='</ul>';
		$str.='</div>';
	//	pray($data);
		return $str; 

	}

	/**
	* @return a string of html markup of a list item
	* basically the markup for one feature
	*/
	function get_html_list_item($view_data)
	{
		$has_anchor=true;
		if($view_data['destination_url']==null)
			$has_anchor=false;

		$height = $this->_view_params['height'];
		
		$anchor_start = '<div class="noLink">';
		$anchor_end = '</div>';

		if($has_anchor)
		{
			$anchor_start = "<a href=\"".$view_data['destination_url']."\" class=\"dest\">";
			$anchor_end = "</a>";
		}

		$media_str="";
		$type_str="";
		if($view_data['current_object_type']=='img')
		{
			$img_url=$view_data['feature_image_url'][$view_data['current_image_index']];
			$img_alt=$view_data['feature_image_alt'][$view_data['current_image_index']];
			if($img_url!="none")
			{
					$media_str ="<div class=\"featureImage\" >\n";
					$media_str.="<img alt=\"".reason_htmlspecialchars($img_alt)."\" name=\"big_pic\" src=\"".$img_url."\" />"."\n";
					$media_str.="</div>";
			}
			$type_str = ' featureTypeImage';
		}
		elseif($view_data['current_object_type']=='av')
		{
			$av_html=$view_data['feature_av_html'][$view_data['current_image_index']];
			$img_url=$view_data['feature_av_img_url'][$view_data['current_image_index']];
			$img_alt=$view_data['feature_av_img_alt'][$view_data['current_image_index']];
			$img_id="featureVideo-".$view_data['id'];
			$anchor_start = "<a href=\"#".$img_id."\" class=\"anchor\">\n";
			$anchor_end = '</a>';
			
			if($img_url!="none")
			{
				$link = ($has_anchor) ? ' (' .$anchor_start . 'more' . $anchor_end . ')' : '';
				$media_str ="<div class=\"featureImage\" >\n";
				$media_str.="<img alt=\"".reason_htmlspecialchars($img_alt)."\"  name=\"big_pic\" src=\"".$img_url."\" />"."\n";
				$media_str .= "</div>";
				$media_str .= "<div class=\"featureVideo nofitvids\" id=\"".$img_id."\" style=\"\">";
				$media_str .= $av_html;
				$media_str .= '<h3 class="featureTitle">'.$view_data['title'].'</h3>'."\n";
				$media_str .= '<div class="featureText">'.$view_data['text'].'</div>'."\n";
				$media_str .= "</div>";
			}
			$type_str = ' featureTypeVideo';
		}
		$title_str="";
		$text_str="";
		if($view_data['show_text'])
		{
			$title_str = "<h3 class=\"featureTitle\">".$view_data['title']."</h3>\n";
			$text_str = "<div class=\"featureText\">".$view_data['text']."</div>\n";
		}
		$str = '';
		$str .= '<li id="feature-'.$view_data['id'].'" class="cropStyle-'.reason_htmlspecialchars($view_data['crop_style']).' '.$type_str.'" style="background-color:#'.$view_data['bg_color'].';" >';
		$str .= $anchor_start;
		$str .= '<div class="featureContent">'."\n";
		$str .= $media_str;
		if($title_str || $text_str)
		{
			$str .= "<div class=\"featureInfo\">";
			$str .= $title_str;
			$str .= $text_str;
			$str .= "</div>";//end featureInfo div
		}
		$str .= '</div>';//end featureContent div
		$str .= $anchor_end;
		$str .= '</li>';

		return $str;
	}

	
}

?>