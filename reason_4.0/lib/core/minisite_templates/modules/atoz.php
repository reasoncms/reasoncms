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

			ksort( $this->alpha_sites );

			echo '<div id="atoz">'."\n";
			echo '<p class="alpha"><span class="jumpTo label">Jump to:</span> ';
			foreach($this->alpha_sites as $keyletter => $keywords )
			{
				echo '<a href="#'.strtolower($keyletter).'">'.$keyletter.'</a> ';
			}
			echo '</p>'."\n";
			echo '<ul>'."\n";
			foreach( $this->alpha_sites as $keyletter => $keywords )
			{
				ksort( $keywords );
				echo "\t".'<li><a name="'.strtolower($keyletter).'"></a><span class="letter">'.$keyletter.'</span><ul>'."\n";
				foreach( $keywords as $word => $sites )
				{
					echo "\t\t".'<li><strong>'.$word.':</strong>';
					$insert_string = '';
					$counts = array();
					foreach( $sites as $id => $site )
					{
						$count = 0;
						foreach($site->get_value('az_keywords') as $key)
						{
							if($word == strtolower($key))
							{
								$count++;
							}
						}
						$counts[$count][$id] = $site;	
					}
					//pray($counts);
					krsort($counts);
					//echo count($counts);
					foreach($counts as $count => $count_sites)
					{
						entity_sort( $count_sites );
						foreach($count_sites as $site)
						{
							$title_attr = '';
							$title = $this->get_title($word, $site->get_value('name'));
							if(!empty($title))
								$title_attr = ' title="'.$title.'"';
							echo $insert_string.' <a href="'.$site->get_value('az_url').'"'.$title_attr.$site->get_language_attribute().'>';
							echo $site->get_value('name');
							echo '</a>';
							$insert_string = ',';
						}
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
				$keywords = $this->get_keywords_array($site); 
				$site->set_value('az_keywords', $keywords);
				if($site->get_value('base_url'))
					$url = $site->get_value('base_url');
				else
					$url = $site->get_value('url');
				$site->set_value('az_url', $url);
				$site->set_value('az_url', $url);

				foreach( $keywords as $word )
				{
					$letter = strtoupper( substr( $word,0,1 ) );
					$this->alpha[ $letter ][ strtolower( $word ) ][$url] = $site->get_value('name'); // for backwards compatibility
					$this->alpha_sites[ $letter ][ strtolower( $word ) ][$site->id()] = $site;
				}
			}
		}
		function get_keywords_array($site)
		{
			$keywords = array();
			if( $site->get_value( 'keywords' ) )
			{
				$keywords = explode( ',', $site->get_value( 'keywords' ) );
				$keywords = array_map('trim', $keywords);
			}
			return $keywords;
		}
	}
?>
