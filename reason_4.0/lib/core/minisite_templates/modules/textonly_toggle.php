<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

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
				echo '<p class="'.$this->generate_class().'"><a href="'.$this->generate_link().'">Text Only/<span class="tiny"> </span>Printer-Friendly</a></p>'."\n";
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
			$link = '?';
			foreach ($_GET as $var=>$str)
			{
				if ( !empty( $this->parent->textonly ) && $var == 'textonly' && !empty($str) )
					continue;
				if ( $var == 'page_id' || $var == 'site_id' )
					continue;
				if( !empty($str) && is_string($str) )
				{
					$str = urlencode($str);
				}
				$link .= $var . '=' . $str . '&amp;';
			}
			if ( empty( $this->parent->textonly ) )
				$link .= 'textonly=1';
			return $link;
		}
	}
?>
