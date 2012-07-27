<?php
    $image = $_GET['image'];
    header('Content-Type: image/jpg');
//    header('Content-length:'.filesize($image));
    readfile('/var/person_photos/'.$image.'.jpg');
?>