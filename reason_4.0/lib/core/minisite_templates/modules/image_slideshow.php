<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/image_sidebar.php' );
	reason_include_once('function_libraries/image_tools.php');

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ImageSlideshowModule';
	
	/**
	 * A minisite module that displays a js-based slideshow of images attached to the page
	 *
	 * @todo get css and inline javascript into external files.
	 * @todo make me work properly if only one dimensions is set (height or width) - right now dynamic determination is not based on image aspect ratio.
	 * @todo make flexsider width, height, aspect ratio stuff dynamic and determined in javascript.
	 * @todo with changes, try to avoid using reason sized image in the init phase.
	 */
	class ImageSlideshowModule extends DefaultMinisiteModule
	{
		/**
		 * An array of the slideshow's image entities
		 * @var array
		 */
		var $images;
		
		var $acceptable_params = array(
			'slideshow_type' => 'auto', 
			'width' => 0,
			'height' => 0,
			'crop' => '',
			//'force_image_enlargement' => false,
			'rand_flag' => false, 
			'num_to_display' => '',
			'caption_flag' => true,
			'show_long_caption' => true,
			'order_by' => '',
			'alternate_source_page_id' => '', 
			// The animation_type 'slide' is buggy in FlexSlider.  We're not going to support it right now.
			//'animation_type' => 'slide',
			//'slide_direction' => 'vertical',
			'slide_timer' => 5,
			'show_direction_nav' => true,
			//'show_control_nav' => false,
		);
		
		/**
		 * The tallest the slideshow should ever be
		 * @var int
		 */
		var $max_height;
		/**
		 * The widest the slideshow should ever be
		 * @var int
		 */
		var $max_width;
		/**
		 * height/width (to avoid having to find the inverse of this ratio every time)
		 * @var float
		 */
		var $aspect_ratio;
		
		
		/**
		 * This function returns a string of flexslider parameters that can be directly inserted into
		 * the javascript flexslider initialization code.
		 * @return string
		 */
		function get_flexslider_properties()
		{
			$properties = '';
			foreach ($this->params as $param => $value)
			{
				if ($param == 'slideshow_type') {
					$properties .= 'controlNav: false, ';
					if ($value == 'manual'){
						$properties .= 'slideshow: false, ';
					} elseif ($value == 'auto') {
						$properties .= 'directionNav: false, ';
						$properties .= 'keyboardNav: false, ';
						$properties .= 'mousewheel: false, ';
					}
				} elseif ($param == 'rand_flag') {
					$flag = $value ? 'true' : 'false';
					$properties .= 'randomize: '. $flag .', ';
				} /*elseif ($param == 'animation_type') {
					$properties .= 'animation: "'. addslashes($value) .'", ';
				} elseif ($param == 'slide_direction') {
					$properties .= 'slideDirection: "'. addslashes($value) .'", ';
				} */elseif ($param == 'slide_timer') {
					$properties .= 'slideshowSpeed: '. addslashes($value*1000) .', ';
				} elseif ($param == 'show_direction_nav') {
					$flag = $value ? 'true' : 'false';
					$properties .= 'directionNav: '. $flag .', ';
				} /*elseif ($param == 'show_control_nav') {
					$flag = $value ? 'true' : 'false';
					$properties .= 'controlNav: '. $flag .', ';
				} */
			}
			return $properties;
		}
		
		
		function init( $args = array() )
		{
			parent::init( $args );
			//Add necesary head items
			$head_items = $this->get_head_items();
			$head_items->add_javascript(JQUERY_URL, true);
			$head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/jquery.flexslider.js');
			$head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/flexslider.css');
						
			$this->select_images();

			if (empty($this->images))
			{
				//return here since there will not be a slide show with no images.
				return;
			}
			
			// Find the max height and width of the slide show considering picture sizes and given
			// height and width parameters.
			$this->max_height = $this->params['height'];
			$this->max_width = $this->params['width'];
			if( empty($this->params['height']) || empty($this->params['width']))
			{
				foreach($this->images as $img)
				{
					if (empty($this->params['height']) && $img->get_value('height') > $this->max_height)
					{
						$this->max_height = $img->get_value('height');
					}
					if (empty($this->params['width']) &&$img->get_value('width') > $this->max_width)
					{
						$this->max_width = $img->get_value('width');
					}
				}
			}
			$this->aspect_ratio = $this->max_height / $this->max_width;			
			
			//define the javascirpt functions needed for sizing the slideshow to preserve the aspect ratio and add them to the head.
			$slider_hookup_js = '
								var slider_width;
			
								$(window).load(function() {
																			
    								$(".flexslider").flexslider({ 
    									'. $this->get_flexslider_properties() . '
    								
    									after: function(slider){
    										handleResizing();
    									}
    								});
    								
    								handleResizing();
								});
								
								function handleResizing() {
									var width = $(".flexslider").width();
									var height = width * '. $this->aspect_ratio .';
									
									$(".slide-img").each(function() {
										var margin = getMarginTop($(this),height, width);
										$(this).css("margin-top", margin);
									});

									$(".flexslider-img").css("height",height);
									$(".listelement").css("height", height);
									$(".flexslider").css("height",height);
								}
								
								function getMarginTop(img, height_of_frame, width_of_frame){
									if (height_of_frame < img.attr("init_height") || width_of_frame < img.attr("init_width")) {
										if ('.$this->aspect_ratio.' > (img.attr("init_height")/img.attr("init_width"))) {
											frame_height_when_shrink_began = '.$this->aspect_ratio.' * img.attr("init_width");
										} else {
											frame_height_when_shrink_began = img.attr("init_height");
										}
										cur_height_of_image = img.attr("init_height") * (height_of_frame / frame_height_when_shrink_began);
									} else {
										cur_height_of_image = img.attr("init_height");
									}
									return (height_of_frame - cur_height_of_image) / 2;
								}
									  
								$(window).resize(handleResizing);
								
								$(document).ready(function() {
									handleResizing();
									$(".flexslider").removeClass("jsOff");
								});';
			
			$head_items->add_head_item('script', array('type' => 'text/javascript', 'charset' => 'utf-8'), $slider_hookup_js);
			
			/* Add slideshow related css to the head */
			$css = '.flexslider {
						max-width:'. $this->max_width .'px;
						background: rgba(0,0,0,.6);
						background: #444 \9;
						border: 0;
						border-radius: 0;
					}
					div.flexslider li.listelement {
						margin-top:0px !important;
						margin-bottom: 0 !important;
						margin-left: 0 !important;
					}
					img.slide-img {
						position:static !important;
						margin-left:auto !important;
						max-width:100% !important; 
						margin-right:auto !important; 
						max-height:100% !important;
						-ms-filter:inherit; This line needed to force IE8 to fade out slides
						/* opacity:inherit; /*This line needed to force IE8 to fade out captions*/
					}
					ul.slides {
						margin-left: 0 !important;
						margin-right: 0 !important;
						margin-top: 0 !important;
						margin-bottom: 0 !important;
					}
					div.flexslider-img {
						-ms-filter:inherit; /*This line needed to force IE8 to fade out slides*/
					}
					p.flex-caption {
						margin: 0 !important;
						background: rgba(0,0,0,.7);
						font-size: 9pt;
						/* opacity:inherit; /*This line needed to force IE8 to fade out captions*/
						//-ms-filter:inherit;
						//filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#4C000000,endColorstr=#4C000000); ZOOM: 1;
					}
					p.flex-caption strong.shortCaption, p.flex-caption span.longCaption {
						display:block;
					}
					div.flexslider.jsOff li {
						display: block !important;
					}
					td .flexslider {
						width:'. $this->max_width .'px;
					}
					
					/*arrow css for browsers that do not support opacity:*/
				
					.flex-direction-nav li a {
						width: 32px; 
						height: 54px;
						margin: -27px 0 0;
						background: url('.REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/theme/arrows_4up_8bit.png) no-repeat;
						}
					.flexslider:hover .flex-direction-nav li .next {
						background-position: -32px -54px;
					}
					.flexslider:hover .flex-direction-nav li .prev {
						background-position: 0 -54px;
					}
					.flex-direction-nav li .next {
						background-position: -32px 0; right: 0px;
					}
					.flex-direction-nav li .prev {
						left: 0px;
					}
							
					/*arrow css for browsers that do support opacity:*/

					.opacity .flex-direction-nav li a {
						width: 32px; 
						height: 54px;
						margin: -27px 0 0;
						background: url('.REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/theme/arrows_sprited_8bit.png) no-repeat;
						opacity: .6;
						transition: opacity .25s ease-in-out;
						-moz-transition: opacity .25s ease-in-out;
						-webkit-transition: opacity .25s ease-in-out;}
					.opacity .flexslider:hover .flex-direction-nav li .next {
						background-position: -32px 0; right: 0px;
						opacity: 1;
					}
					.opacity .flexslider:hover .flex-direction-nav li .prev {
						background-position: 0px 0px;
						opacity: 1;
					}
					.opacity .flex-direction-nav li .next {
						background-position: -32px 0; right: 0px;
					}
					.opacity .flex-direction-nav li .prev {
						left: 0px;
					}
				
					';
			
			$head_items->add_head_item('style', array('type' => 'text/css', 'charset' => 'utf-8'), $css);		
			
			$opacity_class_css = '/* Modernizr 2.5.3 (Custom Build) | MIT & BSD
			 * Build: http://modernizr.com/download/#-opacity-cssclasses-prefixes
			 */
			;window.Modernizr=function(a,b,c){function v(a){j.cssText=a}function w(a,b){return v(m.join(a+";")+(b||""))}function x(a,b){return typeof a===b}function y(a,b){return!!~(""+a).indexOf(b)}function z(a,b,d){for(var e in a){var f=b[a[e]];if(f!==c)return d===!1?a[e]:x(f,"function")?f.bind(d||b):f}return!1}var d="2.5.3",e={},f=!0,g=b.documentElement,h="modernizr",i=b.createElement(h),j=i.style,k,l={}.toString,m=" -webkit- -moz- -o- -ms- ".split(" "),n={},o={},p={},q=[],r=q.slice,s,t={}.hasOwnProperty,u;!x(t,"undefined")&&!x(t.call,"undefined")?u=function(a,b){return t.call(a,b)}:u=function(a,b){return b in a&&x(a.constructor.prototype[b],"undefined")},Function.prototype.bind||(Function.prototype.bind=function(b){var c=this;if(typeof c!="function")throw new TypeError;var d=r.call(arguments,1),e=function(){if(this instanceof e){var a=function(){};a.prototype=c.prototype;var f=new a,g=c.apply(f,d.concat(r.call(arguments)));return Object(g)===g?g:f}return c.apply(b,d.concat(r.call(arguments)))};return e}),n.opacity=function(){return w("opacity:.55"),/^0.55$/.test(j.opacity)};for(var A in n)u(n,A)&&(s=A.toLowerCase(),e[s]=n[A](),q.push((e[s]?"":"no-")+s));return v(""),i=k=null,e._version=d,e._prefixes=m,g.className=g.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(f?" js "+q.join(" "):""),e}(this,this.document);';
			
			$head_items->add_head_item('script', array('type' => 'text/javascript', 'charset' => 'utf-8'), $opacity_class_css);
			
			
			// Provide border-box sizing for bropwsers that can handle it (i.e. not ie 7-)
			$box_css = '#meat .flex-caption {box-sizing:border-box; -webkit-box-sizing:border-box; -moz-box-sizing:border-box; width:100%}';
			$head_items->add_head_item('style', array('type' => 'text/css', 'charset' => 'utf-8'), $box_css, $add_to_top = false, $wrapper = array('before' => '<!--[if !IE 7]><!-->', 'after' => '<!-- <![endif]-->'));
			
		}
		
		function has_content()
		{
			// Don't bother running the slideshow if no images exist
			if (empty($this->images))
			{
				return false;
			}
			return true;
		}
		
		function run()
		{	
			if (!empty($this->textonly))
			{
				$this->run_text_only();
			}
			else
			{
				$this->run_full_graphics();
			}
		}
		
		/**
		 * Identify the images that should be displayed
		 */
		function select_images()
		{
			// Initialize the images with appropriate entity selector properties
			
			$page_id = $this->page_id;
			if (!empty($this->params['alternate_source_page_id']))
			{
				$page_id = $this->params['alternate_source_page_id'];
				if (!($site_id = get_owner_site_id($page_id))) $site_id = $this->site_id;
			}
			else
			{
				$page_id = $this->cur_page->id();
				$site_id = $this->site_id;
			}
			
			$es = new entity_selector();
			$es->add_type(id_of('image'));
			$es->set_env('site', $site_id);
			$es->add_right_relationship($page_id, relationship_id_of('minisite_page_to_image'));
			if ($this->params['rand_flag']) $es->set_order('rand()');
			elseif (!empty($this->params['order_by'])) $es->set_order($this->params['order_by']);
			else
			{
				$es->add_rel_sort_field( $page_id, relationship_id_of('minisite_page_to_image') );
				$es->set_order('rel_sort_order');
			}
			if (!empty($this->params['num_to_display']))
			{
				$es->set_num($this->params['num_to_display']);
			}
			$this->images = $es->run_one();			
		}
		
		/**
		 * Outputs the html for text only/printer friendly mode
		 */
		function run_text_only()
		{
			$images_info = $this->get_slideshow_images_info($this->images, $this->params['crop'], $this->max_height, $this->max_width);
			echo '<ul>';
			foreach ($images_info as $info)
			{
				echo '<li>';
				echo '<a href="'. $info['url'] .'">Image: '. $info['description'] .'</a>';
				echo '</li>';
			}
			echo '</ul>';
		}
		
		/**
		 * Outputs the flexslider html for full graphics mode
		 */
		function run_full_graphics()
		{
			// Get the appropriate images and image descriptions
			$images_info = $this->get_slideshow_images_info($this->images, $this->params['crop'], $this->max_height, $this->max_width);
			
  			echo '<div class="flexslider jsOff">
  					<ul class="slides">';
  			$i = 0;
  			foreach($images_info as $info)
  			{
  				$init_height = $info['height'];
  				$init_width = $info['width'];
  				
  				echo '<li class="listelement">'."\n";
  				echo '<div class="flexslider-img" id="slide_img_'. $i .'">'."\n";
  				echo '<img class="slide-img" init_height="'. $init_height .'" init_width="'. $init_width .'" src="' . $info['url'] . '"/>'."\n";
  				echo '</div>'."\n";
  				if ($this->params['caption_flag'])
  				{
  					$text = '<strong class="shortCaption">'.strip_tags($info['description']).'</strong>';
  					if ($this->params['show_long_caption'])
  					{
  						$text .= '<span class="longCaption">'.strip_tags($info['content']).'</span>';
  					}
  					echo '<p class="flex-caption">'. $text .'</p>'."\n";
  				}
  					
  				echo '</li>'."\n";
  				$i++;
  			}
  			echo '</ul>
  				</div>';
		}
		
		/**
		 * Returns an array of image info for the given images.
		 * @param array $images array of image entities
		 * @param string $crop Crop style for the reason sized image. May be either 'fill' or 'fit'
		 * @param int $max_height The maximum height of the slideshow
		 * @param int $max_width The maximum width of the slideshow
		 * @return array Each element of the array is an associative array with the folowing keys: description, height, width, url
		 */
		function get_slideshow_images_info($images, $crop, $max_height, $max_width)
		{
			$images_info = array();
			foreach ($images as $image) 
			{
				$img_description = $image->get_value('description');
				$img_content = $image->get_value('content');
				$img_height = $image->get_value('height');
				$img_width = $image->get_value('width');
				
				//Check if making a reason sized image is necessary.
				//if ($img_height <= $max_height && $img_width <= $max_width && !$this->params['force_image_enlargement'])
				//{
				//	$img_url = reason_get_image_url($image);
				//}
				if (0 != $this->params['height'] or 0 != $this->params['width'])
				{
					$rsi = new reasonSizedImage();
					$rsi->set_id($image->id());
					$rsi->set_width($max_width);
					$rsi->set_height($max_height);
					//$rsi->allow_enlarge($this->params['force_image_enlargement']);
					if (!empty($crop)) $rsi->set_crop_style($crop);
					$img_url = $rsi->get_url();
					$img_height = $rsi->get_image_height();
					$img_width = $rsi->get_image_width();
				}
				else
				{
					$img_url = reason_get_image_url($image);
				}
				$images_info[] = array('description' => $img_description, 'content' => $img_content, 'height' => $img_height, 'width' => $img_width, 'url' => $img_url);
			}
			return $images_info;
		}
	}
?>