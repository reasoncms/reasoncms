<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
	
 /**
  * Include base class
  */
	reason_include_once( 'minisite_templates/modules/default.php' );

	/**
	 * Register the class so the template can instantiate it
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'atozModule';

	/**
	 * A minisite module that lists live sites alphabetically by keywords
	 */
	class atozModule extends DefaultMinisiteModule
	{
		var $sites = array();
		var $non_reason_sites = array();
		var $alpha = array();
		
		function init( $args = array() )	 // {{{
		{
			parent::init( $args );

			// find all the sites
			$es = new entity_selector();
			$es->description = "Getting all live sites";
			$es->add_type( id_of( 'site' ) );
			$es->add_relation('site.site_state = "Live"');
			$this->sites = $es->run_one();

			// find all non-reason sites
			$es2 = new entity_selector();
			$es2->description = 'Getting all non-reason sites';
			$es2->add_type( id_of( 'non_reason_site_type' ) );
			$this->non_reason_sites = $es2->run_one();
		} // }}}
		function has_content() // {{{
		{
			if( empty($this->sites) && empty($this->non_reason_sites) )
				return false;
			else
				return true;
		} // }}}
		function run() // {{{
		{
			foreach( $this->sites as $site )
			{
				$this->add_to_alpha($site);
			}
			foreach( $this->non_reason_sites as $site )
			{
				$this->add_to_alpha($site);
			}

			ksort( $this->alpha );

			echo '<div id="atoz">'."\n";
			echo '<p><span class="jumpTo">Jump to:</span> ';
			foreach($this->alpha as $keyletter => $keywords )
			{
				echo '<a href="#'.strtolower($keyletter).'">'.$keyletter.'</a> ';
			}
			echo '</p>'."\n";
			echo '<ul>'."\n";
			foreach( $this->alpha as $keyletter => $keywords )
			{
				ksort( $keywords );
				echo "\t".'<li><a name="'.strtolower($keyletter).'"></a><span class="letter">'.$keyletter.'</span><ul>'."\n";
				foreach( $keywords as $word => $sites )
				{
					asort( $sites );
					echo "\t\t".'<li><strong>'.$word.':</strong>';
					$insert_string = '';
					foreach( $sites as $base_url=>$name )
					{
						$title_attr = '';
						$title = $this->get_title($word, $name);
						if(!empty($title))
							$title_attr = ' title="'.$title.'"';
						echo $insert_string.' <a href="'.$base_url.'"'.$title_attr.'>';
						echo $name;
						echo '</a>';
						$insert_string = ',';
					}
					echo '</li>'."\n";
				}
				echo "\t".'</ul></li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
		} // }}}
		function get_title($word, $name)
		{
			if(false !== stripos($name, $word))
				return '';
			else
				return reason_htmlspecialchars($word) . ' &#8211; ' . reason_htmlspecialchars($name);
		}
		function add_to_alpha($site)
		{
			if( $site->get_value( 'keywords' ) )
			{
				$keywords = explode( ', ',$site->get_value( 'keywords' ) ); 
				foreach( $keywords as $word )
				{
					$letter = strtoupper( substr( $word,0,1 ) );
					if($site->get_value('base_url'))
						$url = $site->get_value('base_url');
					else
						$url = $site->get_value('url');
					$this->alpha[ $letter ][ strtolower( $word ) ][$url] = $site->get_value('name');
				}
			}
		}
	}
?>
