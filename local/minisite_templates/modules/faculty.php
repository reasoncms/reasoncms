<?php
	/**
	 * Register module with Reason and include dependencies
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FacultyStaffModule';

	reason_include_once( 'minisite_templates/modules/faculty.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

function show_person( $person ) // {{{
		{
			$this->show_image($person);
			echo '<a name="'.$person['ds_username'][0].'"></a>'."\n";
			echo '<div class="facStaffName">'.$person['full_name'];
			echo '</div>'."\n";
			if( !empty( $person[ 'ds_title' ])
				|| !empty( $person[ 'ds_phone' ] )
				|| !empty( $person[ 'mail' ] )
				|| !empty( $person['content' ] ) )
			{
				echo '<div class="facStaffInfo">'."\n";
				if ( !empty( $person['ds_title']))
					echo '<div class="facStaffTitle">'.$title['ds_title'].'</div>'."\n";
				if ( !empty ( $person['ds_phone'] ))
					echo '<div class="facStaffPhone">Phone: '.$person['ds_phone'].'</div>'."\n";
				if ( !empty ( $person['mail'] ))
				{
					echo '<div class="facStaffEmail">Email: <a href="mailto:'.$person['mail'].'">'.$person['mail'].'</a></div>'."\n";
				}
				if (!empty( $person['content' ] ) )
				{
					echo '<div class="facStaffContent">' . $person[ 'content' ]  . '</div>'."\n";
				}
				echo '</div>'."\n";
			}
		}
?>
