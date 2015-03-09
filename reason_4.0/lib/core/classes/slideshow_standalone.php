<?php
	/* a refactoring of ImageSlideshowModule to allow slideshow generation to exist outside of page/module context. Originally written for use within
	 * the newsportal 2015 redesign.
	 *
	 * Example usage:
	 *		$slideshow = new SlideshowStandalone($optionalArrayWithInitializationValues);
	 *		$slideshow->setImages(arrayOfImageEntities);
	 *		echo $slideshow->getNormalOutput();
	 *
	 *	@author tfeiler
	 *	@date 2015-01-08
	 */

	class SlideshowStandalone {
		private $images;
		private $params;
		private $maxWidth;
		private $maxHeight;
		private $aspectRatio;

		private $headItems;
		private $echoHeadItems;

		function __construct($params = Array()) {
			$this->params = $params;

			if (isset($params['width']) && isset($params['height'])) {
				$this->maxWidth = $params['width'];
				$this->maxHeight = $params['height'];
			} else {
				$this->maxWidth = -1;
				$this->maxHeight = -1;
			}

			// when called from, say, a module like ImageSlideshowModule, headItems are passed in.
			// when called from, say, part of the Carleton Now story renderer subsystem, it's not.
			//
			// if headItems was passed in, we assume that the template/module system supplying it is outputting these headItems
			// during initialization. If we created it, it's up to us to spit it out (in the body unfortunately, but 
			// them's the breaks)
			$probeHead = $this->getParam('headItemsObj');
			$this->headItems = $probeHead != null ? $probeHead : new HeadItems();
			$this->echoHeadItems = $probeHead == null ? true : false;
			
			$this->images = Array();
		}

		function setImages($images) {
			$this->images = $images;
			if( empty($this->params['height']) || empty($this->params['width'])) {
				foreach($this->images as $img) {
					if (empty($this->params['width'])) {
						$this->maxWidth = max($this->maxWidth, $img->get_value("width"));
					}
					if (empty($this->params['height'])) {
						$this->maxHeight = max($this->maxHeight, $img->get_value("height"));
					}
				}
			}
			$this->aspectRatio = $this->maxHeight / $this->maxWidth;			

			if (count($this->images) > 0) {
				$this->prepStaticJsAndCss();
				$this->prepSliderHookupJs();
				$this->prepDynamicCss();
			}
		}

		function getParam($paramName, $defaultVal = null) {
			if (isset($this->params[$paramName])) {
				return $this->params[$paramName];
			} else {
				return $defaultVal;
			}
		}

		function getNormalOutput() {
			$rv = "";

			if ($this->echoHeadItems) {
				$rv .=  $this->headItems->get_head_item_markup();
			}
			// echo "INSIDE: <TEXTAREA ROWS=10 COLS=80>" . $this->headItems->get_head_item_markup() . "</TEXTAREA>";
			
			// Get the appropriate images and image descriptions
			$imageInfo = $this->getSlideshowImageInfo();
			
  			$rv .= "<div class=\"flexslider jsOff\"><ul class=\"slides\">";
  			$i = 0;
  			foreach($imageInfo as $info) {
  				$initHeight = $info['height'];
  				$initWidth = $info['width'];
  				
  				$rv .= '<li class="listelement">'."\n";
  				$rv .= '<div class="flexslider-img" id="slide_img_'. $i .'">'."\n";
  				$rv .= '<img class="slide-img" init_height="'. $initHeight .'" init_width="'. $initWidth .'" src="' . $info['url'] . '"/>'."\n";
  				$rv .= '</div>'."\n";

  				if ($this->getParam('caption_flag', true)) {
  					$text = '';
  					if ($this->getParam('show_short_caption', true)) {
  						$text .= '<strong class="shortCaption">'.strip_tags($info['description']).'</strong>';
  					}
  					if ($this->getParam('show_long_caption', true)) {
  						$text .= '<span class="longCaption">'.
						strip_tags($info['content'], '<a><br><em><strong><sub><sup><u>').'</span>';
  					}
  					if ($this->getParam('show_author', true)) {
  						$text .= '<span class="imageAuthor">'.$info['author'].'</span>';
  					}
  					$rv .= '<p class="flex-caption">'. $text .'</p>'."\n";
  				}
  					
  				$rv .= '</li>'."\n";
  				$i++;
  			}
  			$rv .= '</ul></div>';
			return $rv;
		}

		/**
		 * returns the html for text only/printer friendly mode
		 */
		function getTextOutput() {
			$imageInfo = $this->getSlideshowImageInfo();
			$rv = '<ul>';
			foreach ($imageInfo as $info) {
				$rv .= '<li>';
				$rv .= '<a href="'. $info['url'] .'">Image: '. $info['description'] .'</a>';
				$rv .= '</li>';
			}
			$rv .= '</ul>';
			return $rv;
		}

		function prepStaticJsAndCss() {
			$this->headItems->add_javascript(JQUERY_URL, true);
			$this->headItems->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/jquery.flexslider.js');
			$this->headItems->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH . 'FlexSlider/flexslider.css');
		}

		function prepSliderHookupJs() {
			//define the javascirpt functions needed for sizing the slideshow to preserve aspect ratio
			$sliderHookupJs = '
								var slider_width;
			
								$(window).load(function() {
																			
    								$(".flexslider").flexslider({ 
    									'. $this->getFlexsliderProperties() . '
    								
    									after: function(slider){
    										handleResizing();
    									}
    								});
    								
    								handleResizing();
								});
								
								function handleResizing() {
									var width = $(".flexslider").width();
									var height = width * '. $this->aspectRatio .';
									
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
										if ('.$this->aspectRatio.' > (img.attr("init_height")/img.attr("init_width"))) {
											frame_height_when_shrink_began = '.$this->aspectRatio.' * img.attr("init_width");
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
			
			$this->headItems->add_head_item('script', array('type' => 'text/javascript', 'charset' => 'utf-8'), $sliderHookupJs);
		}

		function prepDynamicCss() {
			/* Add slideshow related css to the head */
			$css = '.flexslider {
						max-width:'. $this->maxWidth .'px;
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
						width:'. $this->maxWidth .'px;
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
			
			$this->headItems->add_head_item('style', array('type' => 'text/css', 'charset' => 'utf-8'), $css);		
			
			$opacityClassCss = '/* Modernizr 2.5.3 (Custom Build) | MIT & BSD
			 * Build: http://modernizr.com/download/#-opacity-cssclasses-prefixes
			 */
			;window.Modernizr=function(a,b,c){function v(a){j.cssText=a}function w(a,b){return v(m.join(a+";")+(b||""))}function x(a,b){return typeof a===b}function y(a,b){return!!~(""+a).indexOf(b)}function z(a,b,d){for(var e in a){var f=b[a[e]];if(f!==c)return d===!1?a[e]:x(f,"function")?f.bind(d||b):f}return!1}var d="2.5.3",e={},f=!0,g=b.documentElement,h="modernizr",i=b.createElement(h),j=i.style,k,l={}.toString,m=" -webkit- -moz- -o- -ms- ".split(" "),n={},o={},p={},q=[],r=q.slice,s,t={}.hasOwnProperty,u;!x(t,"undefined")&&!x(t.call,"undefined")?u=function(a,b){return t.call(a,b)}:u=function(a,b){return b in a&&x(a.constructor.prototype[b],"undefined")},Function.prototype.bind||(Function.prototype.bind=function(b){var c=this;if(typeof c!="function")throw new TypeError;var d=r.call(arguments,1),e=function(){if(this instanceof e){var a=function(){};a.prototype=c.prototype;var f=new a,g=c.apply(f,d.concat(r.call(arguments)));return Object(g)===g?g:f}return c.apply(b,d.concat(r.call(arguments)))};return e}),n.opacity=function(){return w("opacity:.55"),/^0.55$/.test(j.opacity)};for(var A in n)u(n,A)&&(s=A.toLowerCase(),e[s]=n[A](),q.push((e[s]?"":"no-")+s));return v(""),i=k=null,e._version=d,e._prefixes=m,g.className=g.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(f?" js "+q.join(" "):""),e}(this,this.document);';
			
			$this->headItems->add_head_item('script', array('type' => 'text/javascript', 'charset' => 'utf-8'), $opacityClassCss);
			
			// Provide border-box sizing for bropwsers that can handle it (i.e. not ie 7-)
			$boxCss = '#meat .flex-caption {box-sizing:border-box; -webkit-box-sizing:border-box; -moz-box-sizing:border-box; width:100%}';
			$this->headItems->add_head_item('style', array('type' => 'text/css', 'charset' => 'utf-8'), $boxCss, array('before' => '<!--[if !IE 7]><!-->', 'after' => '<!-- <![endif]-->'));
		}

		/**
		 * Returns an array of image info for the given images.
		 * @param string $crop Crop style for the reason sized image. May be either 'fill' or 'fit'
		 * @return array Each element of the array is an associative array with the folowing keys: description, height, width, url
		 */
		function getSlideshowImageInfo() {
			$crop = $this->getParam('crop', '');

			$imageInfo = array();
			foreach ($this->images as $image) {
				$imgDescription = $image->get_value('description');
				$imgContent = $image->get_value('content');
				$imgAuthor = $image->get_value('author');
				$imgHeight = $image->get_value('height');
				$imgWidth = $image->get_value('width');
				
				if (0 != $this->maxHeight || 0 != $this->maxWidth) {
					$rsi = new reasonSizedImage();
					$rsi->set_id($image->id());
					$rsi->set_width($this->maxWidth);
					$rsi->set_height($this->maxHeight);
					if (!empty($crop)) $rsi->set_crop_style($crop);
					$imgUrl = $rsi->get_url();
					$imgHeight = $rsi->get_image_height();
					$imgWidth = $rsi->get_image_width();
				} else {
					$imgUrl = reason_get_image_url($image);
				}

				$imageInfo[] = array('description' => $imgDescription, 'content' => $imgContent, 'author' => $imgAuthor, 'height' => $imgHeight, 'width' => $imgWidth, 'url' => $imgUrl);
			}

			if (count($imageInfo) == 0) {
				trigger_error("No images set for this slideshow");
			}
			return $imageInfo;
		}

		/**
		 * This function returns a string of flexslider parameters that can be directly inserted into
		 * the javascript flexslider initialization code.
		 * @return string
		 */
		function getFlexsliderProperties() {
			$properties = '';
			foreach ($this->params as $param => $value) {
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
				} elseif ($param == 'slide_timer') {
					$properties .= 'slideshowSpeed: '. addslashes($value*1000) .', ';
				} elseif ($param == 'show_direction_nav') {
					$flag = $value ? 'true' : 'false';
					$properties .= 'directionNav: '. $flag .', ';
				} 
			}
			return $properties;
		}
	}
?>
