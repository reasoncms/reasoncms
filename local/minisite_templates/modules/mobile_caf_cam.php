<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileCafCamModule';

class MobileCafCamModule extends DefaultMinisiteModule {
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
   document.images['cafcam'].src = 'http://webcam.luther.edu/cafcam/image.jpg?' + Math.random();
   setTimeout('refreshIt()',10000); // refresh every 5 secs
}
</script>
</head>

<body onLoad=" setTimeout('refreshIt()',10000)">

<img src="http://webcam.luther.edu/cafcam/image.jpg" name="cafcam">

</body>

</html>

<?php
    }
}
?>
