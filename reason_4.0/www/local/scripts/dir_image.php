<?php
    $image = $_GET['image'];
    header('Content-Type: image/jpg');
//    header('Content-length:'.filesize($image));
    readfile('/var/reason/person_photos/'.$image.'.jpg');
?>