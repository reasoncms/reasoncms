<?php
/**
 * @package reason
 * @subpackage content_managers
 */

reason_include_once( 'function_libraries/image_tools.php' );
reason_include_once( 'classes/sized_image.php' );
reason_include_once( 'classes/page_types.php' );
reason_include_once( 'classes/feature_helper.php' );
reason_include_once( 'content_managers/default.php3' );

/**
 * Include the parent class and register the content manager with Reason
 */
$GLOBALS['_content_manager_class_names'][basename(__FILE__)] = 'FeatureManager';

/**
 * A content manager for features
 */
class FeatureManager extends ContentManager
{
	var $image_urls=array();
	var $image_alts=array();
	var $thumbnail_urls=array();
	var $av_image_urls=array();
	var $av_image_alts=array();
	var $av_thumbnail_urls=array();
	var $img_ids=array();
	var $av_ids=array();
	var $av_types=array();
	var $av_img_ids=array();
	var $width=400;
	var $height=300;
	var $crop_style="fill";
	
	function init_head_items()
	{
		//get all the images for the preview, and size them if necessary
		$this->init_images();
		$this->init_avs();
		
		if($this->av_ids[0]!="none")
		{
			$current_object_type="av";
		}
		else
		{
			$current_object_type="img";
		}
		
		//turn the arrays into strings comma delimited strings
		$image_urls_str=implode("\",\"", $this->image_urls);
		$thumbnail_urls_str=implode("\",\"", $this->thumbnail_urls);
		$image_alts_str=implode("\",\"", $this->image_alts);
		$av_image_urls_str=implode("\",\"", $this->av_image_urls);
		$av_thumbnail_urls_str=implode("\",\"", $this->av_thumbnail_urls);
		$av_image_alts_str=implode("\",\"", $this->av_image_alts);
		$image_ids_str=implode("\",\"", $this->img_ids);
		$av_ids_str=implode("\",\"", $this->av_ids);
		$av_img_ids_str=implode("\",\"", $this->av_img_ids);
		$av_types_str=implode("\",\"", $this->av_types);
		//The path to the scripts for the AJAX calls in live preview
		$script_path=REASON_HTTP_BASE_PATH.'scripts/content_live_previewers/';
		
		//now we put the javascript that live preview will need to operate into the 
		//head section.
		$this->head_items->add_head_item("script",array("type"=>"text/javascript"),"
					var scriptpath=\"$script_path\";
					var big_images=new Array(\"".$image_urls_str."\");
					var thumbnails=new Array(\"".$thumbnail_urls_str."\");
					var img_alts=new Array(\"".$image_alts_str."\");
					var av_img_urls=new Array(\"".$av_image_urls_str."\");
					var av_thumbnails=new Array(\"".$av_thumbnail_urls_str."\");
					var av_img_alts=new Array(\"".$av_image_alts_str."\");  
					var img_ids=new Array(\"".$image_ids_str."\");
					var av_ids=new Array(\"".$av_ids_str."\");
					var av_types=new Array(\"".$av_types_str."\");
					var av_img_ids=new Array(\"".$av_img_ids_str."\");
					var curr_img_index=0;
					var curr_av_index=0;
					var current_object_type=\"".$current_object_type."\";
					var feature_width=".$this->width.";
					var feature_height=".$this->height.";
					",true);
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/content_live_previewers/feature_preview.js');
		$this->head_items->add_javascript(REASON_HTTP_BASE_PATH.'js/tabs.js');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/features/feature.css');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/features/feature_admin.css');
		$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/tabs.css');
		
		//this css is necessary for the live preview to dynamically resize images, (and the feature being displayed)
		$this->head_items->add_head_item("style",array("type"=>"text/css"),"
		.sizable
		{
			width:".$this->width."px;
			height:".$this->height."px;
		}

		",false);
	}
	
	/**
	* get the images ids out of the database for this feature
	* and then get the sized images for the big pics
	* and thumbnails for the small clickable pics
	*/
	function init_images()
	{
		//now get an image, if it exists
		$id=$this->get_value('id');
		$es = new entity_selector();
		$es->add_type( id_of('feature_type') );
		$es->add_left_relationship_field( 'feature_to_image','entity','id','image_id');
		$es->enable_multivalue_results();
		$es->add_relation('entity.id='.$id);
		$results_array = $es->run_one("","All");

		
		//if this feature has no images, set default values
		// for ids and cropping.
		$img_ids="none";
		$crop_style=$this->crop_style;
		foreach($results_array as $r)
		{
			if($id==$r->get_value('id'))
			{
				$img_ids=$r->get_value('image_id');
				$cs=$r->get_value('crop_style');
				if($cs != null)
				{
					$crop_style=$cs;
				}
			}
		}
		
		//Now turn the image ids into url to the sized images
		// and thumbnails.
		$image_urls=array();
		$thumbnail_urls=array();
		if(isset($img_ids))
		{
			if(is_array($img_ids))
			{
				$this->img_ids=$img_ids;
				foreach($img_ids as $img_id)
				{
					$thumbnail_urls[]=reason_get_image_url($img_id,'thumbnail');
					$img_info=$this->get_image_url_and_alt($img_id,$crop_style);
					$image_urls[]=$img_info['url'];
					$image_alts[]=htmlspecialchars($img_info['alt'],ENT_QUOTES);
				}
			}
			else
			{
				if($img_ids!='none')
				{
					$this->img_ids[]=$img_ids;
					$thumbnail_urls[]=reason_get_image_url($img_ids,'thumbnail');
					$img_info=$this->get_image_url_and_alt($img_ids,$crop_style);
					$image_urls[]=$img_info['url'];
					$image_alts[]=htmlspecialchars($img_info['alt'],ENT_QUOTES);

				}
				else
				{
					$thumbnail_urls[]="none";
					$image_urls[]="none";
					$this->img_ids[]="none";
					$image_alts[]="";

				}
			}
		}
		else
		{
			$thumbnail_urls[]="none";
			$image_urls[]="none";
			$this->img_ids[]="none";
			$image_alts[]="";
		}
		$this->thumbnail_urls=$thumbnail_urls;
		$this->image_urls=$image_urls;
		$this->image_alts=$image_alts;
	}//end init_images function
	
	function init_avs()
	{
		//now get an av, if it exists
		$fh= new Feature_Helper();
		$id=$this->get_value('id');
		$av_ids=null;
		$results_array=$fh->get_avs_associated_with_feature($id);
		if(!empty($results_array))
		{
			foreach($results_array as $r)
			{
				$tmp=$r->get_value('av_id');
				if(is_array($tmp))
				{
					$av_ids=$tmp;
				}
				else
				{
					if($tmp != "none")
					{
						$av_ids=array(0=>$tmp);
					}
					else
					{
						$av_ids=array(0=>"none");
					}
				}
			}
	    }
		else
		{
			$av_ids=array(0=>"none");
		}
		$this->av_ids=$av_ids;
		
		$av_html=array();
		$av_img_url=array();
		$av_img_alt=array();
		$av_thumbnail_urls=array();
		$av_img_ids=array();
		$av_types=array();
		foreach($av_ids as $id)
		{
			if($id != "none")
			{
				$av_info=$fh->get_av_info($id,$this->width,$this->height);
				$av_html[]=htmlspecialchars($av_info['av_html'],ENT_QUOTES);
				$av_img_url[]=$av_info['av_img_url'];
				$av_img_alt[]=$av_info['av_img_alt'];
				if($av_info['av_img_id']!="none")
				{
					$av_thumbnail_urls[]=reason_get_image_url($av_info['av_img_id'],'thumbnail');
					$av_img_ids[]=$av_info['av_img_id'];
				
				}
				else
				{
					$av_thumbnail_urls[]=$av_info['av_img_url'];
					$av_img_ids[]="none";
				}
				$av_types[]=$av_info['type'];
				//pray($av_info);
			}
			else
			{
				$av_img_url[]="none";
				$av_img_alt[]="";
				$av_thumbnail_urls[]="none";
				$av_img_ids[]="none";
				$av_types[]="none";
			}
		}
		$this->av_image_urls=$av_img_url;
		$this->av_image_alts=$av_img_alt;
		$this->av_thumbnail_urls=$av_thumbnail_urls;
		$this->av_img_ids=$av_img_ids;
		$this->av_types=$av_types;
	}
	
	/**
	* use the image id to get a url to a sized image
	* @param $id image id of image to be sized
	* @param $crop_style how to fill the rectangle containing the
	*        image:  fill or fit
	* @return the url to the sized image
	*/
	function get_image_url_and_alt($id,$crop_style="fill")
	{
		$width=$this->width;;
		$height=$this->height;

		$rsi = new reasonSizedImage();
		$rsi->set_id($id);
		$rsi->set_width($width);
		$rsi->set_height($height);
		$rsi->set_crop_style($crop_style);
	 	$ret = $rsi->get_url_and_alt();
		return $ret;
	}
	
	/**
	* @return an html string containing image tags of the clickable thumbnails.
	*/
	function get_thumb_image_tags()
	{
		$thumbs=$this->thumbnail_urls;
		$av_thumbs=$this->av_thumbnail_urls;
		$count=1;//count is used to give each thumbnail an unique name
		$str="";
		$count=1;
		foreach($av_thumbs as $url)
		{
			$width="";
			if($url!="none")
			{
				$src=$url;
				if($url==null)
				{
					$width=" width=\"100\"";
					$src=$image_url[$count-1];
				}
				$str.="<a href=\"#\">";
				$str.="<img ";
				$str.=$width;
				$str.="name =\"feature_av_image-".$count."\" ";
				$str.="alt=\"\"";
				$str.="style=\"border-style:none\"";
				$str.="src=\"".$src."\"";
				$str.="/>";
				$str.="</a>\n";
			}
			$count++;
		}

		$count=1;
		foreach($thumbs as $url)
		{
			$width="";
			if($url!="none")
			{
				$src=$url;
				if($url==null)
				{
					$width=" width=\"100\"";
					$src=$image_url[$count-1];
				}
				$str.="<a href=\"#\">";
				$str.="<img ";
				$str.=$width;
				$str.="name =\"feature_image-".$count."\" ";
				$str.="alt=\"\"";
				if($count==1)
				{
					$str.="style=\"border-style:solid\"";
				}
				else
				{
					$str.="style=\"border-style:none\"";
				}
				$str.="src=\"".$src."\"";
				$str.="/>";
				$str.="</a>\n";
			}
			$count++;
		}

		return $str;
	}
	
	/**
	* @return an html string contain the controls for resizing the live preview
	*
	*/
	function get_preview_controls()
	{
		//get the parameters, location, and page types that use feature
		//and place the height and width as option tag values, and page type
		// and location as what the user sees when using the select box.
		$rpts =& get_reason_page_types();
		$ptypes=$rpts->get_params_of_page_types_that_use_module('feature/feature');
		$types = array();
		$contents=array();
		foreach($ptypes as $type)
		{
			$types[$type['page_type']] = $type;
		}
//		pray($types);
		if(!empty($types))
		{
			$prepped = array();
			foreach($types as $pt=>$type)
				$prepped[] = reason_sql_string_escape($pt);
			
			$es = new entity_selector();
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('custom_page IN ("'.implode('","',$prepped).'")');
			$es->add_left_relationship( $this->get_value('id'), relationship_id_of('page_to_feature'));
			$placed_pages = $es->run_one();
//			pray($placed_pages);
			
			foreach($placed_pages as $page)
			{
				$w=$this->width;
				$h=$this->height;
				if(!empty($types[$page->get_value('custom_page')]['params']['width']))
				{
					 $w=htmlspecialchars($types[$page->get_value('custom_page')]['params']['width']);
				}
				if(!empty($types[$page->get_value('custom_page')]['params']['height']))
				{
					$h=htmlspecialchars($types[$page->get_value('custom_page')]['params']['height']);
				}
				$name=strip_tags($page->get_value('name'));
				$contents[]=array('name'=>$name,'w'=>$w,'h'=>$h,'italicize'=>true);
			}
			$es = new entity_selector($this->get_value('site_id'));
			$es->add_type(id_of('minisite_page'));
			$es->add_relation('custom_page IN ("'.implode('","',$prepped).'")');
			if(!empty($placed_pages))
			{
				$es->add_relation('entity.id NOT IN ("'.implode('","',array_keys($placed_pages)).'")');
			}
			$site_pages = $es->run_one();
			
			foreach($site_pages as $page)
			{
				$w=$this->width;
				$h=$this->height;
				if(!empty($types[$page->get_value('custom_page')]['params']['width']))
				{
					 $w=htmlspecialchars($types[$page->get_value('custom_page')]['params']['width']);
				}
				if(!empty($types[$page->get_value('custom_page')]['params']['height']))
				{
					$h=htmlspecialchars($types[$page->get_value('custom_page')]['params']['height']);
				}
				$name=strip_tags($page->get_value('name'));
				$contents[]=array('name'=>$name,'w'=>$w,'h'=>$h);
			}

		}// end if(!empty($types))

		if(empty($contents))
		{
			$contents[]=array('name'=>"Default Size (No pages show features on the site yet)",'w'=>$this->width,'h'=>$this->height);
		}
		//testing with a ton O' tabs
/*
		for($i=100;$i<400;$i=$i+10)
		{
			$contents[]=array('name'=>"foo$i",'w'=>$i,'h'=>$i);
		}


		//testing with a ton O' pages
		for($i=100;$i<400;$i++)
		{
			$contents[]=array('name'=>"foo$i",'w'=>400,'h'=>300);
		}
*/
		$tabs=new Feature_Tabs();
		$tabs->set($contents);
		$w=$this->width; $h=$this->height;
//		echo $w."x".$h;
//		$tabs->set_active_tab($w."x".$h);
		$tab_html_str=$tabs->get_html();
		$str ="<h4 class=\"size_label\">Preview At Different Sizes </h4>";
		$str.=$tab_html_str;


		return $str;
	}
	
	/**
	* Thus function was used to test _cd_crop_image and _imagemagick_crop_image
	*/
	function test_resized_images()
	{

		$this->add_element('img_func', 'radio_inline',array('options' => array( 'si' => 'SI','im' =>'IM','gd'=>'GD')) );
		$this->move_element('img_func','before','name');
		$this->set_value('img_func','si');
		$str ="W: <input name=\"img_width\" type=text size=3 value=\"".$this->width."\" />";
		$str.=" H:<input name=\"img_height\" type=text size=3 / value=\"".$this->height."\">";

		$this->add_element('resize','comment',array('text'=>$str) );
		$this->move_element('resize','before','name');
		
	}
	
	/**
	* customize this form for feature objects
	*/
	function alter_data()
	{
		$this->add_required('title');
		
		$show_text=$this->get_value('show_text');
		$this->change_element_type('show_text', 'radio_no_sort',array('options' => array( '1' =>'Yes','0' => 'No')) );
		if($show_text=='0')
		{
			$this->set_value('show_text','0');
		}
		else
		{
			$this->set_value('show_text','1');
		}


		$crop_style=$this->get_value('crop_style');
		$this->change_element_type('crop_style', 'radio',array('options' => array( 
			'fill' => '<strong>Fill</strong> entire feature, even if it means cropping parts of the image', 
			'fit' =>'<strong>Fit</strong> entirely in the feature, even if it produces bars of the background color')) );
		if($crop_style=='fit')
		{
			$this->set_value('crop_style','fit');
		}
		else
		{
			$this->set_value('crop_style','fill');
		}


		$this->set_element_properties('text', array('rows'=>'3') );
		$this->change_element_type('bg_color', 'colorpicker' );
		$this->set_display_name('bg_color','Background Color');
		$this->set_display_name('destination_url','Link To');


		//for testing the hell out of sized images
//		$this->test_resized_images();

	
		
		//used for testing multiple color pickers
//		$this->add_element('foo_color','colorpicker');
//		$this->add_element('bar_color','colorpicker');

	}
	
	/**
	* set up the live preview area before the form for controlling it is
	* loaded.  It has three main sections: the live preview area, (live_preview_view), the area containing 
	* thumbnails, (live_preview_thumbnails),and the area containing controls, (live_preview_controls)
	* All three sections are contained in a larger section called live_preview_panel
	* "Wheels within wheels."
	*/
	function pre_show_form() 
	{
		$str ="\n<div id=\"live_preview_panel\" class=\"live_preview\" >\n";
		$str .= '<h3>Live Preview</h3>';
		$str.="<form action=\"\" method=\"post\" >\n";
		
		$str.="<div id=\"live_preview_view\">\n";
		$str.="</div>\n";
		$num_imgs=0;
		if($this->image_urls[0]!="none")
		{
			$num_imgs+=count($this->image_urls);
		}
		if($this->av_image_urls[0]!="none")
		{
			$num_imgs+=count($this->av_image_urls);
		}
		if($num_imgs>1)
		{
			$str.="<div class=\"live_preview_thumbnails\">\n";
			$str.=$this->get_thumb_image_tags();
			$str.="</div>\n";
		}
		$str.="<div class=\"live_preview_controls\">\n";
		$str.=$this->get_preview_controls();
		$str.="</div>\n";


		$str.="</form>\n";
		$str.="</div>\n";

		
		return $str;
	}


}




class Feature_Tabs 
{
	var $tab_content_html=array();
	var $tab_label=array();
	var $active_label=null;
	

	function cmp($a,$b)
	{
		if($a['w']<$b['w'])
		{
			return 1;
		}
		elseif($a['w']==$b['w'] && $a['h']<$b['h'])
		{
			return 1;
		}
		elseif($a['w']==$b['w'] && $a['h']==$b['h'])
		{
			return strcmp(strtolower($a['name']),strtolower($b['name']));
		}
		else
		{
			return 0;
		}
	}
	
	function cmpII_The_Movie($a,$b)
	{
		$a_count=count($a['contents']);
		$b_count=count($b['contents']);
		$a_area=($a['w']*$a['h']);
		$b_area=($b['w']*$b['h']);
		if($a['on_page']==0 && $b['on_page']==1)
		{
			return 1;
		}
		elseif($a['on_page']==1 && $b['on_page']==1 && $a_count < $b_count)
		{
			return 1;
		}
		elseif($a['on_page']==0 && $b['on_page']==0 &&  $a_count < $b_count)
		{
			return 1;
		}
		elseif( $a['on_page']==0 && $b['on_page']==0 && $a_count == $b_count && $a_area > $b_area ) 
		{
			return 1;
		}

		return 0;
	}

	function set($contents)
	{
		//sort the $contents array by decreasing width, then decreasing height
		//then alphabetically by name
		usort($contents,array($this,"cmp"));
		$num=count($contents);
		
		//firsts we get our tab labels organized
		$labels=array();
		$tmp=array();
		$tmp2=array();
		for($i=0;$i<$num;$i++)
		{
			$tmp[]=$contents[$i]['w']."x".$contents[$i]['h'];
		}
		$tmp2=array_unique($tmp);
		
		//sadly, array_unique preserves the keys as it removes repeated
		//values, so we need to reindex it.
		$ws=array();
		$hs=array();
		foreach($tmp2 as $val)
		{
			$labels[]=$val;
			$tmp=explode('x',$val);
			$ws[]=(int)$tmp[0];
			$hs[]=(int)$tmp[1];
		}

		//now we get the tab contents organized
		$content=array();
		$page=array();
		$name=array();
		$str="";
		$name_str="";
		$curr=0; $w=0; $h=0;
		$row=array();
		$on_page=0;
		foreach($labels as $label)
		{
			$row=array();
			$page=array();
			$on_page=0;
			for($i=0;$i<$num;$i++)
			{
				$w=$contents[$i]['w'];
				$h=$contents[$i]['h'];
				$str=$w."x".$h;
				if($label==$str)
				{
					$name_str=$contents[$i]['name'];
					if(isset($contents[$i]['italicize']) && $contents[$i]['italicize']==true)
					{
						$name_str="<em>".$name_str."</em>";
						$on_page=1;
					}
					$page[]=$name_str;
				}
			}
			sort($page);
			$row['on_page']=$on_page;
			$row['w']=$ws[$curr];
			$row['h']=$hs[$curr];
			$row['contents']=$page;
			$content[]=$row;
			$curr++;
		}

		usort($content,array($this,"cmpII_The_Movie"));

		$panels=array();
		$labels=array();
		foreach($content as $tent)
		{
			$tmps=$tent['contents'];
			$str="";	
			foreach($tmps as $tmp)
			{
				$str.=$tmp."<br />";
			}
			$labels[]=$tent['w']."x".$tent['h'];
			$panels[]=$str;

		}	
		
		$this->set_active_tab($labels[0]);	
		$this->tab_content_html=$panels;
		$this->tab_label=$labels;
//		echo "<pre>";
//		print_r($labels);
//		print_r($content);
//		echo "</pre>";
	}// end function set($contents)

	function set_active_tab($active_label)
	{
		$this->active_label=$active_label;
	}
	
	function set_tab_content($i,$label,$content)
	{
		$this->tab_content_html[$i]=$content;
		$this->tab_label[$i]=$label;
	}
	
	function get_tab_content($i, $class)
	{
		$str ="<div id=\"tab".$i."\" class=\"tab_content ".$class."\">\n";
		$str.=$this->tab_content_html[$i-1]."\n";
		$str.="</div>\n";
		return $str;
	}

	
	function get_html()
	{
		$num=count($this->tab_content_html);
		$str ="";
		$active_tab_str=" class=\"active_tab\" ";
		if($this->active_label == null)
		{
			$this->active_label=$this->tab_label[0];
		}
		
		if($num>0)
		{
			$str.="<div class=\"tabbed_content\">\n";
			$str.="	<div class=\"panel_container_contents\">\n";	

			$str.="	<div class=\"panel_container tabs\">\n";
			$str.="		<ul class=\"tabs\">\n";
			
					for($i=1; $i<=$num; $i++)
					{
						$label=$this->tab_label[$i-1];
						$str.="   		<li";
						if($label==$this->active_label)
						{
							$str.=$active_tab_str.">";
						}
						else
						{
							 $str.=">";
						}	
						$str.="<a href=\"#tab".$i."\"><strong>".$this->tab_label[$i-1]."</strong> </a></li>\n";
					}

					$str.="		</ul>\n";
			$str.="	</div>\n";

			$str.="		<div class=\"tab_container\">\n";
					for($i=1; $i<=$num;$i++)
					{
						$label=$this->tab_label[$i-1];
						if($label==$this->active_label)
						{
							$str.=$this->get_tab_content($i,"active_tab");
						}
						else
						{
							$str.=$this->get_tab_content($i,"inactive_tab");
						}
						
					}
			$str.="		</div>\n";
			$str.="	</div>\n";

		
			$str.="</div>\n";
		}
		return $str;
	}
	
}// end class Feature_Tabs



?>