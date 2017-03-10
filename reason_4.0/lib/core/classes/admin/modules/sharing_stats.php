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
        $types = $this->get_all_types();
		$data = $this->get_data($types);
		$sums = $this->get_sums($data);
		
		$sum_labels = array(
			'Sites' => 'Number of site/type shares',
			'Entities' => 'Number of entities shared in total',
			'Borrows' => 'Number of entities borrowed in total',
		);
		
        foreach($sums as $label => $count)
        {
        	$label = isset($sum_labels[$label]) ? $sum_labels[$label] : $label;
        	echo '<p>' . $label . ': ' . $count . '</p>';
        }
        $unique_sharing_sites_count = count($this->get_all_sharing_sites());
        echo '<p>Unique sites sharing anything: ' . $unique_sharing_sites_count . '</p>';
        echo '<p>Average number of types shared per site: ' . round($sums['Sites']/$unique_sharing_sites_count, 2) . '</p>';
        echo '<p>Average number of entities shared per site: ' . round($sums['Entities']/$unique_sharing_sites_count, 2) . '</p>';
        $deferred = array();
        foreach($types as $type)
        {
        	$defer = true;
        	$str = '';
        	$str .= '<h3>'.$type->get_display_name().'</h3>';
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
        		echo $str;
        	}
        }
        if(!empty($deferred))
        {
        	echo '<h3>Types with no shares</h3>';
        	echo '<ul>';
        	foreach($deferred as $type)
        	{
        		echo '<li>'.$type->get_display_name().'</li>';
        	}
        	echo '</ul>';
        }
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
    
    	$sites = $this->get_sharing_sites($type);
    	
    	return array(
    		'Sites' => count($sites),
    		'Entities' => $this->get_shared_entities_count($type, $sites),
    		'Borrows' => $this->get_borrowed_entities_count($type),
    	);
    }
    
    function get_sharing_sites($type)
    {
    	if(isset($this->sharing_sites[$type->id()])) {
    		return $this->sharing_sites[$type->id()];
    	}
    	$es = new entity_selector(id_of('master_admin'));
    	$es->add_type(id_of('site'));
    	$es->add_left_relationship($type->id(), relationship_id_of('site_shares_type'));
    	$es->limit_tables();
    	$this->sharing_sites[$type->id()] = $es->run_one();
    	return $this->sharing_sites[$type->id()];
    }
    
    function get_shared_entities_count($type, $sites)
    {
    	if(empty($sites))
    		return 0;
    	
    	$es = new entity_selector(array_keys($sites));
    	$es->add_type($type->id());
    	$es->add_relation('(entity.no_share IS NULL OR entity.no_share = 0)');
    	$es->limit_tables();
    	return $es->get_one_count();
    }
    
    function get_borrowed_entities_count($type)
    {
    	if($rel_id = get_borrows_relationship_id($type->id()))
    	{
    		$rel_name = relationship_name_of($rel_id);
    		$es = new entity_selector();
    		$es->add_type($type->id());
    		$es->add_right_relationship_field($rel_name, 'entity', 'id', 'borrowing_site');
    		$es->enable_multivalue_results();
    		$es->limit_tables();
    		return $es->get_one_count();
    	}
    	return 0;
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
		$sites = array();
		foreach($this->get_all_types() as $type)
		{
			foreach($this->get_sharing_sites($type) as $site_id => $site)
			{
				$sites[$site_id] = $site;
			}
		}
		return $sites;
	}
}

