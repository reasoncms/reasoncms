<?php
/**
 * Default Reason search module
 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'SearchModule';

	/**
	 * Reason Search Module
	 * 
	 * Support for default_text and header_text from parameters added 11/28/07 - Nathan White
	 *
	 * @package reason
	 * @subpackage modules
	 */
	class SearchModule extends DefaultMinisiteModule
	{
		var $default_text;
		var $header_text;
		var $acceptable_params = array('header_text' => NULL, 'default_text' => NULL);
		
		function init( $args = array() )
		{
			$this->default_text = (isset($this->params['default_text'])) 
								  ? $this->params['default_text'] 
								  : 'Search ' . reason_htmlspecialchars(strip_tags($this->parent->site_info->get_value('name')));
			
			$this->header_text = (isset($this->params['header_text'])) ? $this->params['header_text'] : '';
		}
		
		function has_content()
		{
			if( $this->parent->site_info->get_value('base_url') 
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
			echo '<form method="'.REASON_SEARCH_FORM_METHOD.'" action="'.REASON_SEARCH_ENGINE_URL.'" name="search" class="searchForm">'."\n";
			echo '<input type="text" name="'.REASON_SEARCH_FORM_INPUT_FIELD_NAME.'" size="'.min(strlen($this->default_text), 40).'" value="'.$this->default_text.'" onfocus=\'if(this.value=="'.$this->default_text.'") {this.value="";}\' onblur=\'if(this.value=="") {this.value="'.$this->default_text.'";}\' class="searchInputBox" id="minisiteSearchInput" />'."\n";
			echo '<a href="javascript:document.search.submit()" class="searchSubmitLink">Go</a>'."\n";
			echo '<noscript><input name="go" type="submit" value="go" /></noscript>'."\n";
			echo '<input type="hidden" name="'.REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME.'" value="http://'.REASON_HOST . $this->parent->site_info->get_value('base_url').'" />'."\n";
			echo REASON_SEARCH_FORM_HIDDEN_FIELDS."\n";
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
