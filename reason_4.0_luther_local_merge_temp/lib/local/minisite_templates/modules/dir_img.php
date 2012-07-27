<?php
echo "This is a test of dir_img.php";
$image = $_GET['image'];
header('Content-Type: image/jpeg');
readfile('/var/person_photos/'.$image.'.jpg');


//if (isset($_SESSION['logged_in'])) {
  //header("Content-type: image/jpg");
  //readfile("/var/person_photos/".$_GET['user'].".jpg");
  //readfile("/var/person_photos/burkaa01.jpg");
//}
?>