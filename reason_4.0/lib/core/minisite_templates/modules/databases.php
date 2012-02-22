<?php
	/**
	 * @package reason
	 * @subpackage minisite_modules
	 */
	
	/**
	 * Include the base class & register the module with Reason
	 */
	reason_include_once( 'minisite_templates/modules/generic3.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DatabasesModule';

/**
 * A minisite template that lists reason entities that represent databases
 *
 * Note that these are "databases" in the library science sense -- really it lists websites
 * that are presumed to be interfaces to databases of scholarly research
 *
 * This module might be flexible enough to serve as a directory of websites/online resources.
 */
class DatabasesModule extends Generic3Module
{
	var $style_string = 'databases';
	var $current_letter = '';
	var $filter_types = array(	'category'=>array(	'type'=>'category_type',
													'relationship'=>'database_to_category',
												),
								'content'=>array(	'type'=>'content_type_type',
													'relationship'=>'database_to_content_type',
												),
								'subject'=>array('type'=>'subject_type',
													'relationship'=>'database_to_subject',
													),
								'vendor'=>array('type'=>'organization_type',
												'relationship'=>'db_provided_by_organization',
												),
							);
	var $search_fields = array('entity.name','meta.description','meta.keywords','date_string.date_string','db.output_parser');
	var $use_filters = true;
	var $acceptable_params = array(
								'content_types'=>array(), //array of content type unique names to limit dbs to 
								'content_type_matching'=>'and', // 'and' = grab dbs related to ALL; 'or' = grab dbs related to ANY
								'subjects'=>array(), //array of subject unique names to limit dbs to
								'subject_matching'=>'and', // 'and' = grab dbs related to ALL; 'or' = grab dbs related to ANY
								'vendors'=>array(), //array of vendor unique names to limit dbs to
								'vendor_matching'=>'and', // 'and' = grab dbs related to ALL; 'or' = grab dbs related to ANY
	);
	var $top_link = '<div class="top"><a href="#top">Top</a></div>';
	
	/**
	 * The string used to denote the item in the query string
	 * '_id' added to this string to build actual query key
	 * @var string
	 */	
	var $query_string_frag = 'db';
	
	function init($args = array())
	{
		parent::init($args);
		
		if(!empty($this->current_item_id) && !empty($this->items[$this->current_item_id]))
		{
			$url = $this->items[$this->current_item_id]->get_value('primary_url');
			header('Location: '.str_replace('&amp;','&',$url));
			echo '<a href="'.reason_htmlspecialchars($url).'">Attempted redirect to '.reason_htmlspecialchars($url).'</a>';
			// pray($this->items[$this->current_item_id]->get_values());
			die();
		}
	}
	
	function set_type()
	{
		$this->type = id_of('database_type');
	}
	function alter_es() // {{{
	{
		$this->es->limit_fields('entity.id');
		$this->es->set_order( 'entity.name ASC' );
		$this->es->add_left_relationship_field( 'db_to_primary_external_url', 'external_url' , 'url' , 'primary_url' );
		$this->db_alter_es($this->es);
	} // }}}
	function db_alter_es(&$es)
	{
		if(!empty($this->params['content_types']))
		{
			if($this->params['content_type_matching'] == 'or')
				$this->add_or_style_limitation('database_to_content_type',$this->params['content_types'],$es);
			else
				$this->add_and_style_limitation('database_to_content_type',$this->params['content_types'],$es);
		}
		if(!empty($this->params['subjects']))
		{
			if($this->params['subject_matching'] == 'or')
				$this->add_or_style_limitation('database_to_subject',$this->params['subjects'],$es);
			else
				$this->add_and_style_limitation('database_to_subject',$this->params['subjects'],$es);
		}
		if(!empty($this->params['vendors']))
		{
			if($this->params['vendor_matching'] == 'or')
				$this->add_or_style_limitation('db_provided_by_organization',$this->params['vendors'],$es);
			else
				$this->add_and_style_limitation('db_provided_by_organization',$this->params['vendors'],$es);
		}
	}
	
	function add_and_style_limitation($rel_name, $unique_names, &$es)
	{
		$relid = relationship_id_of($rel_name);
		foreach($unique_names as $uname)
		{
			if($id = id_of($uname))
				$es->add_left_relationship($id,$relid);
		}
	}
	function add_or_style_limitation($rel_name, $unique_names, &$es)
	{
		$relid = relationship_id_of($rel_name);
		if($relid)
		{
			$rel_field_info = $es->add_left_relationship_field($rel_name,'entity','id','related_id');
			$ids = array();
			foreach($unique_names as $uname)
			{
				if($id = id_of($uname))
					$ids[] = $id;
			}
			if(!empty($ids))
			{
				$es->add_relation($rel_field_info['related_id']['table'].'.'.$rel_field_info['related_id']['field'].' IN ('.implode(',',$ids).')');
			}
		}
	}
	function _show_item( $id ) // {{{
	{
		$item = new entity( $id );
		$es = new entity_selector();
		$es->add_type(id_of('external_url'));
		$es->add_right_relationship($id, relationship_id_of('db_to_primary_external_url'));
		$urls = $es->run_one();
		$url = current($urls);
		$item->get_values();
		if (is_object($url))
		{
			$item->_values['primary_url'] = $url->get_value('url');
		} else {
			trigger_error('No external urls found for database');
		}
		$this->show_list_item( $item );
	} // }}}
	function list_items() // {{{
	{
		$this->show_jump();
		parent::list_items();
		echo $this->top_link;
		/* echo '<div id="dbList">'."\n";
		if(!empty($this->items))
		{
			foreach( $this->items AS $item )
			{
				$this->show_list_item( $item );
			}
			echo '</ul>'."\n";
		}
		else
		{
			if(!empty($this->request['search']))
				$phrase[] = 'search term';
			if(!empty($this->filters))
				$phrase[] = 'focus';
				
			echo '<p>';
			if(!empty($phrase))
				echo 'There are no databases that match the current '.implode(' and ', $phrase).'.';
			else
				echo 'There are no databases available on this site.';
			echo '</p>'."\n";
		}
		echo '</div>'."\n"; */
	} // }}} 
	function show_list_item( $item ) // {{{
	{
		$es = new entity_selector();
		$es->add_type(id_of('content_type_type'));
		$es->description = 'Selecting content types for '.$item->get_value('name');
		$es->add_right_relationship( $item->id(), relationship_id_of('database_to_content_type') );
		$types = $es->run_one();
		
		$processed_types = array();
		foreach($types as $type)
			$processed_types[$type->id()] = $type->get_value('name');
		
		$first_letter = strtoupper(substr($item->get_value('name'), 0, 1));
		if($first_letter != $this->current_letter)
		{
			$this->current_letter = $first_letter;
			echo '</ul>'.$this->top_link.'<h3><a name="db_'.$this->current_letter.'" id="db_'.$this->current_letter.'"></a>'.$this->current_letter.'</h3><ul class="moduleNav">'."\n";
		}
		
		echo '<li><strong>';
		echo '<a href="' . $this->_get_url_of_item($item) . '">' . $item->get_value( 'name' ).'</a>';
		echo '</strong>';
		if($item->get_value('date_string') || !empty($processed_types))
		{
			echo ' <span class="coverageAndContentTypes">(';
			if($item->get_value('date_string'))
			{
				echo $item->get_value('date_string');
				if(!empty($processed_types))
					echo ' &#8226; ';
			}
			if(!empty($processed_types))
			{
				echo implode(', ', $processed_types);
			}
			echo ')</span>';
		}
		$this->show_list_item_desc( $item );
		echo '</li>'."\n";
	} // }}}
	function _get_url_of_item($item)
	{
		return $item->get_value('primary_url');
	}
	function show_list_item_desc( $item )
	{
		$es = new entity_selector();
		$es->add_type(id_of('external_url'));
		$es->description = 'Selecting secondary urls for '.$item->get_value('name');
		$es->add_right_relationship( $item->id(), relationship_id_of('db_to_secondary_external_url') );
		$urls = $es->run_one();
		
		if($item->get_value('description') || !empty($urls) || $item->get_value('output_parser'))
		{
			
			echo "\n".'<ul>'."\n";
			if($item->get_value('description'))
				echo '<li>'.$item->get_value('description').'</li>'."\n";
			if(!empty($urls) || $item->get_value('output_parser'))
			{
				$links = array();
				echo '<li><em>More Info:</em> ';
				foreach($urls as $url)
					$links[$url->id()] = '<a href="'.$url->get_value('url').'">'.$url->get_value('name').'</a>';
				echo implode(', ', $links);
				if($item->get_value('output_parser'))
				{
					if(!empty($urls))
						echo ' &#8226; ';
					echo '<em>EndNote Import:</em> '.$item->get_value('output_parser');
				}
				echo '</li>'."\n";
			}
			echo '</ul>';
		}
	}
	function alter_relationship_checker_es($es)
	{
		$this->db_alter_es($es);
		return $es;
	}
	function show_jump()
	{
		$current_letter = '';
		$links = array();
		foreach( $this->items AS $item )
		{
			$first_letter = strtoupper(substr($item->get_value('name'), 0, 1));
			if($first_letter != $current_letter)
			{
				$current_letter = $first_letter;
				$links[] = '<a href="#db_'.$current_letter.'">'.$current_letter.'</a>';
			}
		}
		if(count($links) > 1)
		{
			echo '<div id="dbJump" class="smallText">Jump: '.implode(' ', $links).'</div>'."\n";
		}
	}
}
?>
