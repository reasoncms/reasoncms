<?php

/**
 * @package reason
 * @subpackage admin
 */
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');

/**
 * An administrative module that displays counts of entities by type and status.
 */
class ReasonSharingStatsModule extends DefaultModule
{
    protected $types;
    protected $data;
    protected $sharing_sites = array();

    function ReasonSharingStatsModule(&$page) {
        $this->admin_page = & $page;
    }

    function init()
    {
        $this->admin_page->title = 'Sharing Stats';
    }

    function run()
    {
        if (!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data')) {
            echo 'Sorry; you do not have the rights to view this information.';
            return;
        }
        
        if($type_id = $this->get_type())
        {
        	echo $this->get_type_details( $type_id );
        }
        else
        {
        	echo $this->get_type_list();
        }
    }
    
    protected function get_type_list()
    {
        $types = $this->get_all_types();
		$data = $this->get_data($types);
		$sums = $this->get_sums($data);
		
		return $this->get_counts_html($types, $data, $sums);
	}
	
	protected function get_counts_html($types, $data, $sums)
	{
    	$ret = '';
    	
		$sum_labels = array(
			'Sites' => 'Number of site/type shares',
			'Entities' => 'Number of entities shared in total',
			'Borrows' => 'Number of entities borrowed in total',
		);
		
        foreach($sums as $label => $count)
        {
        	$label = isset($sum_labels[$label]) ? $sum_labels[$label] : $label;
        	$ret .= '<p>' . $label . ': ' . $count . '</p>';
        }
        $unique_sharing_sites_count = count($this->get_all_sharing_sites());
        $ret .= '<p>Unique sites sharing anything: ' . $unique_sharing_sites_count . '</p>';
        $ret .= '<p>Average number of types shared per site: ' . round($sums['Sites']/$unique_sharing_sites_count, 2) . '</p>';
        $ret .= '<p>Average number of entities shared per site: ' . round($sums['Entities']/$unique_sharing_sites_count, 2) . '</p>';
        $deferred = array();
        foreach($types as $type)
        {
        	$defer = true;
        	$str = '';
        	$str .= '<h3><a href="'.$this->admin_page->make_link(['stats_type' => $type->id()]).'">'.$type->get_display_name().'</a></h3>';
        	$str .= '<ul>';
        	
        	foreach($data[$type->id()] as $label => $count)
        	{
        		if($count > 0) {
        			$defer = false;
        		}
        		$str .= '<li>'.$label.': '.$count.'</li>';
        	}
        	$str .= '</ul>';
        	if($defer)
        	{
        		$deferred[] = $type;
        	}
        	else
        	{
        		$ret .= $str;
        	}
        }
        if(!empty($deferred))
        {
        	$ret .= '<h3>Types with no shares</h3>';
        	$ret .= '<ul>';
        	foreach($deferred as $type)
        	{
        		$ret .= '<li>'.$type->get_display_name().'</li>';
        	}
        	$ret .= '</ul>';
        }
        return $ret;
    }
    
    function get_type_details( $type )
    {
    	$ret = '';
    	//$counts = $this->get_type_data($type);
    	$examples = $this->get_type_examples($type);
		$ret .= '<a href="'.$this->admin_page->make_link(['stats_type' => 0]).'">Back to list</a>';
    	$ret .= '<h3>'.$type->get_value('name').'</h3>';
    	$ret .= $this->get_examples_html($examples);
    	return $ret;
    }

	function get_examples_html($examples)
	{
    	$ret = '';
    	
		$labels = array(
			'Sites' => 'Sites sharing this type',
			'Entities' => 'Example shared entities',
			'Borrows' => 'Examples of borrowed entities',
		);
		
		
		foreach( $labels as $key => $label )
		{
			$ret .= '<h4>'.$label.'</h4>';
			$ret .= '<table>';
			$ret .= '<tr><th>Name</th>';
			if('Sites' != $key)
			{
				$ret .= '<th>Owned By</th><th>Borrowed By</th>';
			}
			$ret .= '</tr>';
			if(empty($examples[$key]))
			{
				continue;
			}
			foreach($examples[$key] as $example)
			{
				$ret .= '<tr>';
				$ret .= '<td>'.$this->display_entity($example).'</td>';
				if('Sites' == $key)
				{
					continue;
				}
				$owner = $example->get_owner();
				$ret .= '<td>'. ( $owner ? $this->display_entity($owner) : '(Orphan)').'</td>';
			
				$ret .= '<td>';
				$borrowed_bys = $example->get_right_relationship(get_borrows_relationship_id($example->get_value('type')));
				if(!empty($borrowed_bys))
				{
					$ret .= '<ul>';
					foreach($borrowed_bys as $bb)
					{
						$ret .= '<li>'.$this->display_entity($bb).'</li>';
					}
					$ret .= '</ul>';
				}
				$ret .= '</td>';
				$ret .= '</tr>';
			}
			$ret .= '</table>';
		}
		return $ret;
	}
	
