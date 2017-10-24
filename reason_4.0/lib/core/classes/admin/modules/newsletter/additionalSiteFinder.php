<?php

reason_include_once( 'classes/string_to_sites.php' );
reason_include_once('classes/event_helper.php');

/**
 * Class additionalSiteFinder
 * Gets additional sites from which to draw events and publications for a newsletter
 *
 * @author Adante Ratzlaff
 */
class additionalSiteFinder
{
	/**
	 * If the current site has an events module and the page type of the events module has the additional_sites param,
	 * pull events and publications from all additional sites.
	 * @param $site_id
	 * @return entity|array Single site entity or array of site entities
	 */
	function get_additional_sites($site_id){
		// Retrieve an array of all possible events pages on the site
		$event_helper = new EventHelper();
		$ps = new entity_selector($site_id);
		$ps->add_type( id_of('minisite_page') );
		$rels = array();
		foreach($event_helper->get_events_page_types() as $page_type)
		{
			$rels[] = 'page_node.custom_page = "'.$page_type.'"';
		}
		$ps->add_relation('( '.implode(' OR ', $rels).' )');
		$site_pages = $ps->run_one();

		// Get the subset of events page types used by this site's events pages
		$page_types = array();
		foreach($site_pages as $page) {
			$page_type = $page->get_value('custom_page');
			$page_types[] = $page_type;
		}
		$reason_page_types =& get_reason_page_types();

		// For each events page type used by the site, check to see if the page type has the module parameter
		// 'additional_sites' set.  If so, add the string of sites to a longer site string.
		$additional_sites = '';
		foreach($page_types as $page_type_name) {
			$page_type = $reason_page_types->get_page_type($page_type_name);
			foreach ($event_helper->get_events_modules() as $module) {
				if($regions = $page_type->module_regions($module)) {
					$region = $page_type->get_region(reset($regions));
					if(isset($region['module_params'])){
						$module_params = $region['module_params'];
						if(isset($module_params['additional_sites'])) {
							$additional_sites .= $module_params['additional_sites'];
						}
					}
					break;
				}
			}
		}

		// Convert the string to a list of site entities.
		$string_to_sites = new stringToSites();
		$sites = $string_to_sites->get_sites_from_string($additional_sites);
		return $sites;
	}
}