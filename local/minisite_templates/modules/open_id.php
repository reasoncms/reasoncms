<?
reason_include_once('minisite_templates/modules/default.php');
$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'OpenIDModule';

class OpenIDModule extends DefaultMinisiteModule {
    function run() {
        $url = get_current_url();
        //$parts = parse_url($url);
        //$url = $parts['scheme'] . '://' . $parts['host'] . '/login/?dest_page=' . $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo $url;
        
        echo '<iframe src="http://luthertest2.rpxnow.com/openid/embed?token_url=' . $url . '"
    scrolling="no" frameBorder="no" allowtransparency="true" style="width:400px;height:240px"></iframe>';
    }
}
?>