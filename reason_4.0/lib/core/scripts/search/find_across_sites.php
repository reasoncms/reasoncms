<?php
/**
 * Find entities that match a given string in *any* field on *any* site
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
include_once('reason_header.php');
include_once(DISCO_INC .'disco.php');
reason_include_once( 'classes/entity_selector.php');

class DiscoSearcher extends Disco
{
	function where_to()
	{
		return ( '?search_string=' . urlencode($this->get_value('search_string')));
	}
}

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to search across sites.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
echo '<html><head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<title>Find Something in Reason</title>';
if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
{
	echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
}
echo '<style type="text/css">
	body { margin:1.5em; }
	span.hit { background-color:#FFFF99; }
	table td { border-top:1px solid #ffffff; vertical-align:top; background-color:#C8D5EF; }
	table tr.odd td { background-color:#E3EDFF; }
	table th { background-color:#B2C3E3; }
	table td ul, table td ul li { margin-top:0px; margin-bottom:.5em; }
</style>';
echo '</head><body>';
echo '<a name="top" id="top"></a><h1>Find Something in Reason</h1><p>This tool will find the entities in Reason that contain the search string in any field. This tool is not case sensitive.</p>';
$d = new DiscoSearcher;
$d->add_element('search_string');
if(!empty($_REQUEST['search_string']))
{
	$d->set_value('search_string', $_REQUEST['search_string']);
}
$d->actions = array('Search');
$d->run();
if(!empty($_REQUEST['search_string']))
{
	$sql_search_string = str_replace('"','\"',$_REQUEST['search_string']);
	$use_fields = array('id','name','last_modified');
	$es = new entity_selector();
	$es->add_type(id_of('type'));
	$types = $es->run_one();

	echo '<h2>Search results</h2>';
	$hit_count = 0;
	$txt = '';
	
	foreach($types as $type)
	{
		//echo $type->get_value('name').'<br />';
		$tables = get_entity_tables_by_type( $type->id() );
		$es = new entity_selector();
		$es->add_type($type->id());
		$tables = get_entity_tables_by_type( $type->id() );
		//pray($tables);
		$relation_pieces = array();
		foreach($tables as $table)
		{
			$fields = get_fields_by_content_table( $table );
			//pray($fields);
			foreach($fields as $field)
			{
				$relation_pieces[] = $table.'.'.$field.' LIKE "%'.$sql_search_string.'%"';
			}
		}
		$relation = '( '.implode(' OR ',$relation_pieces).' )';
		//echo '<p>'.$relation.'</p>';
		$es->add_relation($relation);
		//$es->add_relation('* LIKE "%'.$_REQUEST['search_string'].'%"');
		$entities = $es->run_one();
		if(!empty($entities))
		{
			$txt .= '<h3>'.$type->get_value('name').'</h3>'."\n";
			$txt .= '<table cellpadding="5" cellspacing="0">'."\n";
			$txt .= '<tr>';
			foreach($use_fields as $field)
			{
				$txt .= '<th>'.$field.'</th>';
			}
			$txt .= '<th>Owned By</th>';
			$txt .= '<th>Search Hits</th>';
			$txt .= '</tr>';
			$class = 'odd';
			foreach($entities as $e)
			{
				$txt .= '<tr class="'.$class.'">';
				foreach($use_fields as $field)
				{
					if($field == 'last_modified')
					{
						$txt .= '<td>'.date('j M Y',get_unix_timestamp($e->get_value($field))).'</td>'."\n";
					}
					else
					{
						$txt .= '<td>'.$e->get_value($field).'</td>'."\n";
					}
				}
				$txt .= '<td>';
				// This is the one thing that could make for poor performance if there are a lot of results
				// I'm not savvy enough yet to know how to include the owner info in the original query
				$owner_site_id = get_owner_site_id( $e->id() );
				if(!empty($owner_site_id))
				{
					$owner_site = new entity(get_owner_site_id( $e->id() ) );
					$txt .= '<a href="'.$owner_site->get_value('base_url').'">';
					$txt .= $owner_site->get_value('name');
					$txt .= '</a>';
				}
				else
				{
					$txt .= 'Orphan Entity -- does not have an owner site';
				}
				$txt .= '</td>';
				$txt .= '<td>';
				$txt .= '<ul>';
				foreach($e->get_values() as $key=>$value)
				{
					if(stristr($value,$_REQUEST['search_string']))
					{
						
						if(function_exists('str_ireplace'))
							$value = str_ireplace($_REQUEST['search_string'],'<span class="hit">'.$_REQUEST['search_string'].'</span>',$value);
						else
							$value = preg_replace('/('.preg_quote($_REQUEST['search_string']).')/i','<span class="hit">\\0</span>',$value);
						$txt .= '<li><strong>'.$key.':</strong> '.$value.'</li>';
					}
				}
				$txt .= '</ul>';
				$txt .= '</td>';
				$txt .= '</tr>'."\n";
				if( $class == 'odd' )
				{
					$class = 'even';
				}
				else
				{
					$class = 'odd';
				}
				$hit_count++;
			}
			$txt .= '</table>'."\n";
		}
	}
	if($hit_count < 1)
	{
		$txt .= '<p>No fields in Reason matched your search request.</p>'."\n";
	}
	else
	{
		$txt .= '<p><a href="#top">Top of Page</a></p>'."\n";
		echo '<p>Total matches: '.$hit_count.'</p>';
		echo $txt;
	}
}

echo '</body></html>';
?>
