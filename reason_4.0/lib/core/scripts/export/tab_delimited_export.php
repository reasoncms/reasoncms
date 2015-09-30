<?php
/**
 * An attempt at a generalized tab-delimited data export tool for Reason
 *
 * This is still experimental, and it particularly needs work around authorization
 * -- at the moment it only allows administrators to export data
 *
 * @package reason
 * @subpackage scripts
 *
 * @todo improve authorization to allow reason users to export data in their sites
 * @todo perhaps change to CSV rather than tab-delimited?
 */
 
/**
 * Include dependencies
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity_selector.php' );
connectDB( REASON_DB );

// testing url: REASON_HTTP_BASE_PATH.scripts/export/tab_delimited_export.php?site_id=70230&type_id=31512&show_fields=name,datetime,content,location,hours,minutes,dates,registration&limit_field=event.registration&limit_value=full&limit_type=exact

reason_include_once( 'function_libraries/user_functions.php' );
force_secure_if_available();
$current_user = check_authentication();
$reason_user_id = get_user_id ( $current_user );

// Note: this is a temporary restriction in place until this tool comes out of experimental mode
if (!reason_user_has_privs( $reason_user_id, 'view_sensitive_data' ) )
{
	die('<h1>Sorry.</h1><p>You do not have permission to do an export.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
}
else
{

	if(!empty($_REQUEST['site_id']) && !empty($_REQUEST['type_id']) )
	{
		$site_id = $_REQUEST['site_id'];
		settype($site_id, 'integer');
		
		$type_id = $_REQUEST['type_id'];
		settype($type_id, 'integer');
		
		if(!empty($_REQUEST['show_fields']))
		{
			$showable_fields = explode(',',$_REQUEST['show_fields']);
		}
		
		$type = new entity($type_id);
		$site = new entity($site_id);
		
		$reason_user_entity = new entity($reason_user_id);
		
		if( reason_user_has_privs( $reason_user_id, 'view_sensitive_data' ) || $site->has_left_relation_with_entity( $reason_user_entity, 'site_to_user'))
		{
		
			$es = new entity_selector( $site_id );
			$es->add_type( $type_id );
			
			if(!empty($_REQUEST['limit_field']) && !empty($_REQUEST['limit_value']) )
			{
				$limit_field = reason_sql_string_escape($_REQUEST['limit_field']);
				$limit_value = reason_sql_string_escape($_REQUEST['limit_value']);
				if(empty($_REQUEST['limit_type']) || $_REQUEST['limit_type'] != 'exact')
				{
					$relation = $limit_field.' LIKE "%'.$limit_value.'%"';
				}
				else
				{
					$relation = $limit_field.' = "'.$limit_value.'"';
				}
				$output .= $relation;
				$es->add_relation($relation);
			}
			
			$items = $es->run_one();
			
			$first = true;
			
			$filename = strtolower(str_replace(' ','_',$site->get_value('name')).'_'.str_replace(' ','_',$type->get_value('plural_name')).'_'.date('Y_m_d').'.xls');
			
			
			$output = '';
			
			foreach($items as $item)
			{
				if($first)
				{
					if(!empty($showable_fields))
					{
						foreach($item->get_values() as $key=>$value)
						{
							if(in_array($key, $showable_fields))
							{
								$keys[] = $key;
							}
						}
						$output .= implode("\t", $keys);
					}
					else
					{
						$output .= implode("\t", array_keys($item->get_values()));
					}
					$output .= "\n";
				}
				$unclean_values = $item->get_values();
				$values = array();
				if(!empty($showable_fields))
				{
					foreach($unclean_values as $key=>$value)
					{
						if(in_array($key, $showable_fields))
						{
							$values[] = str_replace(array("\t","\n"),array(' ',' '),$value);
						}
					}
				}
				else
				{
					foreach($unclean_values as $key=>$value)
					{
						$values[] = str_replace(array("\t","\n"),array(' ',' '),$value);
					}
				}
				$output .= implode("\t",$values);
				$output .= "\n";
				$first = false;
			}
		}
		else
		{
			echo 'Sorry; you cannot get this export unless you are a member of the site';
		}
		if(!empty($output))
		{
			$size_in_bytes = strlen($output);
			//header('Content-Type: application/text; charset=utf-8');
			header('Content-Type: text/tab-separated-values; charset=utf-8');
			header('Content-Disposition: attachment; filename='.$filename.'; size='.$size_in_bytes);
			echo $output;
		}
	} // not sure where my tabbing went wrong. :(  (found it, fixed it -bcochran)
	else
	{
		echo 'A site id & type id must be set for this export. Please contact your Reason support person for assistance';
	}
}
?>
