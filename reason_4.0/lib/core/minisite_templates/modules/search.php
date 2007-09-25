<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'SearchModule';

	class SearchModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			if( $this->parent->pages->site_info->get_value('base_url') && defined('REASON_SEARCH_ENGINE_URL') && REASON_SEARCH_ENGINE_URL != '')
				return true;
			else
				return false;
		}
		function run()
		{
			$siteName = reason_htmlspecialchars(strip_tags($this->parent->pages->site_info->get_value('name')));
			$defaultText = ' Search ' . $siteName;
			$defaultTextLength = strlen($defaultText);
			if($defaultTextLength > 40)
			{
				$defaultTextLength = 40;
			}
			echo '<form method="'.REASON_SEARCH_FORM_METHOD.'" action="'.REASON_SEARCH_ENGINE_URL.'" name="search" class="searchForm">'."\n";
			echo '<input type="text" name="'.REASON_SEARCH_FORM_INPUT_FIELD_NAME.'" size="'.$defaultTextLength.'" value="'.$defaultText.'" onfocus=\'if(this.value=="'.$defaultText.'") {this.value="";}\' onblur=\'if(this.value=="") {this.value="'.$defaultText.'";}\' class="searchInputBox" id="minisiteSearchInput" />'."\n";
			echo '<a href="javascript:document.search.submit()" class="searchSubmitLink">Go</a>'."\n";
			echo '<noscript><input name="go" type="submit" value="go" /></noscript>'."\n";
			echo '<input type="hidden" name="'.REASON_SEARCH_FORM_RESTRICTION_FIELD_NAME.'" value="http://'.REASON_HOST . $this->parent->pages->site_info->get_value('base_url').'" />'."\n";
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
