<?
reason_include_once('minisite_templates/modules/default.php');
$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'OpenIDModule';

class OpenIDModule extends DefaultMinisiteModule {

    var $sess;

    function run() {
        $url = get_current_url();
        $next_url = $_GET['next'];
        if ($next_url){
            $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php?next=' . $next_url;
        }else{
            $url = 'https://reasondev.luther.edu/reason/open_id/new_token.php';
        }
        
        //$parts = parse_url($url);
        //$url = $parts['scheme'] . '://' . $parts['host'] . '/login/?dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        //echo $url;
        
        echo '<iframe src="https://luthertest2.rpxnow.com/openid/embed?token_url=' . $url . '"
    scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>';

        $this->sess =& get_reason_session();
        if( $this->sess->exists( ) ) {
                if( !$this->sess->has_started() )
                        $this->sess->start();
                        //echo "<br />>>>>> STARTED SESSION <<<<<";
        }

//        echo '<br /> session name: ' . $this->sess->sess_name;
//        echo "<br /> session id: " . session_id();
//        echo '<br /> openid_id:' . $this->sess->get('openid_id');
//        echo '<br /> openid_provider:' . $this->sess->get('openid_provider');
//        echo '<br /> openid_name:' . $this->sess->get('openid_name');
    }
}
?>