	function display_entity($e)
	{
		return '<a href="'.reason_htmlspecialchars($e->get_edit_url()).'">'.$e->get_value('name').'</a>';
	}
	
	function get_entity_borrowed_bys($e)
	{
		$ret = array();
		$rel = relationship_finder( 'site', $e->get_value('type'), 'borrows' );
		if($rel)
		{
			return $e->get_right_relationship('borrows');
		}
		return $ret;
	}

    function get_all_types() {
    	if(isset($this->types))
    		return $this->types;
    	
        $es = new entity_selector( );
        $es->add_type(id_of('type'));
        $es->set_order('entity.name ASC');
        $this->types = $es->run_one();
        return $this->types;
    }
    
    function get_type() {
    	if(!empty($this->admin_page->request['stats_type']))
    	{
    		$type_id = (integer) $this->admin_page->request['stats_type'];
    		$types = $this->get_all_types();
    		if(isset($types[$type_id]))
    		{
    			return $types[$type_id];
    		}
    	}
    	return 0;
    }

    function get_data($types) {
    	if(isset($this->data)) {
    		return $this->data;
    	}
    	$this->data = array();
    	foreach($types as $type)
    	{
    		$this->data[$type->id()] = $this->get_type_data($type);
    	}
    	return $this->data;
    }
    function get_type_data($type) {
    	return array(
    		'Sites' => count($this->get_sharing_sites($type)),
    		'Entities' => $this->get_shared_entities_count($type, $this->get_all_sharing_sites()),
    		'Borrows' => $this->get_borrowed_entities_count($type),
    	);
    }
    function get_type_examples($type) {
    	return array(
    		'Sites' => $this->get_sharing_sites($type),
    		'Entities' => $this->get_shared_entities_examples($type),
    		'Borrows' => $this->get_borrowed_entities_examples($type),
    	);
    }
    
    function get_sharing_sites($type, $num = -1)
    {
    	if(isset($this->sharing_sites[$type->id()])) {
    		return $this->sharing_sites[$type->id()];
    	}
    	$es = new entity_selector(id_of('master_admin'));
    	$es->add_type(id_of('site'));
    	$es->add_left_relationship($type->id(), relationship_id_of('site_shares_type'));
    	$es->limit_tables();
    	$es->set_order('RAND()');
    	$es->set_num($num);
    	$this->sharing_sites[$type->id()] = $es->run_one();
    	return $this->sharing_sites[$type->id()];
    }
    
    function get_shared_entities_es($type)
    {
    	$sites = $this->get_sharing_sites($type);
    	if(empty($sites))
    		return 0;
    	
    	$es = new entity_selector(array_keys($sites));
    	$es->add_type($type->id());
    	$es->add_relation('(entity.no_share IS NULL OR entity.no_share = 0)');
    	$es->limit_tables();
    	return $es;
    }
    
    function get_shared_entities_count($type)
    {
    	if( $es = $this->get_shared_entities_es($type) )
    	{
    		return $es->get_one_count();
    	}
    	return 0;
    }
    
    function get_shared_entities_examples($type)
    {
    	if( $es = $this->get_shared_entities_es($type) )
    	{
    		$es->set_num(20);
    		$es->set_order('RAND()');
    		return $es->run_one();
    	}
    	return array();
    }
    
    function get_borrowed_entities_es($type)
    {
    	if($rel_id = get_borrows_relationship_id($type->id()))
    	{
    		$rel_name = relationship_name_of($rel_id);
    		$es = new entity_selector();
    		$es->add_type($type->id());
    		$es->add_right_relationship_field($rel_name, 'entity', 'id', 'borrowing_site');
    		$es->enable_multivalue_results();
    		$es->limit_tables();
    		return $es;
    	}
    	return 0;
    }
    
    function get_borrowed_entities_count($type)
    {
    	if($es = $this->get_borrowed_entities_es($type))
    	{
    		return $es->get_one_count();
    	}
    	return 0;
    }
    
    function get_borrowed_entities_examples($type)
    {
    	if($es = $this->get_borrowed_entities_es($type))
    	{
    		$es->set_num(20);
    		$es->set_order('RAND()');
    		return $es->run_one();
    	}
    	return array();
    }
    
    function get_sums($data)
    {
    	$sums = array();
        foreach($data as $type_id => $counts)
        {
        	foreach($counts as $label => $count)
        	{
        		if(!isset($sums[$label]))
        			$sums[$label] = 0;
        		$sums[$label] += $count;
        	}
        }
        return $sums;
    }
	function get_all_sharing_sites()
	{
		static $sites;
		if(null === $sites)
		{
			$sites = array();
			foreach($this->get_all_types() as $type)
			{
				foreach($this->get_sharing_sites($type) as $site_id => $site)
				{
					$sites[$site_id] = $site;
				}
			}
		}
		return $sites;
	}
}

