
<?php 
include_once( 'reason_header.php' );
reason_include_once( 'function_libraries/admin_actions.php');
reason_include_once( 'classes/entity_selector.php');
include_once ( DISCO_INC . 'disco.php');

class UpdateNutritionalInfo extends Disco{

	var $elements = array(
		'element_a' => 'hidden',
	);

	var $file_path = '/usr/local/webapps/www-data/nutrition_info/nutrinfo_cleaned.txt' ;

	var $menu_items = array();	//array of all menu items on the site.
	var $new_info = array();
	var $nutritional_info_items = array('Portion'=>'Portion Size',
										'Calories'=>'Calories',
										'Carboyhdrates'=>'Carboyhdrates',
										'Dietary Fiber'=>'Dietary Fiber',
										'Fat'=>'Fat',
										'Saturated Fat'=>'Saturated Fat',
										'Polyunsaturated Fat'=>'Polyunsaturated Fat',
										'Cholesterol'=>'Cholesterol',
										'Calories from Fat'=>'Calories from Fat',
										'Protein'=>'Protein',
										'Sodium'=>'Sodium',
										'Iron'=>'Iron',
										'Calcium'=>'Calcium',
										'Phosphates'=>'Phosphates',
										'Potassium'=>'Potassium',
										'Vitamin A'=>'Vitamin A',
										'Vitamin B1'=>'Vitamin B1',
										'Niacin'=>'Niacin',
										'Vitamin C'=>'Vitamin C',
										'Vitamin B2'=>'Vitamin B2',
										'Vitamin B6'=>'Vitamin B6',
									);
	var $info_item_dividers = array('Calories','Carboyhdrates','Fat','Protein','Sodium','Iron');
	var $fields_to_update = array  ('name'=>'RecipeName',
									'nutrition_facts'=>'nutrition_facts_html',
									);
	var $mode = 'add'; // options: 'add' only adds items, 'update' adds items and updates old ones
										
	function on_every_time(){
		echo 'This script will update the nutrition facts for the menu items on this site using information from the 
file '.$this->file_path.'.  <br>Do you wish to continue?';
	}	

	function process(){
		set_time_limit(240);
		$this->init_menu_items();
		$this->init_and_process_new_info();
#		prp($this->new_info);
#		$this->process_new_info();
		echo '<div> All updates have been completed.</div>';
		$this->show_form = false;
	}

	##### Necessary Functions #####
	# generate menu_items ... keyed by sync number.
	# generate new_info (better name?)  ... menu items & nutrition facts.
	# generate HTML for nutrition facts  (done for now)
	# update old menu_item_entities
	# add new menu_item entitites - see student job form for example of entity creation.
	

	//get menu items.  Maybe organize differently? 				
	function init_menu_items(){
		echo '<br>Generating list of current menu items on this site ...';
		$es = new entity_selector();
		$es->description = "Getting the menu items on this site";
		$es->add_type( id_of( 'menu_item_type' ) );
		$temp_menu_items = $es->run_one();
		//organize menu_items by sync number
		foreach($temp_menu_items as $menu_item)
		{
			$sync_name = $menu_item->get_value('sync_name');
			if(!empty($sync_name))
				$this->menu_items[$sync_name] = $menu_item;
			else
				$this->menu_items['no_sync_name'][$menu_item->get_value('name')] = $menu_item;
		}
	}
	
	function init_and_process_new_info()
	{
		echo '<br>Importing data from file ...';
		$info_by_lines = file($this->file_path);
		
		//get header line
		$headers = explode(';', $info_by_lines[0]);
		unset($info_by_lines[0]);
		foreach($headers as $key=>$header)
		{
			$headers[$key] = trim($header);
		}
				
		foreach($info_by_lines as $line)
		{
			$line = trim($line);
			if(!empty($line))
			{
				$values = explode(';', $line);
				$nice_values = array();
				foreach($values as $key=>$value)
				{
					$nice_values[$headers[$key]] = trim($value);
				}
				$nice_values['nutrition_facts_html'] = $this->generate_nutritional_info_html($nice_values);
				if(!empty($nice_values['RecipeName']))
				{
					$this->process_new_info_item($nice_values);
				}
			}
		}
	}
	
	function generate_nutritional_info_html($array)
	{
		$html = '<ul>'."\n";
		foreach($this->nutritional_info_items as $key=>$value)
		{
			if(in_array($key,$this->info_item_dividers))
			{
				$html .= '<li class="divider">';
			}
			else
			{
				$html .= '<li>';
			}
			$html .= '<strong>'.$value.':</strong> '.$array[$key].'</li>'."\n";
		}
		$html .= '</ul>'."\n";
		return $html;
	}
	
/*	function process_new_info()
	{
		foreach($this->new_info as $menu_item)
		{
			$this->process_new_info_item($menu_item);
		}
	} */
	function process_new_info_item($menu_item)
	{
			$sync_name = $menu_item['Number'];
			if(!empty($this->menu_items[$sync_name]))
				$this->update_menu_item($menu_item);
			else
				$this->add_menu_item($menu_item);
	}
	
	function update_menu_item($up_to_date_menu_item)
	{
		if($this->mode == 'update')
		{
			$old_menu_item = $this->menu_items[$up_to_date_menu_item['Number']];
			$flat_values = array();
			
			foreach($this->fields_to_update as $key=>$value)
			{
				$flat_values[$key] = $up_to_date_menu_item[$value];
			}
			
			$tables = get_entity_tables_by_type(id_of('menu_item_type'));
	
			$successful = update_entity( 
				$old_menu_item->id(), 
	// 	maybe shouldn't be using my user_id?
				get_user_id('gibbsm'),
				values_to_tables($tables, $flat_values,  $ignore = array())
			); 
			
			if($successful)
				echo '<br>Updated menu item with sync name '.$old_menu_item->get_value('sync_name').' - "'.$old_menu_item->get_value('name').'"';
			else
				echo '<br>Could not update menu item with sync name '.$old_menu_item->get_value('sync_name').' - "'.$old_menu_item->get_value('name').'"';
		}
	}
	
	function add_menu_item($menu_item)
	{
		$flat_values = array (
			'state' => 'Live',
			'name' => $menu_item['RecipeName'],
			'nutrition_facts' => $menu_item['nutrition_facts_html'],
			'sync_name' => $menu_item['Number']
		);
				
		$tables = get_entity_tables_by_type(id_of('menu_item_type'));
		
		$successful = create_entity( 
			id_of('dining_services_site'), 
			id_of('menu_item_type'), 
			get_user_id('gibbsm'), 
			$flat_values['name'],
			values_to_tables($tables, $flat_values, $ignore = array()), 
			$testmode = false
		);
		
		if($successful)
			echo '<br>Created new menu item with sync name '.$menu_item['Number'].' - "'.$menu_item['RecipeName'].'"';
		else
			echo '<br><em>Failed to create new menu item with sync name '.$menu_item['Number'].' - "'.$menu_item['RecipeName'].'"</em>';
	}
	
}

$form = new UpdateNutritionalInfo();
$form->run();

	

?>
