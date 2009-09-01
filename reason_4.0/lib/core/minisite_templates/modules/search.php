<?php
/**
 * Default Reason search module
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the parent class and register the module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'SearchModule';

	/**
	 * Reason Search Module
	 * 
	 * Support for default_text and header_text from parameters added 11/28/07 - Nathan White
	 */
	class SearchModule extends DefaultMinisiteModule
	{
		var $default_text;
		var $header_text;
		var $acceptable_params = array('header_text' => NULL, 'default_text' => NULL, 'site_unique_name'=> NULL);
		var $_search_site;
		
		function init( $args = array() )
		{
			if(isset($this->params['site_unique_name']))
			{
				$this->_search_site = new entity(id_of($this->params['site_unique_name']));
			}
			else
			{
				$this->_search_site = $this->parent->site_info;
			}
			$this->default_text = (isset($this->params['default_text'])) 
								  ? $this->params['default_text'] 
								  : 'Search ' . reason_htmlspecialchars(strip_tags($this->_search_site->get_value('name')));
			
			$this->header_text = (isset($this->params['header_text'])) ? $this->params['header_text'] : '';
		}
		
		function has_content()
		{
			if( $this->_search_site->get_value('base_url') 
				&& defined('REASON_SEARCH_ENGINE_URL') 
				&& REASON_SEARCH_ENGINE_URL != '')
				return true;
			return false;
		}
		
		function run()
		{
			if (!empty($this->header_text))
			{
				echo '<h3>' . $this->header_text .'</h3>';
			}
			$this->run_form_open();
			$this->run_search_input_field();
			$this->run_script_go();
			$this->run_noscript_go();
			$this->run_restrict();
			$this->run_form_hidden_fields();
			$this->run_form_close();
		}
		function run_form_open()
		{
			echo '<form method="'.REASON_SEARCH_FORM_METHOD.'" action="'.REASON_SEARCH_ENGINE_URL.'" name="search" class="searchForm">'."\n";
		}
		function run_search_input_field()
		{
			echo '<input type="text" name="'.REASON_SEARCH_FORM_INPUT_FIELD_NAME.'" size="'.min(strlen($this->default_text), 40).'" value="'.$this->default_text.'" onfocus=\'if(this.value=="'.$this->default_text.'") {this.value="";}\' onblur=\'if(this.value=="") {this.value="'.$this->default_text.'";}\' class="searchInputBox" id="minisiteSearchInput" />'."\n";
		}
		function run_script_go()
		{
			echo '<a href="javascript:document.search.submit()" class="searchSubmitLink">Go</a>'."\n";
		}
		function run_noscript_go()
		{
			echo '<noscript><input name="go" type="submit" value="go" /></noscript>'."\n";
		}
		
		function run_restrict()
		{
			echo '<input type="hidden" name="'.REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME.'" value="http://'.REASON_HOST . $this->_search_site->get_value('base_url').'" />'."\n";
		}
		function run_form_hidden_fields()
		{
			echo REASON_SEARCH_FORM_HIDDEN_FIELDS."\n";
		}
		function run_form_close()
		{
			echo '</form>';
		}
		
		function get_documentation()
		{
			if($this->has_content())
			{
				return '<p>Presents a box for searching this site</p>';
			}
			else
			{
				return false;
			}
		}
	}
?>
