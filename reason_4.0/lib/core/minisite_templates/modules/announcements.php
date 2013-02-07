<?php
	/**
	 * Announcements
	 *
	 * Provides a way to add a prominent announcement to an entire site via the Master Admin
	 *
	 * @package reason
	 * @subpackage minisite_modules
	 */
	 
	/**
	 * Include the base module & register with Reason
	 */
	reason_include_once( 'minisite_templates/modules/default.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AnnouncementsModule';
	/**
	 * Announcements Module
	 * Provides a way to add a prominent announcement to an entire site via the Master Admin
	 * Uses the allowable relationship site_to_announcement_blurb
	 */
	class AnnouncementsModule extends DefaultMinisiteModule
	{
		/**
		* Blurbs array
		* @var array an array of blurb entities
		*/
		var $blurbs;
		/**
		* Initialize the module
		* grabs all the blurbs associated with the current site
		* @param $args array
		*/
		function init( $args = array() )
		{
			parent::init($args);
			$master_admin_id = id_of('master_admin');
			$es = new entity_selector($master_admin_id);
			$es->limit_tables();
			$es->limit_fields();
			$es->add_type(id_of('text_blurb'));
			$es->add_right_relationship($this->site_id, relationship_id_of('site_to_announcement_blurb'));
			$es->set_env('site',$master_admin_id);
			$this->blurbs = $es->run_one();
		}
		/**
		* Tells template whether module has content or not
		* @return boolean $has_content
		*/
		function has_content()
		{
			if( !empty( $this->blurbs ) )
				return true;
			else
				return false;
		}
		/**
		* Generates the html for the site announcements
		*/
		function run()
		{
			echo '<div id="announcements">'."\n";
			foreach($this->blurbs as $blurb)
			{
				echo '<div class="announcement">'.$blurb->get_value('content').'</div>'."\n";
			}
			echo '</div>'."\n";
		}
		
		function get_documentation()
		{
			if($this->has_content())
			{
				return '<p>Sitewide notices are displayed here. (Sitewide notices set up by Reason administrators)</p>';
			}
			return false;
		}
	}
?>
