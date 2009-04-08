<?php
/**
 * Create the quote type and relationships to pages and categories
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * Start script
 */
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Upgrade Reason: Quote Creator</title>
</head>

<body>
<?php
include ('reason_header.php');
include_once(CARL_UTIL_INC.'db/db_selector.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('function_libraries/util.php');
reason_include_once('function_libraries/user_functions.php');
reason_include_once('function_libraries/admin_actions.php');

class quoteCreatorb5b6
{
	var $mode;
	var $reason_user_id;
	
	var $quote_type_details = array (
		'new'=>0,
		'unique_name'=>'quote_type',
		'custom_content_handler'=>'quote.php',
		'plural_name'=>'Quotes');
	
	var $quote_to_category_details = array (
		'description'=>'Asset to Category',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'yes',
		'display_name'=>'Assign to Categories',
		'display_name_reverse_direction'=>'Quotes in this category',
		'description_reverse_direction'=>'Quotes in this category');
	
	var $page_to_quote_details = array (
		'description'=>'Association between page and quotes',
		'directionality'=>'bidirectional',
		'connections'=>'many_to_many',
		'required'=>'no',
		'is_sortable'=>'yes',
		'display_name'=>'Place Quotes',
		'display_name_reverse_direction'=>'Place on Pages',
		'description_reverse_direction'=>'Pages with this Quote');
		
	//type_to_default_view
	
	function do_updates($mode, $reason_user_id)
	{
		if($mode != 'run' && $mode != 'test')
		{
			trigger_error('$mode most be either "run" or "test"');
			return;
		}
		
		$this->mode = $mode;
		
		settype($reason_user_id, 'integer');
		if(empty($reason_user_id))
		{
			trigger_error('$reason_user_id must be a nonempty integer');
			return;
		}
		$this->reason_user_id = $reason_user_id;
		
		// The updates
		$this->add_quote_type();
		$this->add_page_to_quote_relationship();
		$this->add_quote_to_category_relationship();
		$this->ensure_quote_type_is_using_content_manager();
	}

	function add_quote_type()
	{
		if (reason_unique_name_exists('quote_type', false))
		{
			echo '<p>quote_type already exists. No need to create</p>'."\n";
			return false;
		}
		if($this->mode == 'run')
		{
			reason_create_entity(id_of('master_admin'), id_of('type'), $this->reason_user_id, 'Quote', $this->quote_type_details);
			
			if(reason_unique_name_exists($this->quote_type_details['unique_name'], false))// make sure to refresh cache
			{
				$type_id = id_of('quote_type');
				create_default_rels_for_new_type($type_id, $this->quote_type_details['unique_name']);
				$this->add_entity_table_to_type('meta', 'quote_type');
				$this->add_entity_table_to_type('chunk', 'quote_type');
				// setup its relationship to entity tables
				echo '<p>quote_types successfully created</p>'."\n";
			}
			else
			{
				echo '<p>Unable to create quote_type</p>';
			}
		}
		else
		{
			echo '<p>Would have created quote_type.</p>'."\n";
		}
	}
	
	function add_page_to_quote_relationship()
	{
		if (reason_relationship_name_exists('page_to_quote', false))
		{
			echo '<p>page_to_quote already exists. No need to update.</p>'."\n";
			return false;
		}
		if($this->mode == 'run')
		{
			$r_id = create_allowable_relationship(id_of('minisite_page'), id_of('quote_type'), 'page_to_quote', $this->page_to_quote_details);
			if($r_id)
			{
				echo '<p>page_to_quote allowable relationship successfully created</p>'."\n";
			}
			else
			{
				echo '<p>Unable to create page_to_quote allowable relationship</p>';
				echo '<p>You might try creating the relationship page_to_quote yourself in the reason administrative interface - it should include the following characteristics:</p>';
				pray ($this->page_to_quote_details);
			}
		}
		else
		{
			echo '<p>Would have created page_to_quote allowable relationship.</p>'."\n";
		}
	}	

	function add_quote_to_category_relationship()
	{
		if (reason_relationship_name_exists('quote_to_category', false))
		{
			echo '<p>quote_to_category already exists. No need to update.</p>'."\n";
			return false;
		}
		if($this->mode == 'run')
		{
			$r_id = create_allowable_relationship(id_of('quote_type'), id_of('category_type'), 'quote_to_category', $this->quote_to_category_details);
			if($r_id)
			{
				echo '<p>quote_to_category allowable relationship successfully created</p>'."\n";
			}
			else
			{
				echo '<p>Unable to create quote_to_category allowable relationship</p>';
				echo '<p>You might try creating the relationship quote_to_category yourself in the reason administrative interface - it should include the following characteristics:</p>';
				pray ($this->quote_to_category_details);
			}
		}
		else
		{
			echo '<p>Would have created quote_to_category allowable relationship.</p>'."\n";
		}
	}
	
	function ensure_quote_type_is_using_content_manager()
	{
		if (reason_unique_name_exists('quote_type', false))
		{
			$qt_id = id_of('quote_type');
			$qt = new entity($qt_id);
			if ($qt->get_value('custom_content_handler') != 'quote.php')
			{
				if ($this->mode == 'run')
				{
					reason_update_entity( $qt_id, $this->reason_user_id, $this->quote_type_details );
					echo '<p>Updated quote type to use correct content manager.</p>';
				}
				else
				{
					echo '<p>Would update quote type to use correct content manager.</p>';
				}
			}
			else
			{
				echo '<p>The quote type is using the correct content manager. No need to update.</p>';
			}
		}
	}
	
	function add_entity_table_to_type($et, $type)
	{
		$pub_type_id = id_of($type);	
		$es = new entity_selector( id_of('master_admin') );
		$es->add_type( id_of('content_table') );
		$es->add_right_relationship($pub_type_id, relationship_id_of('type_to_table') );
		$es->add_relation ('entity.name = "'.$et.'"');
		$entities = $es->run_one();
		if (empty($entities))
		{
			$es2 = new entity_selector();
			$es2->add_type(id_of('content_table'));
			$es2->add_relation('entity.name = "'.$et.'"');
			$es2->set_num(1);
			$tables = $es2->run_one();
			if(!empty($tables))
			{
				$table = current($tables);
				create_relationship($pub_type_id,$table->id(),relationship_id_of('type_to_table'));
				return true;
			}
		}
		return false;
	}
}

force_secure_if_available();

$user_netID = reason_require_authentication();

$reason_user_id = get_user_id( $user_netID );

if(empty($reason_user_id))
{
	die('valid Reason user required');
}

if(!reason_user_has_privs( $reason_user_id, 'upgrade' ) )
{
	die('You must have Reason upgrade rights to run this script');
}

?>
<h2>Reason: create quote type</h2>
<p>What will this update do?</p>
<ul>
<li>Create the quote type.</li>
<li>Create the quote to category relationship.</li>
<li>Create the page_to_quote relationship.</li>
<li>Ensure quote type is using quote content manager.</li>
</ul>
<form method="post"><input type="submit" name="go" value="test" /><input type="submit" name="go" value="run" /></form>
<?php

if(!empty($_POST['go']) && ($_POST['go'] == 'run' || $_POST['go'] == 'test'))
{
	if($_POST['go'] == 'run')
		echo '<p>Running updater...</p>'."\n";
	else
		echo '<p>Testing updates...</p>'."\n";
		
	$updater = new quoteCreatorb5b6();
	$updater->do_updates($_POST['go'], $reason_user_id);
}

?>
</body>
</html>
