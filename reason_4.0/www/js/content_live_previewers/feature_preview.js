/**
* This is the file that controls the live preview in the feature admin panel.
* It contains two separate AJAX calls. One for changing parameters in the form
* and a more expensive one that only resizes images when requested by the user
*/

var feature=Array();//an array the holds that parameters that can change in the live preview
var timeInterval;//returned value for the setInterval function in Javascript
var interval_length=3.5; // in seconds, determines how often to look for changes in the form containing a features values
var first_call=true;//controls stuff that has to be done when first loaded
var resize_images=false;//a switch to make sure that images only get resized when the controls request it.

$(document).ready(function() {

	init();
	update_live_preview();
	timeInterval=setInterval(cha_cha_cha_changes,interval_length*1000);

	function init()
	{
		freshen_up_feature_array();
	}
	
	/**
	* the function that gets called after the AJAX call gets finished
	* it updates the live preview with the returned data in data.
	*/
	function show_preview(data)
	{
		var content=""+data+"";
		$('#live_preview_view').html(content);
		var nav_str="<a href=\"\"><</a>"+"<a class=\"current\" href=\"\">1</a>"+"<a class=\"nonCurrent\" href=\"\">2</a>"+"<a href=\"\">></a>"
		$("div[class*=featureNav]").append(nav_str);
	    var width=parseInt(feature['w']);
	    var height=parseInt(feature['h']);
		$(".sizable").css('width',width);
		$(".sizable").css('height',height);
		pic=get_pic();
//		$('#live_preview_panel').append(pic.attr('src'));
		resize_images=false;
	}

	/**
	* updates the preview after the AJAX call responsible for 
	* resizing images, or changing the crop style
	*/
	function show_images(data)
	{
//		alert('foo'+data);
		var tmp=data.split(":");
		big_images=tmp[0].split(",");
//		alert(tmp[1]);
		av_img_urls=tmp[1].split(",");
//		alert(av_img_urls);
	    var width=parseInt(feature['w']);
	    var height=parseInt(feature['h']);
		$(".sizable").css('width',width);
		$(".sizable").css('height',height);

		var pic=get_pic();
//		pic.attr('width',feature_width);
//		pic.attr('height',feature_height);
//		alert(pic.attr('src')+"::"+av_img_urls[curr_av_index]);
		if(current_object_type=="img")
		{
			pic.attr('src',big_images[curr_img_index]);
		}
		else if(current_object_type=="av")
		{
			pic.attr('src',av_img_urls[curr_av_index]);
			//pic.attr('src',av_img_urls[1]);
		}
//		freshen_up_feature_array();
//		update_live_preview();
	}
	
	
	/**
	* sets up and implements an AJAX call for changing the live preview
	* with data from the form
	*/
	//WCCO: Go to the reporter at the scene
	function update_live_preview()
	{
		$.ajax({
		  url: scriptpath+"feature_preview.php",
		  data:{
					id:feature['id'],
					show_text:feature['show_text'],
					active:feature['active'],
					bg_color:feature['bg_color'],
					w:feature['w'],
					h:feature['h'],
					crop_style:feature['crop_style'],
					destination_url:feature['destination_url'],
					title:feature['title'],
					text:feature['text'],
					curr_img_index:feature['curr_img_index'],
					image_id:feature['image_id'],
					feature_image_url:feature['feature_image_url'],
					feature_image_alt:feature['feature_image_alt'],
					current_object_type:feature['current_object_type'],
					media_works_id:feature['media_works_id']
				},
		  success: show_preview
		});
	} 
	
	/**
	* sets up and implements an AJAX call for resizing
	* images
	*/
	function update_images()
	{
		freshen_up_feature_array();
		feature['image_id']=img_ids.join(",");// +","+ av_img_ids.join(",");
//		alert(feature['w']+'x'+feature['h']); 
		feature['av_image_id']=av_img_ids.join(",");
		feature['av_type']=av_types.join(",");
//		if($('input[name=img_func]:radio:checked').length>0)
//		{
//			feature['image_func']=$('input[name=img_func]:radio:checked').val();
//		}
//		else
//		{
			feature['image_func']="si";
//		}
		$.ajax({
		url: scriptpath+"feature_image_resize.php",
		data:{
				id:feature['id'],
				w:feature['w'],
				h:feature['h'],
				av_image_id:feature['av_image_id'],
				av_type:feature['av_type'],
				image_id:feature['image_id'],
				crop_style:feature['crop_style'],
				image_func:feature['image_func']
			},
		success: show_images
		});
	}



	/**
	* collects data from the form for passing in AJAX calls
	*/
	function freshen_up_feature_array()
	{
	//	alert($('#bg_color').val());
		
		feature['id']=$('#idElement').val();
		feature['show_text']=get_show_text();
		feature['active']="active";
		feature['bg_color']=$('#bg_color').val();
		feature['crop_style']=get_crop_style();
		feature['destination_url']=$('input[name=destination_url]').val();
		feature['title']=$('input[name=title]').val();
		feature['text']=$('textarea[name=text]').val();
		feature['curr_img_index']=curr_img_index;
		feature['w']=feature_width; 
		feature['h']=feature_height; 
		
		if(resize_images)
		{
			feature['image_id']=img_ids.join(",");
		}
		else
		{
			feature['image_id']="none";
		}		
		
		if(first_call)
		{
			if(current_object_type=="img")
			{
				feature['feature_image_url']=big_images[0];
				feature['feature_image_alt']=img_alts[0];
				feature['feature_av_img_url']="none";
				feature['feature_av_img_alt']="";

			}
			else if(current_object_type=="av")
			{
				feature['feature_image_url']="none";
				feature['feature_image_alt']="";
				feature['feature_av_img_url']=av_img_urls[0];
				feature['feature_av_img_alt']=av_img_alts[0];
			}
			else
			{
				feature['feature_image_url']="none";
				feature['feature_image_alt']="";
				feature['feature_av_img_url']="none";
				feature['feature_av_img_alt']="";
			}
			first_call=false;
		}
		else
		{


			var pic=get_pic();
			if(pic.length>0)
			{
				feature['feature_image_url']=pic.attr('src');
				feature['feature_image_alt']=pic.attr('alt');
			}
			else
			{
				feature['feature_image_url']="none";
				feature['feature_image_alt']="";
			}
		}
		if(av_ids.length>0 )
		{
				feature['media_works_id']=av_ids[0];	
		}
		feature['current_object_type']=current_object_type;
	
	}
	
	function get_show_text()
	{
		var show_text;
		if($('#radio_show_text_0').attr('checked')==true)
		{
			show_text=$('#radio_show_text_0').val();
		}
		else if($('#radio_show_text_1').attr('checked')==true)
		{
			show_text=$('#radio_show_text_1').val();
		}
		else
		{
			show_text="1";
		}
		return show_text;
	}
	
	function get_crop_style()
	{
		var crop_style;
		if($('#radio_crop_style_0').attr('checked')==true)
		{
			crop_style=$('#radio_crop_style_0').val();
		}
		else if($('#radio_crop_style_1').attr('checked')==true)
		{
			crop_style=$('#radio_crop_style_1').val();
		}
		else
		{
			crop_style="fill";
		}
		return crop_style;
	}
	
	/**
	* keeps an eye out for changes in the form controlling the live preview
	*/
	function cha_cha_cha_changes()
	{
		var bg_color=$('#bg_color').val();
		var title=$('input[name=title]').val();
		var text=$('textarea[name=text]').val();
		var destination_url=$('input[name=destination_url]').val();
		var show_text=get_show_text();
		
		var must_change_preview=0;
		must_change_preview+=( (bg_color!=feature['bg_color'])? 1 : 0 );
		must_change_preview+=( (title!=feature['title'])? 1 : 0 );
		must_change_preview+=( (text!=feature['text'])? 1 : 0 );
		must_change_preview+=( (destination_url!=feature['destination_url'])? 1 : 0 );
		must_change_preview+=( (show_text!=feature['show_text'])? 1 : 0 );

		if(must_change_preview>0)
		{
			freshen_up_feature_array();
			update_live_preview();
		}
	}
	
	function get_current_feature_image_url()
	{
		return big_images[curr_img_index];
	}
	
	/*
	* changes the feature image when you click on one of the
	* little thumbnails
	*/
	$('img[name*=feature_image]').click(function(event){
		event.preventDefault();
		var name=$(this).attr('name');
	  	var new_thumb_src=$(this).attr('src');
		var name_array=name.split('-');
		curr_img_index=name_array[1]-1;
		current_object_type="img";
		var new_src="";
		var new_alt="";
		new_src=big_images[curr_img_index];
		new_alt=img_alts[curr_img_index];
	  	var pic=get_pic();
		pic.attr('src',new_src);
		pic.attr('alt',new_alt);
		$('img[name*=feature_image]').css('border-style','none');
		$(this).css('border-style','solid');
	});
	
	$('img[name*=feature_av_image]').click(function(event){
		event.preventDefault();
		var name=$(this).attr('name');
	  	var new_thumb_src=$(this).attr('src');
		var name_array=name.split('-');
		curr_av_index=name_array[1]-1;
		current_object_type="av";
		var new_src="";
		var new_alt="";
		new_src=av_img_urls[curr_av_index];
		new_alt=av_img_alts[curr_av_index];
	  	var pic=get_pic();
		pic.attr('src',new_src);
		pic.attr('alt',new_alt);
		$('img[name*=feature_av_image]').css('border-style','none');
		$(this).css('border-style','solid');
	});	
	/*
	* returns the current img element being displayed in the feature
	*/
	function get_pic()
	{
		var pic=$('img[name=big_pic]');
//		var pic=$('div.featureContent>div.featureImage > a >img');
//		if(pic.length==0)
//		{
//			pic=$('div.featureContent> div.featureImage >img');
//		}
		return pic;
	}
	
	/*
	* the crop style select box, causes a resize image AJAX call.
	*/
	$("input[name=crop_style]:radio").change(function(){
		update_images();
		resize_images=true;
	});
	
	/* 
	* the control that controls the width and height of the feature
	* changes initiate a resize image AJAX call.
	*/
	$('#feature_dimensions').change(function(){
		var str=$(this).val();
		var dim=str.split("x");
		feature_width=parseInt(dim[0]);
		feature_height=parseInt(dim[1]);
		update_images();
		resize_images=true;
	});
	
	$('ul.tabs > li ').click(function(event){
		event.preventDefault();
		var str=$(this).children().children().html();
		var dim=str.split("x");
		feature_width=parseInt(dim[0]);
		feature_height=parseInt(dim[1]);
		update_images();
		resize_images=true;
	});
	
	
	//the following functions are used to test _gd_crop_image and 
	//_imagemagick_crop_image
	$("input[name=img_width]").change(function(){
		feature_width=$(this).val();
		update_images();
		resize_images=true;
	});
	$("input[name=img_height]").change(function(){
		feature_height=$(this).val();
		update_images();
		resize_images=true;
	});
	$('input[name=img_func]:radio').click(function(){
		update_images();
		resize_images=true;
	});

  });

