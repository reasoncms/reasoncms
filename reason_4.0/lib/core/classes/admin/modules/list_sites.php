<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * Site List Module
	 * A module that lists live sites (as well as not live sites if it's run by an admin)
	 * @author Ben Cochran
	 * @date 2006-10-31
	 */
	class ListSitesModule extends DefaultModule // {{{
	{
		var $live_sites_list = array();
		var $not_live_site_list = array();
		var $ls_count;
		var $nls_count;
		
		function ViewUsersModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * Sets up the entity selectors and grabs the site lists
		 * 
		 * @return void
		 */
		function init() // {{{
		{
			parent::init();
			$this->site = new entity( $this->admin_page->site_id );
			$this->admin_page->title = 'Site Listing';
			$lm = new entity_selector();
			$lm->add_type(id_of('site'));
			$lm->set_order( 'entity.name' );
			$lm->add_relation('site.site_state = "Live"');
			$this->ls_count = $lm->get_one_count();
			$this->live_sites_list = $lm->run_one();
			
			if(reason_user_has_privs( $this->admin_page->user_id, 'view_sensitive_data') )
			{
				$nm = new entity_selector();
				$nm->add_type(id_of('site'));
				$nm->set_order( 'entity.name' );
				$nm->add_relation('site.site_state != "Live"');
				$this->nls_count = $nm->get_one_count();
				$this->not_live_site_list = $nm->run_one();
			}
			
		} // }}}
		/**
		 * Lists the sites, the non-live list depending on admin role
		 * 
		 * @return void
		 */
		function run() // {{{
		{			
			echo '<h2>'.$this->ls_count.' Live Sites</h2>'."\n";
			$this->list_minisites( $this->live_sites_list );
			
			/* Non-live sites are listed only if viewed by an admin */
			if(reason_user_has_privs( $this->admin_page->user_id, 'view_sensitive_data'))
			{
				
				echo '<h2>'.$this->nls_count.' Non-Live Sites</h2>'."\n";
				$this->list_minisites( $this->not_live_site_list );
				
			}
		} // }}}
		
		/**
		 * Actually displays the site list with links to each site
		 * @param array $minisites array of minisite objects
		 * @return void
		 */
		function list_minisites( $minisites )
		{
			echo '<ul>'."\n";
			foreach( $minisites AS $m )
			{
				echo '<li>';
				if( $m->get_value( 'base_url' ) )
					echo '<a href="'.$m->get_value( 'base_url' ).'">'.$m->get_value('name').'</a><br />';
				else echo $m->get_value('name');
				echo '</li>'."\n";
			}
			echo '</ul>'."\n";
		}
		
	} // }}}
?>