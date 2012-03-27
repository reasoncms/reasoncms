<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * Include base class & other dependencies
  */
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/asset_functions.php' );
	reason_include_once('classes/group_helper.php');

	/**
	 * Register the class so the template can instantiate it
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AssetsModule';
	
	/**
	 * A minisite module to display assets (files) attached to the current page
	 */
	class AssetsModule extends DefaultMinisiteModule
	{
		var $es;
		var $assets = array();
		var $assets_by_category = array();
		
		/**
		 * Parameters that can be set up in the page type
		 *
		 * limit_by_page_categories is DEPRECATED. Use organize_by_page_categories instead.
		 *
		 * offer_merged_pdfs parameter requires pdflib to be installed.
		 *
		 * show_fields uses same values as the 2nd param of @see function make_assets_list_markup()
		 */
		var $acceptable_params = array(
			'show_fields'=>array(),
			'date_format'=>'',
			'limit_by_page_categories'=>false,
			'organize_by_page_categories'=>false,
			'order'=>'',
			'offer_merged_pdfs'=>false,
		);
		
		var $cleanup_rules = array('asset_view'=>'turn_into_string');
		
		function init( $args = array() ) // {{{
		{
			if ($this->params['limit_by_page_categories'])
			{
				trigger_error('limit_by_page_categories is a deprecated parameter. Use organize_by_page_categories instead.' );
				$this->params['organize_by_page_categories'] = $this->params['limit_by_page_categories'];
				$this->params['limit_by_page_categories'] = false;
			}
			$this->site = new entity($this->site_id);
			$this->es = new entity_selector();
			$this->es->description = 'Selecting assets for this page';
			$this->es->set_env( 'site', $this->site_id);
			$this->es->add_type( id_of('asset') );
			$this->es->add_right_relationship( $this->page_id, relationship_id_of('page_to_asset') );
			if(!empty($this->params['order']))
			{
				$this->es->set_order( $this->params['order'] );
			}
			else
			{
				$this->es->add_rel_sort_field( $this->page_id, relationship_id_of('page_to_asset'));
				$this->es->set_order( 'rel_sort_order' );
			}
			if ($this->params['organize_by_page_categories']) $es_by_cat = carl_clone($this->es);
			$this->assets = $this->es->run_one();
			
			if ($this->assets)
			{
				if ($this->params['organize_by_page_categories'])
				{
					$es_by_cat->enable_multivalue_results();
					$es_by_cat->add_left_relationship_field('asset_to_category', 'entity', 'id', 'cat_id'); // grab category ids
					$result = $es_by_cat->run_one();
					if (!empty($result)) $this->assets_by_category =& $this->init_by_category($result);
				}
			}
			if($this->params['offer_merged_pdfs'] && !empty($this->request['asset_view']) && $this->request['asset_view'] == 'merged_pdf')
			{
				$this->_merge_and_send_pdfs( $this->_get_pdfs_to_merge() );
			}
		} // }}}
		
		/**
		 * Grab categories and for each page category, build a reference to a subset of the page assets
		 *
		 * Takes an array in this form:
		 *
		 * <code>
		 * array( $asset_id=>$asset, $asset_id=>$asset, ...);
		 * </code>
		 *
		 * Returns an array in this form:
		 *
		 * <code>
		 * array(
		 * 		$category_id => array( $asset_id=>$asset, $asset_id=>$asset, ...),
		 * 		$category_id => array( $asset_id=>$asset, $asset_id=>$asset, ...),
		 * 		...
		 * );
		 * </code>
		 *
		 * @param array $page_assets
		 * @return array assets by category
		 */
		function &init_by_category(&$page_assets)
		{
			$assets_by_category = false;
			
			$cat_es = new entity_selector($this->site_id);
			$cat_es->set_env( 'site', $this->site_id );
			$cat_es->add_type( id_of('category_type') );
			$cat_es->limit_tables('entity');
			$cat_es->limit_fields('entity.name');
			$cat_es->add_right_relationship( $this->page_id, relationship_id_of('page_to_category') );
			$cat_es->add_rel_sort_field( $this->page_id, relationship_id_of('page_to_category'));
			$cat_es->set_order( 'rel_sort_order' );
			$result = $cat_es->run_one();
			
			if ($result) 
			{
				$asset_ids = array_keys($page_assets);
				$cat_ids = array_keys($result);
				foreach ($asset_ids as $asset_id)
				{
					$item =& $page_assets[$asset_id];
					$asset_cat_ids = (is_array($item->get_value('cat_id'))) ? $item->get_value('cat_id') : array($item->get_value('cat_id'));
					$cat_intersect = array_intersect($asset_cat_ids, $cat_ids);
					if (!empty($cat_intersect))
					{
						foreach ($cat_intersect as $cat_id)
						{
							$stack[$cat_id][$asset_id] =& $item;
						}
						unset ($this->assets[$asset_id]); // it is in at least one category - zap from main asset list
					}
				}
				foreach ($cat_ids as $cat_id)
				{
					if (isset($stack[$cat_id])) $assets_by_category[$cat_id] =& $stack[$cat_id];
				}
			}
			else
			{
				
			}
			return $assets_by_category;
		}
		/**
		 * Merge and send a set of pdfs
		 *
		 * @access private
		 */
		function _merge_and_send_pdfs($pdfs)
		{
			if(!empty($pdfs))
			{
				$username = reason_check_authentication();
				if(!$this->_has_access($pdfs, $username))
				{
					if(!empty($username))
					{
						$this->_display_403_page();
						die();
					}
					else
					{
						header('Location: '.REASON_LOGIN_URL.'?dest_page='.urlencode(get_current_url()));
						die();
					}
				}
				$pdf_files = array();
				$titles = array();
				foreach($pdfs as $pdf)
				{
					$file_location = reason_get_asset_filesystem_location($pdf);
					$pdf_files[] = $file_location;
					$titles[$file_location] = strip_tags($pdf->get_value('name'));
				}
				
				include_once(CARL_UTIL_INC.'pdf/pdf_utils.php');
				$merged = carl_merge_pdfs($pdf_files, $titles);
				if(empty($merged))
				{
					trigger_error('PDF merge failed');
				}
				else
				{
					if(carl_send_pdf($merged, $this->cur_page->get_value('url_fragment').'.pdf'))
						die();
					else
						trigger_error('Unable to send PDF');
				}
			}
		}
		/**
		 * Display a 403 (access denied) page
		 */
		function _display_403_page()
		{
			http_response_code(403);
			if(file_exists(WEB_PATH.ERROR_403_PATH) && is_readable(WEB_PATH.ERROR_403_PATH))
			{
				include(WEB_PATH.ERROR_403_PATH);
			}
			else
			{
				trigger_error('The file at ERROR_403_PATH ('.ERROR_403_PATH.') is not able to be included');
				echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>403: Forbidden</title></head><body><h1>403: Forbidden</h1><p>You do not have access to this resource.</p></body></html>';
			}
		}
		
		/**
		 * Determine if a given user has access to all of a set of assets
		 *
		 * @param array $access
		 * @param string $username
		 * @return boolean
		 */
		function _has_access($assets, $username)
		{
			if(!is_array($assets))
				$assets = array($assets->id()=>$assets);
			
			if(!empty($assets))
			{
				$es = new entity_selector();
				$es->add_right_relationship(array_keys($assets), relationship_id_of('asset_access_permissions_to_group'));
				$es->add_type(id_of('group_type'));
				$es->set_env('site',$this->site_id);
				$groups = $es->run_one();
				//pray($groups);
				//die();
				
				foreach($groups as $group_id=>$group)
				{
					$gh = new group_helper();
					$gh->set_group_by_entity($group);
					if(!$gh->has_authorization($username))
						return false;
				}
			}
			return true;
		}
		
		/**
		 * Get the set of PDFs on the page that can be merged
		 *
		 * This function provides the pdfs in the same order that they are displayed on the page
		 *
		 * @return array $to_merge an array of PDF assets
		 */
		function _get_pdfs_to_merge()
		{
			$to_merge = array();
			// pick out the pdfs & add to array
			foreach($this->assets as $asset)
			{
				if($asset->get_value('file_type') == 'pdf')
					$to_merge[$asset->id()] = $asset;
			}
			foreach($this->assets_by_category as $assets)
			{
				foreach($assets as $asset)
				{
					if($asset->get_value('file_type') == 'pdf')
						$to_merge[$asset->id()] = $asset;
				}
			}
			return $to_merge;
		}
		
		/**
		 * Does the current page contail only assets of a given file type( e.g. pdf, txt, etc.)?
		 * @param string $type
		 * @return boolean
		 */
		function _page_contains_entirely_single_file_type($type)
		{
			foreach($this->assets as $asset)
			{
				if($asset->get_value('file_type') != $type)
					return false;
			}
			foreach($this->assets_by_category as $assets)
			{
				foreach($assets as $asset)
				{
					if($asset->get_value('file_type') != $type)
						return false;
				}
			}
			return true;
		}
		
		/**
		 * Does the module have any content to display?
		 * @return boolean
		 */
		function has_content() // {{{
		{
			if( $this->assets || $this->assets_by_category ) return true;
			else return false;
		} // }}}
		/**
		 * Generate and output the XHTML content of the module
		 */
		function run() // {{{
		{
			$markup = '';
			if ($this->assets) $markup .= $this->get_asset_markup($this->assets);
			if ($this->assets_by_category)
			{
				foreach ($this->assets_by_category as $category_id=>$assets)
				{
					$category = new entity( $category_id );
					$markup .= '<h4>' . $category->get_value('name') . '</h4>';
					$markup .= $this->get_asset_markup($assets);
				}
			}
			
			if(!empty($markup))
			{
				$class = ($this->assets_by_category) ? "assets assetsByCategory" : "assets";
				echo '<div class="'.$class.'">'."\n";
				echo '<h3>Related Documents</h3>'."\n";
				echo $this->_get_pdf_merge_markup();
				echo $markup;
				echo '</div>'."\n";
			}
		} // }}}
		
		function _get_pdf_merge_markup()
		{
			if($this->params['offer_merged_pdfs'] && count($this->_get_pdfs_to_merge()) > 1)
			{
				if($this->_page_contains_entirely_single_file_type('pdf'))
				{
					return '<div class="downloadMerged"><a href="?asset_view=merged_pdf">Download all documents as a single PDF</a></div>'."\n";
				}
				return '<div class="downloadMerged"><a href="?asset_view=merged_pdf">Download PDFs on this page as one file</a> <div class="note smallText">Note: only PDFs will be merged.</div></div>'."\n";
			}
			return '';
		}
		
		/**
		 * Get the markup for a list of assets
		 *
		 * @param array $assets
		 * @return string $markup
		 */
		function get_asset_markup($assets)
		{
			if(!empty($this->params['show_fields']) && !empty($this->params['date_format']))
			{
				$markup = make_assets_list_markup( $assets, $this->site, $this->params['show_fields'], $this->params['date_format'] );
			}
			elseif(!empty($this->params['show_fields']))
			{
				$markup = make_assets_list_markup( $assets, $this->site, $this->params['show_fields'] );
			}
			elseif(!empty($this->params['date_format']))
			{
				trigger_error('assetsModule::run(): the show_fields parameter must be passed to the assetsModule for the date_format parameter to work');
				$markup = make_assets_list_markup( $assets, $this->site );
			}
			else
			{
				$markup = make_assets_list_markup( $assets, $this->site );
			}
			return $markup;
		}
		/**
		 * When was the most recently edited asset last modified?
		 *
		 * @return string | boolean Either a mysql-formatted datetime or false if no assets on page
		 */
		function last_modified()
		{
			if( $this->has_content() )
			{
				$temp = $this->es->get_max( 'last_modified' );
				return $temp->get_value( 'last_modified' );
			}
			else
				return false;

		}
		/**
		 * Explain the function of the assets module in plain text.
		 */
		function get_documentation()
		{
			return'<p>Displays assets (e.g. pdfs and other documents) attached to this page.</p>'."\n";
			foreach($this->params as $key=>$val)
			{
				switch($key)
				{
					case 'show_fields':
						echo '<p>For each asset, these fields will be shown: '.htmlspecialchars(implode(', ',$val), ENT_QUOTES).'</p>'."\n";
						break;
					case 'date_format':
						echo '<p>Dates will use this format: '.date($val).'</p>'."\n";
						break;
					case 'limit_by_page_categories':
					case 'organize_by_page_categories':
						if($val)
							echo '<p>Assets will be listed in groups based on the categories assigned to the page.</p>'."\n";
						else
							echo '<p>Assets will be listed in a single list (not grouped by categories)</p>'."\n";
						break;
					case 'order':
						echo '<p>Assets will be listed in the following order: '.htmlspecialchars($val, ENT_QUOTES).'.</p>'."\n";
						break;
					case 'offer_merged_pdfs':
						if(function_exists('PDF_new'))
							echo '<p>If the page contains only PDFs, a visitors will be able to download all files as a single PDF.</p>'."\n";
						else
							echo '<p>NOTE: It does not appear that the server is set up correctly to offer merged downloads. Please make sure pdflib is installed.</p>'."\n";
						break;
				}
			}
		}
	}
?>
