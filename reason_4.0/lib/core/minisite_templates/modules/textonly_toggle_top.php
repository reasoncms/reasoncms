<?php

	reason_include_once( 'minisite_templates/modules/textonly_toggle.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'TextOnlyToggleTopModule';

	class TextOnlyToggleTopModule extends TextOnlyToggleModule
	{
		function generate_class()
		{
			if (!empty($this->parent->textonly))
				return 'fullGraphicsLink';
			else
				return 'hide';
		}
	}
?>
