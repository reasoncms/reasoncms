<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/url_utils.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'TextOnlyToggleModule';

	class TextOnlyToggleModule extends DefaultMinisiteModule
	{
		function has_content()
		{
			return true;
		}
		function run()
		{
			if (!empty($this->parent->textonly))
			{
				echo '<p class="'.$this->generate_class().'"><a href="'.$this->generate_link().'">View full graphics version of this page</a></p>'."\n";
			}
			else
			{
				echo '<p class="'.$this->generate_class().'"><a href="'.$this->generate_link().'"><span class="textOnly">Text Only/<span class="tiny"> </span>Printer-Friendly</span></a></p>'."\n";
			}
		}
		function generate_class()
		{
			if (!empty($this->parent->textonly))
				return 'fullGraphicsLink';
			else
				return 'textOnlyLink smallText';
		}
		function generate_link()
		{
			if ( empty( $this->parent->textonly )) return carl_make_link(array('textonly' => 1));
			else return carl_make_link(array('textonly' => ''));
		}
		
		function get_documentation()
		{
			return '<p>Displays a link to switch between normal mode and text-only/printer-friendly mode</p>';
		}
	}
?>
