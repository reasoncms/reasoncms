<?php
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );
reason_include_once( 'config/modules/profile/config.php' );
reason_include_once( 'minisite_templates/modules/profile/lib/profile_functions.php' );
reason_include_once( 'minisite_templates/modules/profile/lib/profile_person.php' );
reason_include_once('content_managers/image.php3');
include_once( DISCO_INC . 'boxes/linear.php' );

$GLOBALS[ '_profiles_model' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileFeatureModel';

/**
 * Returns profile feature object for a username.
 *
 * Assumes profile_get_config context and configuration with a username.
 *
 * @todo throw error when used without a username.
 *
 */
class DefaultProfileFeatureModel extends ReasonMVCModel
{
	function build()
	{
		$profile_config = profile_get_config();
		$username = $this->config('username');
		if (!empty($username))
		{
			$person = new $profile_config->person_class($username);
			$profile_id = $person->get_profile_id();
			if ($profile_id)
			{
				$profile_feature_set = new ProfileFeatureSet($profile_id, $username);
				return $profile_feature_set;
			}
		}
		return '';
	}
}

/**
 * Container of features, which supports methods to add or remove.
 *
 * Data structure maps to a site with pages and related images.
 */
class ProfileFeatureSet
{
	function __construct($profile_id, $username)
	{
		$this->username = $username;
		$this->profile_id = $profile_id;
		$profile = new entity($this->profile_id);
		$site = $profile->get_owner();
		$this->site_id = $site->id();
		$this->features = $this->get_features();
	}
	
	/**
	 *
	 */	
	function get_features()
	{
		$es = new entity_selector( $this->site_id );
		$es->add_type(id_of('image'));
		$es->add_right_relationship($this->profile_id, relationship_id_of('profile_to_feature_image'));
		$feature_entities = $es->run_one();
		if (!empty($feature_entities))
		{
			foreach ($feature_entities as $k => $v)
			{
				$features[$k] = new ProfileFeature($v);
			}
		}
		return (!empty($features)) ? $features : array();
	}
	
	/**
	 * Setup a disco form with appropriate callbacks - return shim object with methods for the view.
	 */
	function get_add_section()
	{
		$form = new disco();
		$form->form_enctype = 'multipart/form-data';
		$params = array('crop_ratio' => 4/2,
					    'require_crop' => true,
					    'max_width' => 800,
					    'max_height' => 400,
			  			);	
		$form->add_element( 'feature', 'reasonImageUploadCroppable', $params );			
		$shim = new ProfileFeatureShim('feature', $form, $this->site_id, $this->profile_id, $this->username);
		$shim->set_display_name('Add an Image');
		
		// these are set very low (basically not active) because error triggers aren't working.
		$form->add_callback(array($shim, 'check_width'), 'run_error_checks');
		$form->add_callback(array($shim, 'check_height'), 'run_error_checks');
		$form->add_callback(array($shim, 'save_image'), 'process');
		$form->add_callback(array($shim, 'post_save_redirect'), 'where_to');
		return $shim;
	}
	
	/**
	 * Setup a disco form with appropriate callbacks - return shim object with methods for the view.
	 */
	function get_edit_section($id)
	{
		$form = new disco();
		$form->form_enctype = 'multipart/form-data';
		$params = array('crop_ratio' => 4/2,
					    'require_crop' => true,
					    'max_width' => 800,
					    'max_height' => 400,
			  			);	
		$form->add_element( 'feature', 'reasonImageUploadCroppable', $params );	
		$shim = new ProfileFeatureShim('feature', $form, $this->site_id, $this->profile_id, $this->username, $id);
		$shim->set_display_name('Edit Feature');
		
		// these are set very low (basically not active) because error triggers aren't working.
		//$shim->set_min_width(10);
		//$shim->set_min_height(10);
		//$form->add_callback(array($shim, 'verify_min_width_and_height'), 'run_error_checks');
		$form->add_callback(array($shim, 'check_width'), 'run_error_checks');
		$form->add_callback(array($shim, 'check_height'), 'run_error_checks');
		$form->add_callback(array($shim, 'save_image'), 'process');
		$form->add_callback(array($shim, 'post_save_redirect'), 'where_to');
		return $shim;
	}
	
	function id_is_in_featureset($id)
	{
		$features = $this->get_features();
		return (isset($features[$id]));
	}
}

/**
 * This could replace the image upload form used for profile images likely
 */
class ProfileFeatureShim
{
	var $form;
	var $element_name;
	var $site_id;
	var $profile_id;
	var $username;
	var $min_width;
	var $min_height;
	
	function __construct($element_name, $form, $site_id, $profile_id, $username, $existing_id = NULL)
	{
		$this->element_name = $element_name;
		$this->form = $form;
		$this->site_id = $site_id;
		$this->profile_id = $profile_id;
		$this->username = $username;
		$this->id = $existing_id;
	}
	
	function set_display_name($name)
	{
		$this->form->set_display_name( $this->element_name, $name );
	}
	
	function set_min_width($num)
	{
		$this->min_width = $num;
	}
	
	function set_min_height($num)
	{
		$this->min_height = $num;
	}
	
	/**
	 * @todo implement me
	 */
	function set_crop($x, $y)
	{
	
	}
	
	/**
	 * @todo this should not depend on imagemagick.
	 */
	function verify_min_width_and_height(&$disco)
	{
		
		if($upload = $disco->get_element($this->element_name))
		{
			if ($info = get_dimensions_image_magick($upload->tmp_full_path))
			{	
				if (isset($this->min_width) && ($info['width'] < $this->min_width))
				{
					$disco->set_error($this->element_name,'Your image is not wide enough; it needs to be at least '.$this->min_width.' pixels in width.');
				}
				if (isset($this->min_height) && ($info['height'] < $this->min_height))
				{
					$disco->set_error($this->element_name,'Your image is not tall enough; it needs to be at least '.$this->min_width.' pixels in height.');
				}
			}
		}
	}
	
	function check_width($disco)
	{
		$feature = $disco->get_element('feature');
		$info = get_dimensions_image_magick($feature->tmp_full_path);
		if ($info['width'] < 800)
		{
			$disco->set_error('feature', 'Your image is not wide enough (it is ' . $info['width']. ' - it must be 800 pixels.');
		}
	}
	
	function check_height($disco)
	{
		$feature = $disco->get_element('feature');
		$info = get_dimensions_image_magick($feature->tmp_full_path);
		if ($info['height'] < 400)
		{
			$disco->set_error('feature', 'Your image is not tall enough - it must be 400 pixels.');
		}
	}
	
	function save_image(&$disco)
	{
		$image = $disco->get_element($this->element_name);
		$profile_config = profile_get_config();
		$this->profile_entity = new entity($this->profile_id);
		$username = $this->username;
		$profile = new $profile_config->person_class($username);

		// Make sure we have an image to start with
		if( !empty($image->tmp_full_path) AND file_exists( $image->tmp_full_path ) )
		{
			// Create a new entity for the image
			$owner = get_user_id('causal_agent');
			$values['new'] = '0';
			$values['author'] = $profile->get_first_ds_value('ds_fullname');
			$values['description'] = 'Feature Image';
			$values['no_share'] = '0';
			$values['keywords'] = $profile->get_first_ds_value('ds_fullname').',' . $this->username;
			
			if (!$this->id)
			{
				if ($id = reason_create_entity( $this->site_id, id_of('image'), $owner, 'Feature Image', $values))
				{
					// The image content manager contains all the logic for processing
					// various image types and creating thumbnails, so we'll just 
					// instantiate one and make it do our bidding.
					$im = new ImageManager();
					$im->load_by_type( id_of('image'), $id, $owner );
					
					$im->handle_standard_image($id, $image);
					$im->handle_original_image($id, $image);		
						
					$im->create_default_thumbnail($id);
					
					// Pull the values generated in the content manager
					// and save them to the entity
					$values = array();
					foreach($im->get_element_names() as $element_name)
					{
						$values[ $element_name ] = $im->get_value($element_name);
					}
					reason_update_entity( $id, $owner, $values, false );
					
					// Relate the new image to the profile
					create_relationship( $this->profile_id, $id, relationship_id_of('profile_to_feature_image'));
					return true;
				}
				else
				{
					trigger_error('Failed to create image entity.');		
				}
			}
			else
			{
				$id = $this->id;
				// The image content manager contains all the logic for processing
				// various image types and creating thumbnails, so we'll just 
				// instantiate one and make it do our bidding.
				$im = new ImageManager();
				$im->load_by_type( id_of('image'), $id, $owner );
				
				$im->handle_standard_image($id, $image);
				$im->handle_original_image($id, $image);		
					
				$im->create_default_thumbnail($id);
					
				// Pull the values generated in the content manager
				// and save them to the entity
				$values = array();
				foreach($im->get_element_names() as $element_name)
				{
					$values[ $element_name ] = $im->get_value($element_name);
				}
				reason_update_entity( $id, $owner, $values, false );
			}
		} 
		else 
		{
			trigger_error('No path to image: '.$image->tmp_full_path);
		}
		return false;
	}
	
	function post_save_redirect()
	{
		$redirect = carl_make_redirect(array('edit_section' => ''));
		return $redirect;
	}
	
	function run()
	{
		ob_start();
		$this->form->run();
		$str = ob_get_contents();
		ob_end_clean();
		return $str;
	}
	// other methods the view may want to use?
}

/**
 * Representation of a feature with save and update methods.
 */
class ProfileFeature
{
	function __construct($entity)
	{
		$this->entity = $entity;
	}
	
	function get_entity_id()
	{
		return $this->entity_id;
	}

	/**
	 * Get site_id for the guid - create if necessary
	 *
	 * @todo should causal agent be the one?
	 */
	function get_profile_site_id()
	{
		
	}
	
	/**
	 * @param $image disco image object
	 * @return image entity
	 *
	 * @todo update existing entity
	 */
	function save_image($image, $description = "")
	{
		if( !empty($image->tmp_full_path) AND file_exists( $image->tmp_full_path ) )
		{
			// Create a new entity for the image
			$owner = get_user_id('causal_agent');
			$values['new'] = '0';
			$values['description'] = (!empty($description)) ? $description: '(No Description)';
			$values['no_share'] = '0';
			
			// Find a site - create site if needed
			$site_id = $this->get_profile_site_id();
			
			if ($id = reason_create_entity( $site_id, id_of('image'), $owner, 'Profile Image', $values))
			{
				// The image content manager contains all the logic for processing
				// various image types and creating thumbnails, so we'll just 
				// instantiate one and make it do our bidding.
				$im = new ImageManager();
				$im->load_by_type( id_of('image'), $id, $owner );
				
				$im->handle_standard_image($id, $image);
				$im->handle_original_image($id, $image);		
					
				$im->create_default_thumbnail($id);
				
				// Pull the values generated in the content manager
				// and save them to the entity
				$values = array();
				foreach($im->get_element_names() as $element_name)
				{
					$values[ $element_name ] = $im->get_value($element_name);
				}
				reason_update_entity( $id, $owner, $values, false );
				return true;
			}
			else
			{
				trigger_error('Failed to create image entity.');		
			}
		} 
		else 
		{
			trigger_error('No path to image: '.$image->tmp_full_path);
		}
		return false;
	}
}

?>