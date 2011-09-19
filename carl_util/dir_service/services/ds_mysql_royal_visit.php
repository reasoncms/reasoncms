<?php

include_once( CARL_UTIL_INC . 'dir_service/services/ds_mysql.php' );

/**
 * MySQL Directory Service -- Interface for access to directory info in MySQL tables
 * @author Steve Smith
 */
class ds_mysql_royal_visit extends ds_mysql {

    /**
     * array Connection settings for this service.
     * @access private
     * @var array
     */
    var $_conn_params = array(
        'connectDBname' => 'royal_visit_connection',
    );
    /**
     * array Settings for the current search
     * @access private
     * @var array
     */
    var $_search_params = array(
        'table' => 'winners',
        'base_attrs' => array('first_name', 'last_name', 'email', 'guest_first_name', 'code'),
    );
    /**
     * array Dependencies for generic attributes
     * @access private
     * @var array
     */
    var $_gen_attr_depend = array(
        'ds_username' => array('email'),
        'ds_email' => array('email'),
        'ds_firstname' => array('first_name'),
        'ds_lastname' => array('last_name'),
        'ds_fullname' => array('first_name', 'last_name'),
    );
    /**
     * string Name of any database we were previously connected to
     * @access private
     * @var string
     */
    var $prev_connection_name;

    /**
     * Validate username and password
     * @access public
     * @param string $username Userid
     * @param string $password Password
     */
    function authenticate($email, $code) {
//        $this->_search_params['table'] = 'winners';
        $this->_search_params['table'] = 'faculty_staff_lottery';
        $this->set_search_param('filter', sprintf('SELECT `email` FROM %s WHERE `email`="%s" AND `code`="%s"', $this->_search_params['table'], $email, $code));
//        $this->set_search_param('filter', sprintf('SELECT `email` FROM %s WHERE `email`="%s" AND `code`="%s"', $this->_search_params['table'], $email, md5($code)));
        $results = $this->search();
        if (count($results) == 0) {
            $this->_search_params['table'] = 'general_public_lottery';
            $this->set_search_param('filter', sprintf('SELECT `email` FROM %s WHERE `email`="%s" AND `code`="%s"', $this->_search_params['table'], $email, $code));
//        $this->set_search_param('filter', sprintf('SELECT `email` FROM %s WHERE `email`="%s" AND `code`="%s"', $this->_search_params['table'], $email, md5($code)));
            $results = $this->search();
        }
        return count($results);
    }

}



?>
