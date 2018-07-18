<?php
/**
 * @package reason
 * @subpackage admin
 */

include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php' );
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once('classes/xml_export.php');
	reason_include_once('classes/csv_export.php');
	reason_include_once('classes/api/api.php');
	
	/**
	 * Exports reason entities by type in XML and CSV formats
	 * @todo export site entities as XML both individually and in the 'all-types export'
	 */
	class ReasonExportModule extends DefaultModule// {{{
	{
		// Set to true in init() if exporting as CSV
		var $should_run_api = false;
		
		function get_custom_export_map()
		{
			return array(
				'image' => array(
					'module' => 'ExportImages',
					'label' => 'Export Original Image Files',
				),
			);
		}
		
		/**
		 * Call back method for disco
		 * @param disco form $d
		 */
		function go_to_url($d) {
			$type_id = $d->get_value('type');
			$site_id = $this->admin_page->request['site_id'];
			$export_type = $this->get_export_type();
			if ($d->get_value('include_empty_columns') == '')
				$show_all_columns = 'false';
			else
				$show_all_columns = $d->get_value('include_empty_columns');
			$num = (integer) $d->get_value('number_of_items');
			$index = (integer) $d->get_value('index');
			$states = '';
			if(is_array($d->get_value('states')))
			{
				$states = implode(',', $d->get_value('states'));
			}
			$link = $this->admin_page->make_link(array('export_type_id'=>$type_id,'export_type'=>$export_type,'show_all_columns'=>$show_all_columns,'number_of_items'=>$num, 'index' => $index, 'states' => $states), false, false);
			return $link;
		}

		function should_run_api() {
			return $this->should_run_api;
		}
		
		function get_export_type() { 
			if (isset($this->admin_page->request['export_type'])) {
				if (($this->admin_page->request['export_type'] != '')) {
					return $this->admin_page->request['export_type'];
				}
			}
			return 'csv';
		}
		
		/**
		 * Handles exporting CSV files
		 */
		function run_api() { 
			
			$types = $this->admin_page->get_types_for_current_site();			
			if(!empty($this->admin_page->request['site_id'])) {
				$site_id = (integer) $this->admin_page->request['site_id'];
			}
			if(!empty($this->admin_page->request['export_type_id']))
			{
				$type_id = (integer) $this->admin_page->request['export_type_id'];
			}
			
			$es = new entity_selector($site_id);
			
			if(!empty($type_id) && isset($types[$type_id]))
			{
				$query_types= array($type_id => $types[$type_id]);
			}
			else
			{
				$query_types= $types;
			}

			if (isset($this->admin_page->request['number_of_items']) && ($num = (integer) $this->admin_page->request['number_of_items']))
			{
				$es->set_num($num);
			}
			if(isset($this->admin_page->request['index']) && ($index = (integer) $this->admin_page->request['index']))
			{
				$es->set_start($index);
			}
			$states = array('Live');
			if(!empty($this->admin_page->request['states']))
			{
				$states = explode(',', $this->admin_page->request['states']);
			}

			$entities = array();
			
			foreach($query_types as $type)
			{
				$entities = array_merge($entities, $es->run_one( $type->id(), $states ) );
			}
	        
	        if ($this->admin_page->request['show_all_columns'] == 'true')
	        	$show_all_columns = true;
	        else
	        	$show_all_columns = false;
	        
	        foreach($entities as $e)
	        {
	        	$this->add_generated_data($e);
	        }


	        $information = array(
	        	'entity-type'=>$this->admin_page->request['export_type_id'],
	        	'site-id'=>$this->admin_page->request['site_id']
	        	);
	        $export_settings = array('show_all_columns'=>$show_all_columns);
            $export = new reason_csv_export();
            $export->show_all_columns($show_all_columns);
            foreach($export->get_headers($type_id,$site_id) as $header)
            	header($header);
            echo $export->get_csv($entities,$type_id,$site_id);
            exit();
		}
		
		protected function add_generated_data($e)
		{
			if($e->method_supported('get_export_generated_data'))
			{
				$data = $e->get_export_generated_data();
				if(!empty($data))
				{
					foreach($data as $k => $v)
					{
						$e->set_value('_generated_'.$k, $v);
					}
				}
			}
		}

		function EntityInfoModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		function init() // {{{
		{
			$this->admin_page->title = 'Export';
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'css/reason_admin/export.css');
            if (isset($this->admin_page->request['download_csv']))
            {
                if ($this->admin_page->request['download_csv'] == 'true') {
	                $this->should_run_api = true;
            	} else {
                	$this->should_run_api = false;
                }
            }
		} // }}}
		
		function run() // {{{
		{  
			$export_type = $this->get_export_type();
			$csv_link = $this->admin_page->make_link(array('export_type'=>'csv'), false);
			$xml_link = $this->admin_page->make_link(array('export_type'=>'xml'), false);
			
			echo '<div class="exportTabs">';
			echo '<h4>Format:</h4>';
			if ($export_type == 'csv') {
				echo '<ul class="tabs">'.
				'<li class="current"><strong>CSV</strong></li>'.
				'<li><a href="'.$xml_link.'">XML</a></li>'.
				'</ul>';				
			}
			else if ($export_type == 'xml') {
				echo '<ul class="tabs">'.
				'<li><a href="'.$csv_link.'">CSV</a></li>'.
				'<li class="current"><strong>XML</strong></li>'.
				'</ul>';		
			}
			echo '</div>';
			if (!isset($this->admin_page->request['site_id'])) {
				$sites_access_to = $this->admin_page->get_sites();
				echo '<h3>Pick a site to export data from:</h3>
				<ul>';
				foreach ($sites_access_to as $site) {
					$link = $this->admin_page->make_link(array('site_id'=>$site->_id,'export_type'=>$export_type),false,false);					
					$name = $site->_values['name'];
					echo '<li><a href="'.$link.'">'.$name.'</a></li>';
				}
				echo '</ul>';
			} else if (isset($this->admin_page->request['site_id'])) {
				$site_id = $this->admin_page->request['site_id'];		
				$types = $this->admin_page->get_types_for_current_site();
				/**
				 * Creating the form
				**/
				$radio_buttons = array();
				$desc = '';
				$notice = '';
				if ('xml' == $export_type) {
					$radio_buttons['all_types'] = 'All';
					$desc = 'Export data from Reason as structured XML';
					$notice = 'Note: XML exports do not contain asset files, image files, or form data.';
				}
				elseif ('csv' == $export_type) {
					$desc = 'Export data from Reason as a spreadsheet';
					$notice = 'Note: CSV exports do not contain asset files, image files, or form data. CSV exports of form data are available when editing a form.';
				}
				
				$map = $this->get_custom_export_map();
				
				$export_type_id = $this->get_export_type_id();
				if($export_type_id && isset($map[unique_name_of($export_type_id)]))
				{
					$export_info = $map[unique_name_of($export_type_id)];
					$notice .= ' <a href="'.$this->admin_page->make_link(array('cur_module'=>$export_info['module'])).'">'.$export_info['label'].'</a>';
				}
				
				foreach ($types as $type) {
					$radio_buttons[$type->get_value('id')] = $type->get_value('name');
				}
				$d = new disco();
				$d->set_actions(array('Create Export'));
				$d->set_box_class('StackedBox');
				$d->add_element('type', 'radio', array('options'=>$radio_buttons));
				if ($export_type == 'csv'){
					$d->add_element('states', 'checkboxgroup', array('options'=>array('Live'=>'Live','Pending'=>'Pending','Deleted'=>'Deleted')));
					$d->set_value('states', array('Live'));
					if(!empty($this->admin_page->request['states']))
					{
						if(is_array($this->admin_page->request['states']))
						{
							$d->set_value('states', $this->admin_page->request['states']);
						}
						else
						{
							$d->set_value('states', explode(',', $this->admin_page->request['states']));
						}
					}
					
					$d->add_element('include_empty_columns', 'checkbox', array('checkbox_id'=>'includeEmptyColumns', 'checked_value'=>'true', 'description'=>''));
					$d->add_element('number_of_items', 'text');
					$d->add_element('index', 'text');
				}
				if (!empty($export_type_id)) {
					$d->set_value('type',$export_type_id);
				}
				if (isset($this->admin_page->request['show_all_columns']))
					if ($this->admin_page->request['show_all_columns'] == 'true')
						$d->set_value('include_empty_columns','true');
				if (!empty($this->admin_page->request['number_of_items']))
					$d->set_value('number_of_items', (integer) $this->admin_page->request['number_of_items']);
				if (!empty($this->admin_page->request['index']))
                                        $d->set_value('index', (integer) $this->admin_page->request['index']);
				echo '<h3 class="description">'.$desc.'</h3>';
				echo '<p class="notice">'.$notice.'</p>';
				$d->add_callback(array($this,'go_to_url'), 'where_to');
				$d->run();	
				/**
				 * Creating Export Output
				**/
				if (isset($export_type_id)) {
					echo '<div class="exportOutput">';
					echo '<h4>Export Output:</h4>';
					if ($export_type == 'csv') {
						if (isset($this->admin_page->request['show_all_columns'])) {
							$show_all_columns = $this->admin_page->request['show_all_columns'];
						} else {
							$show_all_columns = 'false';
						}
						$num = '';
						if (!empty($this->admin_page->request['number_of_items'])) {
							$num = (integer) $this->admin_page->request['number_of_items'];
						}
						$index = 0;
						if (!empty($this->admin_page->request['index'])) {
							$index = (integer) $this->admin_page->request['index'];
						}
						$states = 'Live';
						if (!empty($this->admin_page->request['states'])) {
							$states = $this->admin_page->request['states'];
						}
						$link = $this->admin_page->make_link(array('export_type_id'=>$export_type_id,'download_csv'=>'true','show_all_columns'=>$show_all_columns, 'number_of_items' => $num, 'index' => $index, 'states' => $states),false,false);					
						echo '<a href="'.$link.'">'.'Download'.'</a>';
					} else if ($export_type == 'xml') {
						if ($export_type_id == 'all_types') {
							$entities = array();
							$types = $this->admin_page->get_types_for_current_site();
							if(isset($types[id_of('site')]))
							{
								unset($types[id_of('site')]);
								echo '<p>Site entities are not currently supported by the XML exporter. They will not be included in this export.</p>';
							}
							$entities = array_merge($entities, $types);
						} elseif(id_of('site') == $export_type_id) {
							echo '<p>Site entities are not currently supported by the XML exporter. They will not be included in this export.</p>';
						}
						else {
							$es = new entity_selector($site_id);
							$es->add_type($export_type_id);
							$entities = $es->run_one();
						}
						$export = new reason_xml_export();
	                    echo '<textarea rows="38">'.htmlspecialchars($export->get_xml($entities), ENT_QUOTES).'</textarea>'."\n";
					}
					
                    echo '</div>';
				}
			}
		}
		function get_export_type_id()
		{
			if (isset($this->admin_page->request['export_type_id'])) {
				return (integer) $this->admin_page->request['export_type_id'];
			} elseif (isset($this->admin_page->request['type_id'])) {
				return (integer) $this->admin_page->request['type_id'];
			}
			elseif (isset($this->admin_page->request['type'])) {
				return (integer) $this->admin_page->request['type'];
			}
			return NULL;
		}
	}
?>
