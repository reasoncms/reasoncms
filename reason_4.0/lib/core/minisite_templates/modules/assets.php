<?php

	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'function_libraries/asset_functions.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AssetsModule';
	
	class AssetsModule extends DefaultMinisiteModule
	{
		var $es;
		var $assets;
		var $assets_by_category;
		
		var $acceptable_params = array(
			'show_fields'=>array(),
			'date_format'=>'',
			'limit_by_page_categories'=>false,
			'order'=>'',
		);
		
		function init( $args = array() ) // {{{
		{
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
			if ($this->params['limit_by_page_categories']) $es_by_cat = carl_clone($this->es);
			$this->assets = $this->es->run_one();
			
			if ($this->assets)
			{
				if ($this->params['limit_by_page_categories'])
				{
					$es_by_cat->enable_multivalue_results();
					$es_by_cat->add_left_relationship_field('asset_to_category', 'entity', 'id', 'cat_id'); // grab category ids
					$result = $es_by_cat->run_one();
					if (!empty($result)) $this->assets_by_category =& $this->init_by_category($result);
				}
			}
		} // }}}
		
		/**
		 * Grab categories and for each page category, build a reference to a subset of the page assets
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
		
		function has_content() // {{{
		{
			if( $this->assets || $this->assets_by_category ) return true;
			else return false;
		} // }}}
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
				echo '<div class="assets">'."\n";
				echo '<h3>Related Documents</h3>'."\n";
				echo $markup;
				echo '</div>'."\n";
			}
		} // }}}
		
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
		function get_documentation()
		{
			return'<p>Displays assets (e.g. pdfs and other documents) attached to this page.</p>';
		}
	}
?>
