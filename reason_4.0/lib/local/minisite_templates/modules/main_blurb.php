<?php
	reason_include_once( 'minisite_templates/modules/blurb.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MainBlurbModule';
	
	class MainBlurbModule extends BlurbModule
	{
		function run() // {{{
		{
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
			{
				echo '<aside class="news group">'."\n";
				echo '<header class="blue-stripe"><h1><span>Luther Connections</span></h1></header>'."\n";
			}
						
			echo '<div class="main-blurb">'."\n"; 
			$i = 0;
			foreach( $this->blurbs as $blurb )
			{
				$i++;
	
				if (!preg_match("/[Cc]ontact [Ii]nformation/", $blurb->get_value('name')))
					echo $blurb->get_value('content');
			}
			echo '</div>'."\n";
			
			if ($this->cur_page->get_value( 'custom_page' ) == 'luther2010_public_information')
			{
				echo '</aside> <!-- class="news group" -->'."\n";
			}				
		} 
		
		function has_content()
		{
			if(!empty($this->blurbs))
			{
				foreach($this->blurbs as $blurb)
				{
					if (!preg_match("/[Cc]ontact [Ii]nformation/", $blurb->get_value('name')))
					{
						return true;
					}
				}
			
			}
			return false;
		}
	}
?>