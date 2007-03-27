<?php
	include_once( 'reason_header.php' );
	reason_include_once( 'classes/entity_selector.php' );
	reason_include_once( 'function_libraries/user_functions.php' );
	include_once( DISCO_INC.'disco_db.php' );
	connectDB( REASON_DB );
	
	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
	echo '<html><head><title>Reason: Allowable Relationship Manager</title>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
	if (defined('UNIVERSAL_CSS_PATH') && UNIVERSAL_CSS_PATH != '')
	{
		echo '<link rel="stylesheet" type="text/css" href="'.UNIVERSAL_CSS_PATH.'" />'."\n";
	}
	echo '<link rel="stylesheet" type="text/css" href="'.REASON_ADMIN_CSS_DIRECTORY.'admin.css" /></head><body><div id="allRels">'."\n";
	
	//if(
	//	empty($_SERVER[ 'REMOTE_USER' ])
	//	||
	//	!user_is_a( get_user_id ( $_SERVER[ 'REMOTE_USER' ] ), id_of('admin_role') )
	//)

	// checks for both http and cookie based authentication - nwhite
	if(!on_secure_page())
        { 
                force_secure();
        }
        $current_user = check_authentication();
	if (!user_is_a( get_user_id ( $current_user ), id_of('admin_role') ) )
	{
		die('<h1>Sorry.</h1><p>You do not have permission to edit allowable relationships.</p><p>Only Reason users who have the Administrator role may do that.</p></body></html>');
	}
	
	$orderables = array('id'=>'ID','relationship_a'=>'Left','relationship_b'=>'Right','name'=>'Name','required'=>'Req\'d','connections'=>'Connect','directionality'=>'Dir');
	$directions = array('ASC'=>'^','DESC'=>'v');
	$value_cleanup = array(
		'required'=>array('yes'=>'Required','no'=>'Not Required'),
		'connections'=>array('many_to_many'=>'Many - Many','one_to_many'=>'One - Many','many_to_one'=>'Many - One'),
		'directionality'=>array('unidirectional'=>'Uni-','bidirectional'=>'Bi-'),
		);
	
	$q = 'SELECT * FROM allowable_relationship';
	if(!empty($_REQUEST['order_by']) && array_key_exists($_REQUEST['order_by'], $orderables))
	{
		$order = $_REQUEST['order_by'];
	}
	else
		$order = 'id';
	
	if(!empty($_REQUEST['direction']) && array_key_exists($_REQUEST['direction'], $directions))
	{
		$direction = $_REQUEST['direction'];
	}
	else
		$direction = 'ASC';
	
	$q .= ' ORDER BY '.$order.' '.$direction;
	$r = db_query( $q, 'Unable to retrieve allowable relationships' );
	
	$es = new entity_selector();
	$es->add_type(id_of('type'));
	$types = $es->run_one();

	echo '<h1>Allowable Relationships</h1>'."\n";
	echo '<p><a href="https://'.REASON_WEB_ADMIN_PATH.'">Reason Admin</a> | <a href="edit_alrel.php?id=new">Add New Allowable Relationship</a></p>'."\n";
	echo '<table cellpadding="5" cellspacing="0">'."\n";
	echo '<tr>';
	foreach($orderables as $key=>$value)
	{
		if($order == $key)
		{
			$dirs_temp = $directions;
			unset($dirs_temp[$direction]);
			echo '<th><a href="alrel_manager.php?order_by='.$key.'&amp;direction='.key($dirs_temp).'">'.$value.'</a> '.$directions[$direction].'</th>';
		}
		else
			echo '<th><a href="alrel_manager.php?order_by='.$key.'&amp;direction=ASC">'.$value.'</a></th>';
	}
	echo '<th>Delete</th></tr>'."\n";
	$class = 'odd';
	while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
	{
		echo '<tr class="'.$class.'">';
		echo '<td class="id">'.$row['id'].'</td>';
		echo '<td class="a"><div>'.$row['relationship_a'].'</div><div class="smallText">';
		if(!empty($types[$row['relationship_a']]))
			echo $types[$row['relationship_a']]->get_value('name');
		else
			echo '<strong>No Type</strong>';
		echo '</div></td>';
		echo '<td class="b"><div>'.$row['relationship_b'].'</div><div class="smallText">';
		if(!empty($types[$row['relationship_b']]))
			echo $types[$row['relationship_b']]->get_value('name');
		else
			echo '<strong>No Type</strong>';
		echo '</div></td>';
		echo '<td class="name"><div><a href="edit_alrel.php?id='.$row['id'].'">'.$row['name'].'</a></div><div>'.$row['description'].'</div></td>';
		
		if(!empty($value_cleanup['required'][$row['required']]))
			$value = $value_cleanup['required'][$row['required']];
		else
			$value = $row['required'];
		echo '<td class="required">'.$value.'</td>';
		
		if(!empty($value_cleanup['connections'][$row['connections']]))
			$value = $value_cleanup['connections'][$row['connections']];
		else
			$value = $row['connections'];
		echo '<td class="connections">'.$value.'</td>';
		
		if(!empty($value_cleanup['directionality'][$row['directionality']]))
			$value = $value_cleanup['directionality'][$row['directionality']];
		else
			$value = $row['directionality'];
		echo '<td class="directionality">'.$value.'</td>';
		echo '<td class="delete"><a href="del_alrel.php?id='.$row['id'].'">Delete</a></td>';
		echo '</tr>'."\n";
		if( $class == 'odd' )
			$class = 'even';
		else
			$class = 'odd';
	}
	echo '</table>'."\n";
	echo '</div></body></html>'."\n";
?>
