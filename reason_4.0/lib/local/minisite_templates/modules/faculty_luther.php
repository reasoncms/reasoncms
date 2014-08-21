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

    function list_people( $people ) // {{{ // {{{
    {
        parent::list_people( $people );

        /* Volunteers attached to the page are added at the end */
        $es =  new entity_selector( $this->site_id );
        $es->add_type( id_of( 'volunteer_other_type' ));
        $es->add_right_relationship( $this->cur_page->id(), relationship_id_of( 'page_to_volunteer' ));
        $es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_volunteer'));
        $es->set_order('rel_sort_order');
        $volunteer_info = $es->run_one();
        if ( $volunteer_info ) {
            // $this->show_image_volunteer();
            foreach ($volunteer_info as $vi) {
                echo '<div class="facStaff">'."\n";
                $this->show_volunteer_image($vi);
                echo '<a name="'.$vi->get_value('name').'"></a>'."\n";
                echo '<h2 class="facStaffName">'.$vi->get_value('name').'</h2>'."\n";
                echo '<div class="facStaffInfo">'."\n";
                echo '<h3 class="facStaffTitle">'.$vi->get_value('title').'</h3>'."\n";
                echo '<ul class="facStaffContact">';
                if ($vi->get_value('location')) {
                    echo '<li class="facStaffOffice"><strong>Office:</strong> '.$vi->get_value('location').'</li>'."\n";
                }
                if ($vi->get_value('phone')){
                    echo '<li class="facStaffPhone"><strong>Phone:</strong> <a href="tel:' . $vi->get_value('phone') . '">' . $vi->get_value('phone') . '</a></li>' . "\n";
                }
                if ($vi->get_value('email')){
                    echo '<li class="facStaffEmail"><strong>E-mail:</strong> <a href="mailto:' . $vi->get_value('email') . '">' . $vi->get_value('email') . '</a></li>' . "\n";
                }
                if ($vi->get_value('content')){
                    echo '<div class="facStaffContent">' . $vi->get_value( 'content' )  . '</div>' . "\n";
                }
                echo '</div>'."\n";
                echo '</div>'."\n";
            }
        }
    }

    function show_person( $person ) // {{{
	{
        $this->show_image($person);
        echo '<a name="'.$person['ds_username'][0].'"></a>'."\n";
        echo '<h2 class="facStaffName">'.$person['full_name'];
        echo '</h2>'."\n";


        if( !empty( $person[ 'title' ])
            || !empty( $person[ 'ds_phone' ] )
            || !empty( $person[ 'mail' ] )
            || !empty( $person['content' ] ) )
        {
            echo '<div class="facStaffInfo">'."\n";
            if ( !empty( $person['title']))
            {
                echo '<h3 class="facStaffTitle">'.$person['title'].'</h3>'."\n";
            }

            echo '<ul class="facStaffContact">';

            if ($person['edupersonaffiliation'][0] != 'Emeritus')
            {

                if ( !empty ( $person['ds_office'] ) ){
                    echo '<li class="facStaffOffice"><strong>Office:</strong> ';
                    foreach ($person['ds_office'] as $office) {
                        echo preg_replace('/;/', ', ', $office);
                    }
                    echo '</li>' . "\n";

                }
                if ( !empty ( $person['ds_phone'] )){
                    echo '<li class="facStaffPhone"><strong>Phone:</strong> <a href="tel:' . preg_replace('/,/', ', ', $person['ds_phone']) . '">' . preg_replace('/,/', ', ', $person['ds_phone']) . '</a></li>' . "\n";
                }
            }

            if ( !empty ( $person['mail'] ))
            {
                echo '<li class="facStaffEmail"><strong>E-mail:</strong> <a href="mailto:' . $person['mail'] . '">' . $person['mail'] . '</a></li>' . "\n";
            }

            echo '</ul>';

            if (!empty( $person['content' ] ) )
            {
                echo '<div class="facStaffContent">' . $person[ 'content' ]  . '</div>' . "\n";
            }
            echo '</div>'."\n";
        }
    }

    function grab_volunteer_image( $person ) // {{{
    {
        $images = $person->get_left_relationship( 'volunteer_to_image' );
        if( $images ) {
            return $images[0]->id();
        }
        else
            return false;
    } // }}}

    function show_image( $person )
    {
        $image_id = '';
        if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) )
                $image_id = $this->grab_faculty_image( $this->reason_netids[ $person[ 'ds_username' ][0] ] );
        if (!empty($image_id))
        {
            echo "<figure class='facStaffImage'>";
    		$image = get_entity_by_id($image_id);
    		$url = WEB_PHOTOSTOCK . $image_id . '.' . $image['image_type'];
    		$thumb = WEB_PHOTOSTOCK . $image_id . '_tn.' . $image['image_type'];
    		if (!file_exists(preg_replace("|/$|", "", $_SERVER['DOCUMENT_ROOT']) . $thumb))
    		{
    			$thumb = $url;
    		}
    		$d = max($image['width'], $image['height']) / 125.0;
    		//echo '<div class="figure" style="width:' . intval($image['width']/$d) .'px;">';
    		echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
            echo '<img src="' . $thumb . '" border="0" alt="" title="Click to enlarge" />';
            echo '</a>';

            //show_image( $image_id, false,true,false );
            //echo "</div>\n";
            echo "</figure>\n";
        }
    }
    function show_volunteer_image( $person )
    {
        $image_id = '';
        // if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) )
        //         $image_id = $this->grab_faculty_image( $this->reason_netids[ $person[ 'ds_username' ][0] ] );
        $image_id = $this->grab_volunteer_image($person);
        if (!empty($image_id))
        {
            echo "<figure class='facStaffImage'>";
            $image = get_entity_by_id($image_id);
            $url = WEB_PHOTOSTOCK . $image_id . '.' . $image['image_type'];
            $thumb = WEB_PHOTOSTOCK . $image_id . '_tn.' . $image['image_type'];
            if (!file_exists(preg_replace("|/$|", "", $_SERVER['DOCUMENT_ROOT']) . $thumb))
            {
                $thumb = $url;
            }
            $d = max($image['width'], $image['height']) / 125.0;
            //echo '<div class="figure" style="width:' . intval($image['width']/$d) .'px;">';
            echo '<a href="'. $url . '" class="highslide" onclick="return hs.expand(this, imageOptions)">';
            echo '<img src="' . $thumb . '" border="0" alt="" title="Click to enlarge" />';
            echo '</a>';

            //show_image( $image_id, false,true,false );
            //echo "</div>\n";
            echo "</figure>\n";
        }
    }
}
?>