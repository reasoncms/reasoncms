<?php
	include_once( 'reason_header.php' );
	include_once( DISCO_INC.'disco_db.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	connectDB( REASON_DB );

	$mydb = get_db_credentials(REASON_DB);
        $disco_db = $mydb['db'];
	
	//set $id
	if (!empty($_REQUEST['id']))
	{
		if ($_REQUEST['id'] != 'new');
		settype($_REQUEST['id'], 'integer');
		$id = $_REQUEST['id'];
	}
	else $id = '';

	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
	echo '<html><head><title>Reason: Edit Allowable Relationship</title>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
	{
		echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
	}
	echo '<link rel="stylesheet" type="text/css" href="'.REASON_ADMIN_CSS_DIRECTORY.'admin.css" /></head><body><div id="allRels">'."\n";

        // checks for both http and cookie based authentication - nwhite
        force_secure_if_available();
	$current_user = check_authentication();
        if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to edit allowable relationships.</p><p>Only Reason users who have the Administrator role may do that.</p></div></body></html>');
	}

	class AlRelManager extends DiscoDB
	{
		var $error_checks = array( 'directionality' => array( 'dir_validation' => 'One To Many and Many to One relationships cannot be bidirectional' ),
		'name' => array( 'name_validation' => 'Names must contain only letters, numbers, and/or underscores.  Please make sure the name doesn\'t contain any other characters.',
		'name_uniqueness_check' => 'The name of the allowable relationship must be unique.' )
		);
		function finish()
		{
			return securest_available_protocol() .'://'.HTTP_HOST_NAME.REASON_HTTP_BASE_PATH.'scripts/alrel/alrel_manager.php';
		}
		function dir_validation()
		{
			if( $this->get_value( 'directionality' ) == 'bidirectional' AND
			    ($this->get_value( 'connections' ) == 'one_to_many'  ||
			    $this->get_value( 'connections' ) == 'many_to_one' ))
			{
				return false;
			}
			return true;
		}
		function name_validation()
		{
			if(!eregi( "^[0-9a-z_]*$" , $this->get_value('name') ) )
			{
				return false;
			}
			return true;
		}
		function name_uniqueness_check()
		{
			$ok_to_duplicate = array('owns','borrows');
			if(in_array($this->get_value('name'),$ok_to_duplicate))
			{
				return true;
			}
			$dbs = new DBSelector();
			$dbs->add_table('allowable_relationship');
			$dbs->add_field('allowable_relationship','name','name');
			$dbs->add_relation('`name` = "'.addslashes($this->get_value('name')).'"');
			$dbs->add_relation('`id` != "'.$this->_id.'"');
			$results = $dbs->run('Error getting other names');
			if(!empty($results))
			{
				return false;
			}
			return true;
		}
	}

	$connection_description = '<h4>Connection Definitions</h4><dl>';
	$connection_description .= '<dt>Many to Many</dt><dd>Both entities A and B may have more than one relationship of this type</dd>';
	$connection_description .= '<dt>Many to One</dt><dd>Entity B may not have more than one relationship of this type, but entity A may be related to multiple entities B.</dd>';
	$connection_description .= '<dt>One to Many</dt><dd>Entity A may not have more than one relationship of this type, but entity B may be related to multiple entities A.</dd></dl>';
	$f = new AlRelManager;
	$f->load( $disco_db,'allowable_relationship',($id == 'new') ? '' : $id );
	$f->init();
	$f->add_required( 'relationship_a' );
	$f->set_comments('relationship_a',form_comment('The type on the left side of the relationships created under this alrel. Note that the A side item is often considered the primary item (for example, B side items may be sortable within the contex of the A side item, but not vice versa.)'));
	$f->add_required( 'relationship_b' );
	$f->set_comments('relationship_b',form_comment('The type on the right side of the relationships created under this alrel. Note that the B side item is often considered the secondary item  (see note above.)'));
	$f->add_required( 'name' );
	$f->set_comments('name',form_comment('A unique name for the allowable relationship. This field may only contain letters, numbers, and underscores.'));
	$f->add_required( 'required' );
	$f->set_comments('required',form_comment('Setting Required to "yes" will force users to select at least one B entity across this allowable relationship when they create an A entity.'));
	$f->add_required( 'connections' );
	$f->set_comments('connections', form_comment($connection_description));
	$f->set_comments('display_name',form_comment('This is the text that will be used on the A side as the link to manage relationships of this type. (For example, if the relationship is page=>image, this text would be visible in the context of the page.) This text is also used as a heading above the list of B items when previewing an A item.'));
	$f->set_comments('display_name_reverse_direction',form_comment('This is the text that will be used on the B side as the link to manage relationships of this type. (For example, if the relationship is page=>image, this text would be visible in the context of the image.)'));
	$f->set_comments('custom_associator',form_comment('Entering text into this field will <strong>turn off</strong> Reason\'s automatic relationship features. Only enter text here if you have built some other method of managing the relationship.'));
	$f->set_comments('description',form_comment('More info about the relationship. Just used to help others understand what the allowable relationship is for.'));
	$f->add_required( 'directionality' );
	$f->set_comments('directionality',form_comment('Unidirectional relationships can only be created from the A side; Bidirectional relationships may be created from either the A side or the B side. Before you set a allowable relationship to be bidirectional you should audit the code to make sure that all entity selectors that select across the relationship are given the current site environment.'));
	$f->set_comments('description_reverse_direction',form_comment('The text that is used as a heading above the list of A items when previewing a B item.'));
	$f->add_required( 'is_sortable' );
	$f->set_comments('is_sortable',form_comment('Answering "yes" will enable relationship-based sorting across relationships of this type. You will still need to alter the appropriate code to pay attention to the relationship sort order field before this has any effect on the front end.'));
	// get types
	$es = new entity_selector();
	$es->add_type( id_of( 'type' ) );
	$tmp = $es->run_one();
	// format into a usable form
	foreach( $tmp AS $ent )
		$types[ $ent->id() ] = $ent->get_value( 'name' );
	$f->change_element_type( 'relationship_a','select',array('options'=>$types) );
	$f->change_element_type( 'relationship_b','select',array('options'=>$types) );
	$f->set_order(array('name','description','relationship_a','relationship_b','connections','directionality','required','is_sortable','display_name','display_name_reverse_direction','description_reverse_direction','custom_associator'));
	
	echo '<h1>Reason: Edit Allowable Relationship</h1>'."\n";
	$f->run();
	echo '</div></body></html>'."\n";
?>
