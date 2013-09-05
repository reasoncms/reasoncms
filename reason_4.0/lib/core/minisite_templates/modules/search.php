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
		var $acceptable_params = array(
			'header_text' => NULL,
			'default_text' => NULL,
			'site_unique_name'=> NULL,
			'search_form_method'=> NULL,
			'search_engine_url'=> NULL,
			'input_field_name'=> NULL,
			'restriction_field_name'=> NULL,
			'hidden_fields' => NULL,
		);
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
				&& $this->get_seach_engine_url())
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
		function get_seach_form_method()
		{
			if(!empty($this->params['search_form_method']))
				return $this->params['search_form_method'];
			if(defined('REASON_SEARCH_FORM_METHOD'))
				return REASON_SEARCH_FORM_METHOD;
			return 'get';
		}
		function get_seach_engine_url()
		{
			if(!empty($this->params['search_engine_url']))
				return $this->params['search_engine_url'];
			if(defined('REASON_SEARCH_ENGINE_URL'))
				return REASON_SEARCH_ENGINE_URL;
			return 'http://www.google.com/search';
		}
		function get_seach_form_input_field_name()
		{
			if(!empty($this->params['input_field_name']))
				return $this->params['input_field_name'];
			if(defined('REASON_SEARCH_FORM_INPUT_FIELD_NAME'))
				return REASON_SEARCH_FORM_INPUT_FIELD_NAME;
			return 'q';
		}
		function get_seach_form_restriction_field_name()
		{
			if(!empty($this->params['restriction_field_name']))
				return $this->params['restriction_field_name'];
			if(defined('REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME'))
				return REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME;
			return 'as_sitesearch';
		}
		function get_seach_form_hidden_fields()
		{
			if(isset($this->params['hidden_fields']) && $this->params['hidden_fields'] !== NULL)
				return $this->params['hidden_fields'];
			if(defined('REASON_SEARCH_FORM_HIDDEN_FIELDS'))
				return REASON_SEARCH_FORM_HIDDEN_FIELDS;
			return '';
		}
		function run_form_open()
		{
			echo '<form method="'.$this->get_seach_form_method().'" action="'.$this->get_seach_engine_url().'" name="search" class="searchForm">'."\n";
		}
		function run_search_input_field()
		{
			echo '<input type="text" name="'.$this->get_seach_form_input_field_name().'" size="'.min(strlen($this->default_text), 40).'" value="'.$this->default_text.'" onfocus=\'if(this.value=="'.$this->default_text.'") {this.value="";}\' onblur=\'if(this.value=="") {this.value="'.$this->default_text.'";}\' class="searchInputBox" id="minisiteSearchInput" />'."\n";
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
			// Google doesn't include subpages if there's a trailing slash
			$url = rtrim($this->_search_site->get_value('base_url'),'/');
			echo '<input type="hidden" name="'.$this->get_seach_form_restriction_field_name().'" value="'.REASON_HOST . $url.'" />'."\n";
		}
		function run_form_hidden_fields()
		{
			echo $this->get_seach_form_hidden_fields()."\n";
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
