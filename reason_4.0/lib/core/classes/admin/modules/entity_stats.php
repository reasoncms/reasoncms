<?php

/**
 * @package reason
 * @subpackage admin
 */
/**
 * Include the default module and other needed utilities
 */
reason_include_once('classes/admin/modules/default.php');

/**
 * An administrative module that displays counts of entities by type and status.
 */
class ReasonEntityStatsModule extends DefaultModule
{
    protected $types = array();
    protected $counts = array();
    protected $statuses = array('Live','Pending','Archived');

    function ReasonEntityStatsModule(&$page) {
        $this->admin_page = & $page;
    }

    function init()
    {
        $this->admin_page->title = 'Entity Stats';
        $this->get_counts();
    }

    function run()
    {
        if (!reason_user_has_privs($this->admin_page->user_id, 'view_sensitive_data')) {
            echo 'Sorry; you do not have the rights to view this information.';
            return;
        }

        echo '<table cellspacing="0" cellpadding="8">' . "\n";
        echo '<thead><tr>' . "\n";
        echo '<th class="listHead">Type</th>';
        foreach ($this->statuses as $status) 
        {
            echo '<th class="listHead">'.$status.'</th>' . "\n";
        }
        echo '</tr></thead>' . "\n";
        echo '<tbody>' . "\n";
        foreach ($this->counts as $type => $counts) {
            echo '<tr class="listRow1">' . "\n";
            echo '<td>' . $type . '</td>';
            foreach ($this->statuses as $status) 
            {
                echo '<td>'.$counts[$status].'</td>' . "\n";
            }
            echo '</tr>' . "\n";
        }
        echo '</tbody>' . "\n";
        echo '</table>' . "\n";
    }

    function get_all_types() {
        $es = new entity_selector( );
        $es->add_type(id_of('type'));
        $es->set_order('entity.name ASC');
        $this->types = $es->run_one();
    }

    function get_counts() {
        $this->get_all_types();

        $totals = array();
        
        foreach ($this->types as $type) {
            $es = new entity_selector( );
            $es->add_type($type->id());
            foreach ($this->statuses as $status)
            {
                $this->counts[$type->get_value('name')][$status] = $es->get_one_count($status);
                $totals[$status] = (isset($totals[$status])) ? $totals[$status] + $this->counts[$type->get_value('name')][$status] : $this->counts[$type->get_value('name')][$status];
            }
        }
        $this->counts['Total'] = $totals;
    }

}

