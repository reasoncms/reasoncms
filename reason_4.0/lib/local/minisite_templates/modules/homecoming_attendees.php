<?php
reason_include_once( 'minisite_templates/modules/default.php' );
include_once( THOR_INC . 'thor.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'HomecomingAttendeesModule';


class HomecomingAttendeesModule extends DefaultMinisiteModule
{
    var $_form;

    function init( $args = array())
    {
        if($head_items =& $this->get_head_items())
        {
            $head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/js/jquery.tablesorter.min.js');
            $head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/css/theme.blue.css');
            $head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/jquery.tablesorter.pager.min.js');
            $head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/jquery.tablesorter.pager.css');
            $head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/js/jquery.tablesorter.widgets.min.js');
            $head_items->add_javascript(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/js/jquery.tablesorter.widgets-filter-formatter.min.js');
            $head_items->add_stylesheet(REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/css/filter.formatter.css');
            $head_items->add_javascript('/reason/local/js/homecoming_attendees.js');
        }

        $this->get_form();
    }

    function has_content()
    {
        return ($this->get_form());
    }

    function get_form()
    {
        if (!isset($this->_form))
        {
            $this->_form = false;
            // Get the form entity attached to the current page
            $es = new entity_selector();
            $es->add_type( id_of('form') );
            $es->add_right_relationship( $this->cur_page->id(), relationship_id_of('page_to_form') );
            $es->set_num(1);
            $result = $es->run_one();
            if ($result)
            {
                $this->_form = reset($result);
            }
        }
        return $this->_form;
    }

    function run()
    {
        $form = $this->get_form();

        $xml = $form->get_value('thor_content');
        $table_name = 'form_' . $form->id();
        $thor_core = new ThorCore($xml, $table_name);
        $columns = $thor_core->get_column_names_indexed_by_label();

        $first_name_col = $columns['Current First Name'];
        $last_name_col  = $columns['Current Last Name'];
        $grad_name_col  = $columns['Graduation Name'];
        $class_year_col = $columns['Reunion Class Year'];
        $pref_fn_col    = $columns['Preferred First Name'];

        $thor_values    = $thor_core->get_rows();

        $pager_str = '<div id="attendees-pager" class="pager">'."\n";
        $pager_str .= '<form>'."\n";
        $pager_str .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/first.png" alt="First" class="first"></img>'."\n";
        $pager_str .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/prev.png" alt="Previous" class="prev"></img>'."\n";
        $pager_str .= '<select class="pagesize">'."\n";
        $pager_str .= '<option value="10">10 rows/page</option>'."\n";
        $pager_str .= '<option value="20">20 rows/page</option>'."\n";
        $pager_str .= '<option value="30">30 rows/page</option>'."\n";
        $pager_str .= '<option value="40">40 rows/page</option>'."\n";
        $pager_str .= '</select>'."\n";
        $pager_str .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/next.png" alt="Next" class="next"></img>'."\n";
        $pager_str .= '<img src="'.REASON_PACKAGE_HTTP_BASE_PATH.'mottie-tablesorter/addons/pager/icons/last.png" alt="Last" class="last"></img>'."\n";
        $pager_str .= '</form>'."\n";
        $pager_str .= '</div>'."\n";
        echo $pager_str;

        $str = '';
        $str .= '<table id="attendees" class="tablesorter" border="0" cellpadding="0" cellspacing="0">'."\n";
        $str .= '<thead>'."\n";
        $str .= '<tr>'."\n";
        $str .= '<th>First Name</th>'."\n";
        $str .= '<th>Last Name</th>'."\n";
        $str .= '<th>Graduation Name</th>'."\n";
        $str .= '<th data-placeholder="Select a decade">Class</th>'."\n";
        $str .= '</tr>'."\n";
        $str .= '</thead>'."\n";
        $str .= '<tbody>'."\n";

        foreach ( $thor_values as $v ) {
             if ($v[$first_name_col] && ($v[$pref_fn_col]))
             {
                 $str .= '<td>' .$v[$pref_fn_col]. '</td>'."\n";
             } else {
                 $str .= '<td>' .$v[$first_name_col]. '</td>'."\n";
             }

             $str .= '<td>' .$v[$last_name_col]. '</td>'."\n";
             $gn = strtolower($v[$grad_name_col]);
             if ($gn && ($gn != 'same'))
             {
                 $str .= '<td>' .$v[$grad_name_col]. '</td>'."\n";
             } else {
                 $str .= '<td>'. $v[$first_name_col] . ' ' . $v[$last_name_col] . '</td>'."\n";
             }

             $str .= '<td>' .$v[$class_year_col]. '</td>'."\n";
             $str .= '</tr>'."\n";
        }
        $str .= '</tbody>'."\n";
        $str .= '</table>'."\n";
        echo $str;
    }
}
