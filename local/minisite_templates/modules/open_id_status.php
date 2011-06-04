<?
reason_include_once('minisite_templates/modules/default.php');
$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'OpenIDStatusModule';

class OpenIDStatusModule extends DefaultMinisiteModule {

    var $sess;

    function run() {
        $url = get_current_url();
        $openid_url = "http://reasondev.luther.edu/openid/?next=" . $url;

        $this->sess =& get_reason_session();
        if( $this->sess->exists( ) ) {
                if( !$this->sess->has_started() )
                        $this->sess->start();
                        //echo "<br />>>>>> STARTED SESSION <<<<<";
        }

        if ($this->sess->get('openid_id')){
            echo "Welcome, " . $this->sess->get('openid_name') . ".";
            echo "&nbsp;&nbsp;If you think you started already, click ";
            echo "<a href='" . $openid_url . "'>HERE</a>";
            echo " to choose a different account.<br />&nbsp;";
        } else {

        }

//        echo '<br /> session name: ' . $this->sess->sess_name;
//        echo "<br /> session id: " . session_id();
//        echo '<br /> openid_id:' . $this->sess->get('openid_id');
//        echo '<br /> openid_provider:' . $this->sess->get('openid_provider');
//        echo '<br /> openid_name:' . $this->sess->get('openid_name');
    }
}
?>