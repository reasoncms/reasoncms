<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ContactBlurbModule';
	
	class ContactBlurbModule extends BlurbModule
	{
		function run() // {{{
		{
			$i = 0;
			foreach( $this->blurbs as $blurb )
			{
				$i++;
	
				if (preg_match("/[Cc]ontact [Ii]nformation/", $blurb->get_value('name'))){
					echo '<div class="contact-info">'."\n";
					echo '<h2>Contact Information</h2>'."\n";
					echo $blurb->get_value('content');
				}
			}
			echo '</div>'."\n";
		} 
	}
?>
