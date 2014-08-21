<?php
/**
 * Register module with Reason and include dependencies
 */
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'VolunteerOtherModule';

reason_include_once( 'minisite_templates/modules/faculty_luther.php' );

class VolunteerOtherModule extends LutherFacultyStaffModule
{
    var $volunteer_info;

    function has_content()
    {
        $es =  new entity_selector( $this->site_id );
        $es->add_type( id_of( 'volunteer_other_type' ));
        $es->add_right_relationship( $this->cur_page->id(), relationship_id_of( 'page_to_volunteer' ));
        $es->add_rel_sort_field($this->cur_page->id(), relationship_id_of('page_to_volunteer'));
        $es->set_order('rel_sort_order');
        $this->volunteer_info = $es->run_one();
        if ( $this->volunteer_info ){
            return true;
        } else {
            return false;
        }
        return true;
    }

    function show_person()
    {
        echo '<div id="facultyStaff">'."\n";
        foreach ($this->volunteer_info as $vi) {
            $this->show_volunteer_image($vi);
            echo '<a name="'.$vi->get_value('name').'"></a>'."\n";
            echo '<h2 class="facStaffName">'.$vi->get_value('name').'</h2>'."\n";
            echo '<div class="facStaffInfo">'."\n";
            echo '<h3 class="facStaffTitle">'.$vi->get_value('title').'</h3>'."\n";
            echo '<ul class="facStaffContact">';
            echo '<li class="facStaffOffice"><strong>Office:</strong> '.$vi->get_value('location').'</li>'."\n";
            echo '<li class="facStaffPhone"><strong>Phone:</strong> ' . $vi->get_value('phone') . '</li>' . "\n";
            echo '<li class="facStaffEmail"><strong>E-mail:</strong> <a href="mailto:' . $vi->get_value('email') . '">' . $vi->get_value('email') . '</a></li>' . "\n";
            echo '<div class="facStaffContent">' . $vi->get_value( 'content' )  . '</div>' . "\n";
            echo '</div>'."\n";
        }
        echo '</div>';
    }
}
?>