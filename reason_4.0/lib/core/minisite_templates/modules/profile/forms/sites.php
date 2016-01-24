<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );
include_once( DISCO_INC . 'boxes/stacked.php' );
include_once(DISCO_INC . 'plugins/input_limiter/input_limiter.php');

/**
 * Sites edit form.
 *
 * - Allows you to add / remove the sites (external URLs) that we show.
 * - We use javascript to make a nice hide/show interface.
 * - We allow up to 10 site links total.
 * - We handle "other."
 * - We limit 
 * 
 * @todo validation and safe names for things.
 */
class sitesProfileEditForm extends defaultProfileEditForm
{
	//var $site_options= array('Personal Website', 'LinkedIn', 'Facebook', 'Twitter', 'Blog', 'Other');
	var $site_options= array('LinkedIn', 'Blog', 'Portfolio', 'Other');
	var $max_num_sites = 10;
	var $box_class = 'StackedBox';
	
	function custom_init()
	{
		$head_items = $this->get_head_items(); // module head items
		$head_items->add_javascript(JQUERY_URL, true);
		$head_items->add_javascript(REASON_HTTP_BASE_PATH . 'modules/profiles/sites.js');
	}
	
	function on_every_time()
	{
		$person = $this->get_person();
		$sites = $person->get_sites();
		$count = 0;
		
		// we use a limiter for label fields.
		$limiter = new DiscoInputLimiter($this);
			
		while ($count < $this->max_num_sites)
		{
			$this->add_element('item_'.$count, 'select_no_sort', array('add_empty_value_to_top' => true, 'display_name' => 'Type of Link', 'options' => array_combine($this->site_options, $this->site_options)));
			$this->add_element('item_'.$count.'_name', 'text', array('display_name' => 'Label'));
			
			// this limiter plug in is pretty awesome!
			$limiter->limit_field('item_'.$count.'_name', 40);
			
			$this->add_element('item_'.$count.'_url', 'text', array('display_name' => 'Link'));
			$this->add_element_group(
				'stacked', 
				'sites_'.$count, 
				array('item_'.$count, 'item_'.$count.'_name', 'item_'.$count.'_url'),
				array('use_element_labels' => true, 'use_group_display_name' => false)
			);
			$this->set_display_name('sites_'.$count, 'Link #'.($count+1));
			$this->set_comments('item_'.$count.'_name', form_comment('Only saved if you choose "Other" for the type of link.'));
			if (!empty($sites))
			{
				// populate this element using this site;
				$site_url = reset($sites);
				$site_name = key($sites);
				array_shift($sites);
				$type = (in_array($site_name, $this->site_options)) ? $site_name : 'Other';
				if ($type == 'Other') $this->set_value('item_'.$count.'_name', $site_name);
				$this->set_value('item_'.$count, $type);
				$this->set_value('item_'.$count.'_url', $site_url);
			}
			$count++;		
		}
	}
	
	/**
	 * Lets do some munging ...
	 *
	 * - If we didn't enter a URL, lets clear out the item.
	 * - If we haven't begun the string with http:// or https:// append that to the start.
	 */
	function pre_error_check_actions()
	{
		$count = 0;
		while ($count < $this->max_num_sites)
		{
			$elm_name_type = 'item_'.$count;
			$elm_name_name = 'item_'.$count.'_name';
			$elm_name_url = 'item_'.$count.'_url';
			if ($type = $this->get_value($elm_name_type))
			{
				$url = $this->get_value($elm_name_url);
				// if URL is blank lets blank out the rest.
				if (empty($url))
				{
					$this->set_value($elm_name_type, '');
					$this->set_value($elm_name_name, '');
				}
			}
			if ($url = $this->get_value($elm_name_url)) // we dynamically add http:// if that makes the URL valid where it wasn't before.
			{
				if (!$this->validate_url($url) && $this->validate_url('http://' . $url))
				{
					$this->set_value($elm_name_url, 'http://' . $url);
				}
			}
			$count++;
		}
	}
	
