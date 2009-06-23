<?php
	/**
	 * Register module with Reason and include dependencies
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFacultyStaffModule';

	reason_include_once( 'minisite_templates/modules/faculty.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

class LutherFacultyStaffModule extends FacultyStaffModule
        {
/*                var $directory_people = array();
                var $directory_netids = array();
                var $reason_people = array();
                var $reason_netids = array();
                var $reason_people_dir_info = array();
                var $all_people = array();
                var $affiliations = array();
                var $affiliation_from_directory = array();
                var $sorted_people = array();
                var $heads = true;
                var $other_affiliation_flag = false;
                var $affiliations_to_use_other_aff_flag = array();
*/
                var $required_attributes = array('ds_email','ds_fullname','ds_lastname','ds_affiliation','ds_phone', 'ds_title', 'ds_gecos', 'ds_cn');

function show_person( $person ) // {{{
		{
			
			$this->show_image($person);
			echo '<a name="'.$person['ds_username'][0].'"></a>'."\n";
			echo '<div class="facStaffName">'.$person['full_name'];
			echo '</div>'."\n";
						
			if( !empty( $person[ 'title' ])
				|| !empty( $person[ 'ds_phone' ] )
				|| !empty( $person[ 'mail' ] )
				|| !empty( $person['content' ] ) )
			{
				echo '<div class="facStaffInfo">'."\n";
				if ( !empty( $person['title']))
					echo '<div class="facStaffTitle">Title:'.$person['title'].'</div>'."\n";
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
}
?>
