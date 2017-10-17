<?php

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
	 * @return int|array Single site id or list of site ids
	 */
	function get_additional_sites($site_id){
		$es = new entity_selector($site_id);
		$es->add_type(id_of('minisite_page'));
		$site_pages = $es->run_one();
		$page_types = array();
		foreach($site_pages as $page) {
			$page_type = $page->get_value('custom_page');
			$page_types[] = $page_type;
		}
		$reason_page_types =& get_reason_page_types();
		$additional_sites = '';
		foreach($page_types as $page_type_name) {
			$page_type = $reason_page_types->get_page_type($page_type_name);
			if($regions = $page_type->module_regions('events')) {
				$region = $page_type->get_region(reset($regions));
				if(isset($region['module_params'])){
					$module_params = $region['module_params'];
					if(isset($module_params['additional_sites'])) {
						$additional_sites .= $module_params['additional_sites'];
					}
				}
			}
		}
		$string_to_sites = new stringToSites();
		$sites = $string_to_sites->get_sites_from_string($additional_sites);
		return $sites;
	}
}