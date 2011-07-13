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

            if ($_SESSION['openid_profile']['name']['formatted']){
                $welcome_name = $_SESSION['openid_profile']['name']['formatted'];
            }else{
                $welcome_name = $_SESSION['openid_profile']['displayName'];
            }
            
            if($_SESSION['openid_profile']['photo']){
                $openid_image = "<img src='" . $_SESSION['openid_profile']['photo'] . "' style='height:1em;display:inline;' />";
            }else{
                $openid_image = "";
            }
            
            //echo "XXXX " . $_SESSION['openid_profile']['providerName'] . " XXXX";
            
            if($_SESSION['openid_profile']['providerName']){
                $provider_image = "<img src='/reason/open_id/";
                switch($_SESSION['openid_profile']['providerName']){
                    case 'Twitter':
                        $provider_image .= "twitter.png";
                        break;
                    case 'Google':
                        $provider_image .= "google.png";
                        break;
                    case 'Yahoo!':
                        $provider_image .= "yahoo.png";
                        break;
                    case 'Aol':
                        $provider_image .= "aol.png";
                        break;
                    case 'Windows Live':
                        $provider_image .= "windows.png";
                        break;
                    case 'Facebook':
                        $provider_image .= "facebook.png";
                        break;
                    default:
                        $provider_image .= "openid.png";
                        break;
                }
                $provider_image .= "' style='height:1em;display:inline;' />";
            }else{
                $provider_image = "";
            }

            echo "Welcome, " . $provider_image . " " . $welcome_name . ".&nbsp;&nbsp;";
            echo "<span id='missing_info_tip'>Missing previously completed information?</span>";
            echo "<div class='tooltip_box'>";
            echo "You may have started your application using a different login.";
            echo "<br /><a href='" . $openid_url . "'>Try logging in using a different account</a>";
            echo "</div><br />&nbsp;";
        } else {
            //error?
        }

//        echo '<br /> session name: ' . $this->sess->sess_name;
//        echo "<br /> session id: " . session_id();
//        echo '<br /> openid_id:' . $this->sess->get('openid_id');
//        echo '<br /> openid_provider:' . $this->sess->get('openid_provider');
//        echo '<br /> openid_name:' . $this->sess->get('openid_name');
    }
}
?>