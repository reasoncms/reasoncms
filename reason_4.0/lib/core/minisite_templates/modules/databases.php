<?php
	reason_include_once( 'minisite_templates/modules/generic3.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DatabasesModule';

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
								'content_types'=>array(), //array of unique names to limit content types to (using AND in query)
								'subjects'=>array(), //array of subjects to limit content types to (using AND in query)
								'vendors'=>array(), //array of vendors to limit content types to (using AND in query)
	);
	var $top_link = '<div class="top"><a href="#top">Top</a></div>';
	
	function set_type()
	{
		$this->type = id_of('database_type');
	}
	function alter_es() // {{{
	{
		$this->es->set_order( 'entity.name ASC' );
		$this->es->add_left_relationship_field( 'db_to_primary_external_url', 'external_url' , 'url' , 'primary_url' );
		if(!empty($this->params['content_types']))
		{
			$this->add_content_type_limitation($this->params['content_types']);
		}
		if(!empty($this->params['subjects']))
		{
			$this->add_subject_type_limitation($this->params['subjects']);
		}
		if(!empty($this->params['vendors']))
		{
			$this->add_vendor_limitation($this->params['vendors']);
		}
	} // }}}
	function add_content_type_limitation($content_types)
	{
		$relid = relationship_id_of('database_to_content_type');
		foreach($content_types as $content_type_uname)
		{
			if($content_type_id = id_of($content_type_uname))
			{
				$this->es->add_left_relationship($content_type_id,$relid);
			}
			else
			{
				trigger_error('The content type with unique name '.$content_type_uname.' does not exist in the Reason DB.');
			}
		}
	}
	function add_subject_type_limitation($subjects)
	{
		$relid = relationship_id_of('database_to_subject');
		foreach($subjects as $subject_uname)
		{
			if($subject_id = id_of($subject_uname))
			{
				$this->es->add_left_relationship($subject_id,$relid);
			}
			else
			{
				trigger_error('The subject with unique name '.$subject_uname.' does not exist in the Reason DB.');
			}
		}
	}
	function add_vendor_limitation($vendors)
	{
		$relid = relationship_id_of('db_provided_by_organization');
		foreach($vendors as $vendor_uname)
		{
			if($vendor_id = id_of($vendor_uname))
			{
				$this->es->add_left_relationship($vendor_id,$relid);
			}
			else
			{
				trigger_error('The organization with unique name '.$vendor_uname.' does not exist in the Reason DB.');
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
		$item->_values['primary_url'] = $url->get_value('url');
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
		echo '<a href="' . $item->get_value('primary_url') . '">' . $item->get_value( 'name' ).'</a>';
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