	/**
	 * @todo what is left?
	 */
	function run_error_checks()
	{
		$count = 0;
		while ($count < $this->max_num_sites)
		{
			$elm_name_type = 'item_'.$count;
			$elm_name_name = 'item_'.$count.'_name';
			$elm_name_url = 'item_'.$count.'_url';
			
			//var_dump ($this->get_value($elm_name_type));
			
			if ($this->get_value($elm_name_type))
			{
				$type = $this->get_value($elm_name_type);
				$type_count[$type] = (!isset($type_count[$type])) ? 1 : ($type_count[$type] + 1);
				if ($type == 'Other')
				{
					$name = $this->get_value($elm_name_name);
					$type_count_other[$name] = (!isset($type_count_other[$name])) ? 1 : ($type_count_other[$name] + 1);
					if (!empty($name) && $type_count_other[$name] > 1)
					{
						$this->set_error($elm_name_type, 'You may only add one link with the label "' . htmlspecialchars($name) .'".');
					}
					elseif (empty($name))
					{
						$this->set_error($elm_name_type, 'You need to provide a label if the link type is "Other"');
					}
					elseif ($name != 'Other' && in_array( strtolower($name), array_map('strtolower', $this->site_options) ) )
					{
						$this->set_error($elm_name_type, 'You may not add "Other" links that are already one of the available link options.');
					}
				}
				if ( ($type_count[$type] > 1) && ($type != 'Other') )
				{
					$this->set_error($elm_name_type, 'You may only add one ' . htmlspecialchars($type) . ' link.');
				}
				elseif ($this->get_value($elm_name_name) && ($this->get_value($elm_name_name) != $this->get_value($elm_name_type)) && ($this->get_value($elm_name_type) != 'Other'))
				{
					$this->set_error($elm_name_name, 'Custom labels are only available if you also set the link type to "Other"');
				}
				elseif ($type == 'Personal Website' && !$this->validate_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid Personal Website link.');
				}
				elseif ($type == 'Blog' && !$this->validate_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid Blog link.');
				}
				elseif ($type == 'Portfolio' && !$this->validate_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid portfolio link.');
				}
				elseif ($type == 'LinkedIn' && !$this->validate_linkedin_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid LinkedIn link.');
				}
				elseif ($type == 'Facebook' && !$this->validate_facebook_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid Facebook link.');
				}
				elseif ($type == 'Twitter' && !$this->validate_twitter_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid Twitter link.');
				}
				elseif ($type == 'Instagram' && !$this->validate_instagram_url($this->get_value($elm_name_url)))
				{
					$this->set_error($elm_name_url, 'You need to provide a valid Instagram link.');
				}
				elseif ($type == 'Other' && !$this->validate_url($this->get_value($elm_name_url)))
				{
					$other_name = $this->get_value($elm_name_name);
					if (!empty($other_name))
					{
						$this->set_error($elm_name_url, 'You need to provide a valid ' . htmlspecialchars($other_name) .' link.');
					}
					else
					{
						$this->set_error($elm_name_url, 'You need to provide a valid link.');
					}
				}
			}
			elseif ($this->get_value($elm_name_name) || $this->get_value($elm_name_url))
			{
				$this->set_error($elm_name_type, 'You need to choose a link type.');
			}
			$count++;
		}	
	}

	private function validate_linkedin_url($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
		{
			$host = parse_url($url, PHP_URL_HOST);
			return in_array(strtolower($host), array('linkedin.com', 'www.linkedin.com'));
		}
		return false;
	}
	
	private function validate_facebook_url($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
		{
			$host = parse_url($url, PHP_URL_HOST);
			return in_array(strtolower($host), array('facebook.com', 'www.facebook.com'));
		}
		return false;
	}
	
	private function validate_twitter_url($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
		{
			$host = parse_url($url, PHP_URL_HOST);
			return in_array(strtolower($host), array('twitter.com', 'www.twitter.com'));
		}
		return false;
	}
	
	private function validate_instagram_url($url)
	{
		if (filter_var($url, FILTER_VALIDATE_URL))
		{
			$host = parse_url($url, PHP_URL_HOST);
			return in_array(strtolower($host), array('instagram.com', 'www.instagram.com'));
		}
		return false;
	}
	
	private function validate_url($url)
	{
		return (filter_var($url, FILTER_VALIDATE_URL));
	}
	
	/**
	 * Save new / updated sites as external urls using profile person methods.
	 *
	 * - Grab our new sites array
	 * - Call sync
	 */
	function process()
	{
		$count = 0;
		$sites = array();
		$elements = $this->get_values();
		
		while ($count < $this->max_num_sites)
		{
			if ($this->is_element('item_'.$count))
			{
				$type = $this->get_value('item_'.$count);
				$name = $this->get_value('item_'.$count.'_name');
				$url = $this->get_value('item_'.$count.'_url');
				$entity_name = (!empty($name)) ? $name : $type;
				if (!empty($entity_name) && !empty($url))
				{
					$sites[$entity_name] = $url;
				}
			}
			$count++;
		}
		
		// if there are changes, lets process them.
		$person = $this->get_person();
		$person->sync_sites($sites, $this->site_options);
	}
}