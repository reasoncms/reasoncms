<?php
/**
 * Register module with Reason and include dependencies
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LutherFacultyStaffModule';

reason_include_once( 'minisite_templates/modules/faculty.php' );
include_once( CARL_UTIL_INC . 'dir_service/directory.php' );

class LutherFacultyStaffModule extends FacultyStaffModule
{
/*  
    var $directory_people = array();
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
    var $required_attributes = array('ds_email','ds_fullname','ds_lastname','ds_affiliation','ds_phone', 'ds_office', 'ds_title', 'ds_gecos', 'ds_cn');

    function show_person( $person ) // {{{
	{
        $this->show_image($person);
        echo '<a name="'.$person['ds_username'][0].'"></a>'."\n";
        echo '<div class="facStaffName"><h5>'.$person['full_name'];
        echo '</h5></div>'."\n";

        pray($person);
                    
        if( !empty( $person[ 'title' ])
            || !empty( $person[ 'ds_phone' ] )
            || !empty( $person[ 'mail' ] )
            || !empty( $person['content' ] ) )
        {
            echo '<div class="facStaffInfo">'."\n";
            if ( !empty( $person['title']))
                echo '<div class="facStaffTitle"><h6>'.$person['title'].'</h6></div>'."\n";
            if ($person['edupersonaffiliation'][0] != 'Emeritus')
            {
                if ( !empty ( $person['ds_office'] ) ){
                    echo '<div class="facStaffOffice">Office: ';
                    foreach ($person['ds_office'] as $office) {
                        echo preg_replace('/;/', ', ', $office);
                    }
                    echo '</div>' . "\n";

                }
                if ( !empty ( $person['ds_phone'] )){
                    echo '<div class="facStaffPhone">Phone: ' . preg_replace('/,/', ', ', $person['ds_phone']) . '</div>' . "\n";
                }
            }
            if ( !empty ( $person['mail'] ))
            {
                echo '<div class="facStaffEmail">E-mail: <a href="mailto:' . $person['mail'] . '">' . $person['mail'] . '</a></div>' . "\n";
            }
            if (!empty( $person['content' ] ) )
            {
                echo '<div class="facStaffContent">' . $person[ 'content' ]  . '</div>' . "\n";
            }
            echo '</div>'."\n";
        }
    }

    function show_image( $person )
    {
        $image_id = '';
        if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) )
                $image_id = $this->grab_faculty_image( $this->reason_netids[ $person[ 'ds_username' ][0] ] );
        if (!empty($image_id))
        {
            echo "\t<div class='facStaffImage'>";
    		$image = get_entity_by_id($image_id);
    		$url = WEB_PHOTOSTOCK . $image_id . '.' . $image['image_type'];
    		$thumb = WEB_PHOTOSTOCK . $image_id . '_tn.' . $image['image_type'];
    		if (!file_exists(preg_replace("|/$|", "", $_SERVER['DOCUMENT_ROOT']) . $thumb))
    		{
    			$thumb = $url;
    		}
    		$d = max($image['width'], $image['height']) / 125.0;
    		echo '<div class="figure" style="width:' . intval($image['width']/$d) .'px;">';
    		echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
            echo '<img src="' . $thumb . '" border="0" alt="" title="Click to enlarge" />';
            echo '</a>';

            //show_image( $image_id, false,true,false );
            echo "</div>\n";
            echo "</div>\n";
        }
    }
}
?>
