<?php
    /**
     * Announcements
     *
     * Provides a way to add a prominent announcement to an entire site via the Master Admin
     *
     * @package reason
     * @subpackage minisite_modules
     * @author Steve Smith
     */
     
    /**
     * Include the base module & register with Reason
     */
    reason_include_once( 'minisite_templates/modules/default.php' );
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LisSiteAnnouncementsModule';
    /**
     * LIS Outage Announcements Module
     * Adds a prominent outage announcement to LIS site pages and subsite pages
     * 
     * @todo this is kind of hacky and should be reworked so any site could have an announcement * blurb in the announcement(s) divs on its pages
     */
    class LisSiteAnnouncementsModule extends DefaultMinisiteModule
    {   
        var $lis_announcement;
        var $library_announcement;
        var $helpdesk_announcement;
        var $archives_announcement;

        function init( $args = array() )
        {
            $this->lis_announcement = get_text_blurb_content('lis_site_announcement');
            $this->library_announcement = get_text_blurb_content('library_site_announcement');
            $this->helpdesk_announcement = get_text_blurb_content('helpdesk_site_announcement');
            $this->archives_announcement = get_text_blurb_content('archives_site_announcement');
        }
        function has_content()
        {
            if ($this->site_id == id_of('lis')
                || $this->site_id == id_of('archives')
                || $this->site_id == id_of('library')
                || $this->site_id == id_of('helpdesk')){
                if ($this->lis_announcement == NULL 
                    && $this->helpdesk_announcement == NULL 
                    && $this->library_announcement == NULL 
                    && $this->archives_announcement == NULL)
                    return false;
                else 
                    return true;
            }
        }

        function run()
        {
            echo '<div id="announcements">'."\n";
            if ($this->lis_announcement != NULL)
                echo '<div class="announcement">'.$this->lis_announcement.'</div>'."\n";
            if ($this->helpdesk_announcement != NULL)
                echo '<div class="announcement">'.$this->helpdesk_announcement.'</div>'."\n";
            if ($this->library_announcement != NULL)
                echo '<div class="announcement">'.$this->library_announcement.'</div>'."\n";
            if ($this->archives_announcement != NULL)
                echo '<div class="announcement">'.$this->archives_announcement.'</div>'."\n";
            echo '</div>'."\n";
        }
    }