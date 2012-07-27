<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CafCamModule';

class CafCamModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
?>

<html>

<head>
<script language="JavaScript">
function refreshIt() {
   if (!document.images) return;
   document.images['cafcam'].src = 'http://webcam.luther.edu/cafcam/image-hq.jpg?' + Math.random();
   setTimeout('refreshIt()',2000); // refresh every 2 secs
}
</script>
</head>

<body onLoad=" setTimeout('refreshIt()',2000)">
<img style="width:100%;" src="http://webcam.luther.edu/cafcam/image-hq.jpg" name="cafcam">
<p></p>
<p><i>(image refreshes automatically)</i></p>

</body>

</html>

<?php
    }
}
?>
