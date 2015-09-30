<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'classes/sized_image.php' );
	include_once( DISCO_INC.'disco.php' );
	
	/**
	 * Image Sizer Module
	 *
	 * This module allows site admins to get the URLs of Reason images
	 * at any size or crop style.
	 *
	 * @author Matt Ryan
	 */
	class ImageSizerModule extends DefaultModule // {{{
	{
		/**
		 * The disco form object that this module runs
		 * @var object disco form
		 */
		var $_form;
		/**
		 * The image entity that this module is sizing
		 *
		 * Do not access this object directly; use the method _get_image() instead.
		 *
		 * @var object reason image entity
		 */
		var $_image;
		/**
		 * Is this module OK rto run, based on the sharing properties, etc.?
		 *
		 * Do not access this var directly; use the method _ok_to_run_module() instead.
		 *
		 * @var boolean
		 */
		var $_ok_to_run;
		
		/**
		 * Constructor
		 */
		function ImageSizerModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Initialize the module
		 *
		 * Set up the page title, add appropriate css, and set up the form
		 *
		 * @return void
		 */
		function init()
		{
			parent::init();

			$this->admin_page->title = 'Custom Image Size';
			
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/reason_admin/image_sizer.css');
			
			if($this->_ok_to_run_module() && $image = $this->_get_image())
			{
				$this->admin_page->title .= ': '.$image->get_value('name');
				$this->_set_up_form($image);
			}
			
		}
		
		/**
		 * Set up the disco form for this module
		 *
		 * @param object reason image entity
		 * @return void
		 */
		function _set_up_form($image)
		{
			$max_dimensions = $this->_get_max_dimensions($image);
			
			$this->_form = new Disco();
			
			$this->_form->add_element('width','text', array('size'=>6));
			$this->_form->add_comments('width',' pixels <span class="smallText max">('.$max_dimensions['width'].' max)</span>');
			
			$this->_form->add_element('height','text', array('size'=>6));
			$this->_form->add_comments('height',' pixels <span class="smallText max">('.$max_dimensions['height'].' max)</span>');
			
			$this->_form->add_element('crop','radio_no_sort',array('options' => array('fit'=>'Do not crop (fit to width and height)','fill'=>'Crop so that image fills dimensions given') ) );
			$this->_form->add_required('crop');
			
			$this->_form->set_actions(array('generate'=>'Get Custom-Sized Image'));
			
			$pre_show_callback = array($this,'pre_show_disco');
			$this->_form->add_callback($pre_show_callback, 'pre_show_form');
			
			$error_checks_callback = array($this,'error_check_disco');
			$this->_form->add_callback($error_checks_callback, 'run_error_checks');
		}
		
		/**
		 * Get the image this module will be sizing
		 *
		 * Returns either a Reason image object or false if permissions, types, etc. not correct
		 *
		 * @return mixed
		 */
		function _get_image()
		{
			if(isset($this->_image))
				return $this->_image;
			if(!empty($this->admin_page->id))
			{
				$image = new entity($this->admin_page->id);
				if($image->get_values() && $image->get_value('type') == id_of('image') )
				{
					$this->_image = $image;
				}
				else
				{
					$this->_image = false;
				}
			}
			else
			{
				$this->_image = false;
			}
			return $this->_image;
		}
		/**
		 * Is it OK to run this module?
		 * @return boolean
		 */
		function _ok_to_run_module()
		{
			if($this->_ok_to_run !== true && $this->_ok_to_run !== false)
			{
				$this->_ok_to_run = false;
				
				if(!$this->admin_page->id)
				{
					return $this->_ok_to_run;
				}
			
				$owner_site = get_owner_site_id( $this->admin_page->id );
			
				$entity = new entity($this->admin_page->id);
			
				if($owner_site == $this->admin_page->site_id)
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
				
				if(site_borrows_entity( $this->admin_page->site_id, $entity->id() ))
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
				
				if(site_shares_type($owner_site, $entity->get_value('type')) && $entity->get_value('no_share') == 0 )
				{
					$this->_ok_to_run = true;
					return $this->_ok_to_run;
				}
			}
			return $this->_ok_to_run;
		}
		/**
		 * Run error checks on the image resizing form
		 * @param object disco form
		 * @return void
		 */
		function error_check_disco(&$form)
		{
			if($form->get_value('width'))
			{
				$int_width = (integer) $form->get_value('width');
				if(!is_numeric($form->get_value('width')) || $int_width != $form->get_value('width') )
				{
					$form->set_error('width','Width must be a whole number (integer)');
				}
				elseif($form->get_value('width') < 0)
				{
					$form->set_error('width','Width must be a positive number of pixels');
				}
			}
			if($form->get_value('height'))
			{
				$int_height = (integer) $form->get_value('height');
				if(!is_numeric($form->get_value('height')) || $int_height != $form->get_value('height') )
				{
					$form->set_error('height','Height must be a whole number (integer)');
				}
				elseif($form->get_value('height') < 0)
				{
					$form->set_error('height','Height must be a positive number of pixels');
				}
			}
			
			
			$image = $this->_get_image();
					
			$orig_path = reason_get_image_path($image,'original');
			
			if(!file_exists($orig_path) || $this->admin_page->site_id != get_owner_site_id( $this->admin_page->id ))
			{
				$largest_width = $image->get_value('width');
				$largest_height = $image->get_value('height');
			}
			else
			{
				list($largest_width, $largest_height) = getimagesize($orig_path);
			}
			
			if(!$form->has_errors())
			{
				$max_dimensions = $this->_get_max_dimensions($image);
				if($form->get_value('width') > $max_dimensions['width'])
					$form->set_error('width','Images may not be enlarged; please specify a width no greater than '.$max_dimensions['width'].' pixels.');
					
				if($form->get_value('height') > $max_dimensions['height'])
					$form->set_error('height','Images may not be enlarged; please specify a height no greater than '.$max_dimensions['height'].' pixels.');
			}
		}
		
		function _get_max_dimensions($image)
		{
			$orig_path = reason_get_image_path($image,'original');
			
			if(!file_exists($orig_path) || $this->admin_page->site_id != get_owner_site_id( $this->admin_page->id ))
			{
				$largest_width = $image->get_value('width');
				$largest_height = $image->get_value('height');
			}
			else
			{
				list($largest_width, $largest_height) = getimagesize($orig_path);
			}
			return array('width'=>$largest_width,'height'=>$largest_height);
		}
		
		/**
		 * Get HTML to display the sized image and its url at the top of the given form
		 * @param object disco form
		 * @return string HTML to display
		 */
		function pre_show_disco(&$disco)
		{
			if($disco->has_errors())
			{
				return '';
			}
			if($image = $this->_get_image())
			{
				$unsized_width = $image->get_value('width');
				$unsized_height = $image->get_value('height');
				
				$sized_width = $disco->get_value('width');
				$sized_height = $disco->get_value('height');
				
				
				
				if(
					( empty($sized_width) && empty($sized_height) )
					||
					( $unsized_width == $sized_width && $unsized_height == $sized_height )
				)
				{
					$showing_normal_size = true;
					$url = reason_get_image_url($image);
				}
				else
				{
					$showing_normal_size = false;
					
					$rsi = new reasonSizedImage();
					$server_path = REASON_SIZED_IMAGE_CUSTOM_DIR;
					$web_path = REASON_SIZED_IMAGE_CUSTOM_DIR_WEB_PATH;
					$rsi->set_paths($server_path, $web_path);
					$rsi->set_id($image->id());
					if(!empty($sized_width))
						$rsi->set_width($sized_width);
					if(!empty($sized_height))
						$rsi->set_height($sized_height);
					if($disco->get_value('crop'))
						$rsi->set_crop_style($disco->get_value('crop'));
					$url = $rsi->get_url();
				}
				
				$ret = '<div class="preview">'."\n";
				$ret .= '<div class="image"><img src="'.htmlspecialchars($url).'" alt="Image sized to '.($sized_width ? $sized_width : 'auto').' by '.($sized_height ? $sized_height : 'auto').' pixels" /></div>'."\n";
				if($showing_normal_size)
				{
					$ret .= '<div class="normalSizeNotice smallText">(This is the standard size of this image.)</div>'."\n";
				}
				else
				{
					$ret .= '<div class="url"><div class="label"><p>To use this image at this size:</p><ol><li>copy this web address</li><li>paste it into the "image at web address" tab in the "insert image" dialog box.</li></ol></div><input type="text" value="'.htmlspecialchars($url).'" size="50" /></div>'."\n";
				}
				$ret .= '</div>'."\n";
				
				if(!$showing_normal_size)
					$ret .= '<h4 class="tryAgainHeading">Try another size</h4>'."\n";
				
				return $ret;
			}
		}
		
		/**
		 * Run the module
		 * @return void
		 */
		function run() // {{{
		{
			if(!empty($this->_form))
			{
				echo '<div id="imageSizerModule">'."\n";
				$this->_form->run();
				echo '</div>'."\n";
			}
			else
				echo '<p>This module needs a valid image ID to run</p>'."\n";
		}
		
	} // }}}
?>