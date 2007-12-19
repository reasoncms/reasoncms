<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AssetsModule';
	
	class AssetsModule extends DefaultMinisiteModule
	{
		var $es;
		var $acceptable_params = array(
			'show_fields'=>array(),
			'date_format'=>'',
			'order'=>'',
		);
		function init( $args = array() ) // {{{
		{
			$this->es = new entity_selector();
			$this->es->description = 'Selecting assets for this page';
			$this->es->set_env( 'site', $this->site_id);
			$this->es->add_type( id_of('asset') );
			$this->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_asset') );
			if(!empty($this->params['order']))
			{
				$this->es->set_order( $this->params['order'] );
			}
			else
			{
				$this->es->add_rel_sort_field( $this->parent->cur_page->id(), relationship_id_of('page_to_asset'));
				$this->es->set_order( 'rel_sort_order' );
			}
			$this->assets = $this->es->run_one();
		} // }}}
		function has_content() // {{{
		{
			if( $this->assets )
				return true;
			else
				return false;
		} // }}}
		function run() // {{{
		{
			$site = new entity( $this->parent->site_id );
			reason_include_once( 'function_libraries/asset_functions.php' );
			if(!empty($this->params['show_fields']) && !empty($this->params['date_format']))
			{
				$markup = make_assets_list_markup( $this->assets, $site, $this->params['show_fields'], $this->params['date_format'] );
			}
			elseif(!empty($this->params['show_fields']))
			{
				$markup = make_assets_list_markup( $this->assets, $site, $this->params['show_fields'] );
			}
			elseif(!empty($this->params['date_format']))
			{
				trigger_error('assetsModule::run(): the show_fields parameter must be passed to the assetsModule for the date_format parameter to work');
				$markup = make_assets_list_markup( $this->assets, $site );
			}
			else
			{
				$markup = make_assets_list_markup( $this->assets, $site );
			}
			
			if(!empty($markup))
			{
				echo '<div class="assets">'."\n";
				echo '<h3>Related Documents</h3>'."\n";
				echo $markup;
				echo '</div>'."\n";
			}
		} // }}}
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
