<?php 
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LiveSitesModule';

	/**
	 * A minisite module that lists all the live sites hosted by this Reason instance
	 */
	class LiveSitesModule extends DefaultMinisiteModule
	{
		
		function init ( $args = array() )	 // {{{
		{
			parent::init( $args );

			$s = new entity_selector();
			$s->add_type(id_of('site'));
			$s->set_order( 'entity.name' );
			$s->add_relation('site.site_state = "Live"');
			$this->site_count = $s->get_one_count();
			$this->sites = $s->run_one();
			
			//pray($this->sites);
		} // }}}
		function has_content() // {{{
		{
			if( empty($this->sites) )
			{
				return false;
			}
			else
				return true;
		} // }}}
		function run() // {{{
		{
			/* If the page has no entries, say so */
			if ( empty($this->sites) )
			{
				echo '<p>There are no live sites.</p>';
			}
			/* otherwise, list them */
			else
			{
				echo '<p>There are '.$this->site_count.' live sites currently using Reason: </p>'."\n";
				$this->list_sites();
			}
		} // }}}
		function list_sites()
		{
			echo '<ul class="siteList">'."\n";
			foreach( $this->sites AS $site )
			{
				echo '<li>';
				if( $site->get_value( 'base_url' ) )
					echo '<a href="'.$site->get_value( 'base_url' ) .'">'.$site->get_value('name').'</a><br />';
				else echo $site->get_value('name');
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
	}

?>
