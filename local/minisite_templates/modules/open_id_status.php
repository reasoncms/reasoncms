<?
reason_include_once('minisite_templates/modules/default.php');
$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'OpenIDStatusModule';

class OpenIDStatusModule extends DefaultMinisiteModule {

    var $sess;

    function run() {
        $url = get_current_url();
        $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php';

        $this->sess =& get_reason_session();
        if( $this->sess->exists( ) ) {
                if( !$this->sess->has_started() )
                        $this->sess->start();
                        //echo "<br />>>>>> STARTED SESSION <<<<<";
        }

        echo '<br /> session name: ' . $this->sess->sess_name;
        echo "<br /> session id: " . session_id();
        echo '<br /> openid_id:' . $this->sess->get('openid_id');
        echo '<br /> openid_provider:' . $this->sess->get('openid_provider');
        echo '<br /> openid_name:' . $this->sess->get('openid_name');
    }
}
?>