<?php
include_once("reason_header.php");
include_once(CARL_UTIL_INC.'basic/cleanup_funcs.php');
reason_include_once('function_libraries/image_tools.php');
reason_include_once( 'classes/sized_image.php' );
reason_include_once( 'classes/av_display.php' );

/**
* A helper class for the Feature module.  In general it 
* eliminates code replication between the module and the manager
* @package reason
* @subpackage class
*/
class Feature_Helper
{
	var $media_work_id;
	var $avf_results;
	var $img_results;
	var $width;
	var $height;
	var $crop_style="fill";
	var $media_works_type="Video";
	
	/**
	* @return array of media works associated with the feature
	*/
	function get_avs_associated_with_feature($feature_id)
	{
		$es = new entity_selector();
		$es->add_type( id_of('feature_type') );
		$es->add_left_relationship_field( 'feature_to_media_work','entity','id','av_id');
		$es->enable_multivalue_results();
		$es->add_relation('entity.id='.$feature_id);
		$results_array = $es->run_one();
		return $results_array;
	}
	
	/**
	*  Possible types are "Video", "Interactive", "Quicktime",
	*  "Audio" or "Panorama".
	* @param $type string
	* @return boolean true if there is an av_type in the avf_results with $type
	*/
	function has_type($type)
	{
		$has_type=false;
		$results=$this->avf_results;
		foreach($results as $res)
		{
			$av_type=$res->get_value('av_type');
			if($av_type==$type)
			{
				$has_type=true;
			}
		}
		return $has_type;
	}
	
	/**
	* Possible formats are "Flash Video", "Flash", "Quicktime"
	* "Windows Media", "Real", or "MP3".
	* @param $format string
	* @return boolean true if there is a media_format with $format
	*/
	function has_format($format)
	{
		$has_format=false;
		$results=$this->avf_results;
		foreach($results as $res)
		{
			$av_format=$res->get_value('media_format');
			if($av_format==$format)
			{
				$has_format=true;
			}
		}
		return $has_format;
	}
	
	/**
	* Finds what is available in the list of av files in a hierarchal fashion
	* Preferring "Video" over "Interactive" over "Audio", etc
	* and within each type there is also a hierarchy over format
	* preferring "Flash Video" over "Flash" over "Quicktime", etc.
	* @return array of 2 elements with keys 'type' and 'format'
	*/
	function get_type_and_format()
	{
		$taf=array();
		if($this->has_type("Video"))
		{
			$taf['type']="Video";
			if($this->has_format("Flash Video"))
			{
				$taf['format']="Flash Video";
			}
			elseif($this->has_format("Flash"))
			{
				$taf['format']="Flash";
			}
			elseif($this->has_format("Quicktime"))
			{
				$taf['format']="Quicktime";
			}
			elseif($this->has_format("Windows Media"))
			{
				$taf['format']="Windows Media";
			}
			elseif($this->has_format("Video","Real"))
			{
				$taf['format']="Real";
			}
			elseif($this->has_format("MP3"))
			{
				$taf['format']="MP3";
			}
		}
		elseif($this->has_type("Interactive"))
		{
			$taf['type']="Interactive";
			if($this->has_format("Flash"))
			{
				$taf['format']="Flash";
			}
			elseif($this->has_format("Quicktime"))
			{
				$taf['format']="Quicktime";
			}
		}
		elseif($this->has_type("Audio"))
		{
			$taf['type']="Audio";
			if($this->has_format("MP3"))
			{
				$taf['format']="MP3";
			}
			elseif($this->has_format("Quicktime"))
			{
				$taf['format']="Quicktime";
			}
			elseif($this->has_format("Windows Media"))
			{
				$taf['format']="Windows Media";
			}
			elseif($this->has_format("Real"))
			{
				$taf['format']="Real";
			}
			elseif($this->has_format("Flash Video"))
			{
				$taf['format']="Flash Video";
			}
			elseif($this->has_format("Flash"))
			{
				$taf['format']="Flash";
			}
		}
		elseif($this->has_type("Panorama"))
		{
			$taf['type']="Panorama";
			if($this->has_format("Quicktime"))
			{
				$taf['format']="Quicktime";
			}
			elseif($this->has_format("Flash"))
			{
				$taf['format']="Flash Multimedia";
			}
		}
		else
		{
			$taf['type']="";
			$taf['format']="";
		}
		
		if(count($taf)!=2)
		{
			$taf['type']="";
			$taf['format']="";
		}
		return $taf;
	}

	/**
	* @return object representing the av file of $type and $format
	*/
	function get_avf($type,$format)
	{
		$avf=null;
		$results=$this->avf_results;
		foreach($results as $res)
		{
			$av_type=$res->get_value('av_type');
			$av_format=$res->get_value('media_format');
			if($av_type==$type && $av_format==$format)
			{
				$avf=$res;
			}	
		}
		return $avf;
	}
	
