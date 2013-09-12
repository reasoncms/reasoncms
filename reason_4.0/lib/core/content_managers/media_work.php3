<?php
/**
 * @package reason
 * @subpackage content_managers
 */
reason_include_once('content_managers/default.php3');
reason_include_once('classes/media/factory.php');
require_once(SETTINGS_INC.'media_integration/media_settings.php');
 
/**
 * Register the content manager with Reason
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'av_handler';

/**
 * A content manager for Media Works.
 */
class av_handler extends ContentManager
{	
	var $fields_to_remove = array('rating', 'standalone');
	var $field_order = array ('name', 'datetime', 'author', 'description', 'keywords', 'content','transcript_status', 'rights_statement', 'show_hide');
	var $admissions_field_order = array ('name', 'datetime', 'author', 'description', 'keywords', 'content','rating','transcript_status', 'rights_statement', 'show_hide');

	/**
	 * The content manager uses logic to figure out if it has all the information
	 * it needs to manage the media (e.g. is Reason set to manage the media,
	 * do we have a class to do it, does the class exist)
	 * If those conditions are met the content manager changes the value of
	 * this variable to true.
	 * @var bool
	 */
	var $manages_media = true;
	
	var $manager_modifier;
		
	var $inited = false;
	
	var $choose_integration_library = false;
	
	protected $integration_library;
		
	function init( $externally_set_up = false )
	{	
		// init() is called 3 times, but we only care about one of the calls here.
		if (!$this->inited)
		{	
			$this->inited = true;
			// Determine whether or not an integration library selector needs to be shown.
			// If a library is passed in the request variables, set the integration library
			// to that library.
			if ($this->is_new_entity() && !$this->get_value('integration_library'))
			{
				// if a valid integration library string was passed in the url, use it.'
				if (!empty($_REQUEST['library']) && in_array($_REQUEST['library'], $GLOBALS['NEW_MEDIA_WORK_INTEGRATION_LIBRARIES']))
				{
					$this->set_value('integration_library', addslashes($_REQUEST['library']));
				}
				else
				{
					$num_libraries = count($GLOBALS['NEW_MEDIA_WORK_INTEGRATION_LIBRARIES']);
					if ($num_libraries == 1) // use default integration
					{
						$this->set_value('integration_library', current($GLOBALS['NEW_MEDIA_WORK_INTEGRATION_LIBRARIES']));
					} 
					elseif ($num_libraries >= 2)
					{
						$this->choose_integration_library = true;
					}
					// if there are no integration libraries, just use the default non-integration
				}
			}
			if (!$this->choose_integration_library)
			{
				$this->manager_modifier = MediaWorkFactory::media_work_content_manager_modifier($this->get_value('integration_library'));
				if ($this->manager_modifier)
				{
					$this->integration_library = $this->get_value('integration_library');
					$this->manager_modifier->set_content_manager($this);
					$this->manager_modifier->set_head_items($this->head_items);
					$this->manager_modifier->process();
					$this->manager_modifier->run_error_checks();
				}
			}
		}
		parent::init();
	}
	
	function alter_data()
	{
		/* Choose some sensible security defaults */
		$this->change_element_type('salt','cloaked');
		$this->change_element_type('integration_library','protected');
		if (!$this->choose_integration_library)
		{
			if ($this->manager_modifier)
			{
				$this->manager_modifier->alter_data();
			}
		}
	}
	
	function run()
	{
		if ($this->choose_integration_library) // offer links to choose integration libraries
		{
			$this->show_integration_library_selection();
		}
		else // otherwise, carry on as usual
		{
			if ($this->manager_modifier)
			{
				parent::run();
			}
			else
			{
				echo '<p>Content manager unavailable for '.$this->get_value('integration_library').'-integrated media work.</p>';
			}
		}
	}
	
	/**
	 * This function should echo out markup used to display integration library selection to 
	 * the user.
	 * 
	 * Either implement this method or override it in the child class.
	 */
	function show_integration_library_selection()
	{
		echo '<h5>Please select an integration library for your new Media Work.</h5>'."\n";
		echo '<ul>'."\n";
		foreach ($GLOBALS['NEW_MEDIA_WORK_INTEGRATION_LIBRARIES'] as $key => $val)
		{
			echo '<li>';
			$url = carl_make_link(array('library' => $val));
			echo '<a href="'.$url.'">'.strtoupper($val).'</a>';
			echo '</li>';
		}
		echo '</ul>'."\n";
	}
	
	function _parent_init()
	{
		parent::init();
	}
}
?>