	function get_watermark_absolute_path($type)
	{
		$path=REASON_INC."www/play_button_icons/";
		switch($type)
		{
			case "Video":
				$path.="play_video.png";
			break;
			case "Interactive":
				$path.="launch.png";
			break;
			case "Audio":
				$path.="play_audio.png";
			break;
			case "Panorama":
				$path.="view_panorama.png";
			break;
		}	
		return $path;
	}
	
	function get_watermark_relative_path($type)
	{
		$path=REASON_HTTP_BASE_PATH."play_button_icons/";
		switch($type)
		{
			case "Video":
				$path.="play_video.png";
			break;
			case "Interactive":
				$path.="launch.png";
			break;
			case "Audio":
				$path.="play_audio.png";
			break;
			case "Panorama":
				$path.="view_panorama.png";
			break;
		}
		return $path;
	}
	
	/**
	 * If there is no image this is giving us a mess of an image with a huge icon.
	 */
	function get_av_img_url($image)
	{
		$width=$this->width;
		$height=$this->height;
		$crop_style=$this->crop_style;
		$id=$image->get_value('id');
		
		$rsi = new reasonSizedImage();
		$rsi->set_id($id);
		$rsi->set_width($width);
		$rsi->set_height($height);
		$rsi->set_crop_style($crop_style);
	
		$watermark=$this->get_watermark_absolute_path($this->media_works_type);
		$options=array();
		$options['horizontal']="center";
		$options['vertical']="center";
		$rsi->set_blit($watermark,$options);
		$url = $rsi->get_url();
		return $url;		
	}
	
	function get_av_info($media_works_id,$width=400,$height=300,$crop_style="fill")
	{
		$ret=array(); //the object that gets returned
		$this->media_works_id=$media_works_id;
		$this->width=$width;
		$this->height=$height;
		$this->crop_style=$crop_style;
		
		$media_work = new entity($this->media_works_id);
		
		$es = new entity_selector();
		$es->add_type( id_of('image') );
		$es->add_right_relationship( $media_works_id, relationship_id_of('av_to_primary_image') );
		$images = $es->run_one();
		$this->img_results=$images;
		
		if(count($images)>0)
		{
			$image = current($images);
			$av_img_url=$this->get_av_img_url($image);
			$ret['av_img_url']=$av_img_url;
			//set the av_image_alt to the image description
			$ret['av_img_alt']=$image->get_value('description');
			$ret['av_img_id']=$image->get_value('id');
		}
		else
		{
			/**
			 * This is not working
			 */
			//get a blank image with a play button blitted into it
			// set the av_image_alt to "play"
			$ret['av_img_url']=$this->get_watermark_relative_path($this->media_works_type);
			$ret['av_img_alt']="play";
			$ret['av_img_id']="none";
		}
		
		if($media_work->get_value('integration_library') && $media_work->get_value('integration_library') != 'default')
		{
			reason_include_once('classes/media/factory.php');
			$displayer_chrome = MediaWorkFactory::displayer_chrome($media_work, 'default');
			if ($displayer_chrome)
			{
				$displayer_chrome->set_media_work($media_work);
				$displayer_chrome->set_media_height($height);
				$displayer_chrome->set_media_width($width);
				$ret['av_html'] = $displayer_chrome->get_html_markup();
				$ret['type'] = $media_work->get_value('av_type');
				$ret['format'] = 'HTML5';
			}
			else
			{	// TODO: test this?
				$ret['av_html'] = '<p>Not available.</p>';
				$ret['type'] = $media_work->get_value('av_type');
				$ret['format'] = 'HTML5';
			}
		}
		else
		{
			$avd = new reasonAVDisplay();
			
			$es = new entity_selector();
			$es->add_type( id_of('av_file' ) );
			$es->add_right_relationship( $media_works_id, relationship_id_of('av_to_av_file') );
			$es->set_order('av.media_format ASC, av.av_part_number ASC');
			$results=$es->run_one();
			$this->avf_results=$results;
			$avf=null;
			
			$taf=array();
			$taf=$this->get_type_and_format();
			$avf=$this->get_avf($taf['type'],$taf['format']);
			$ret['type']=$taf['type'];
			$ret['format']=$taf['format'];
			
			$this->media_works_type=$taf['type'];
			
			$avd->set_video_dimensions($width,$height);
			$avd->disable_automatic_play_start();
			if(!empty($image))
			{
				$avd->set_placard_image($image); // This could be an entity, an ID, or a URL string
				//get the image with a play button blitted into it
			}
	
			$embed_markup="";
			if($avf!=null)
			{
				$embed_markup = $avd->get_embedding_markup($avf);
			}
			$ret['av_html']=$embed_markup;
		}
		return $ret;
	}
}
?>