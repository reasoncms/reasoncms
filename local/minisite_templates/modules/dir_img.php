<?php
//if (isset($_SESSION['logged_in'])) {
  header("Content-type: image/jpg");
  readfile("/var/person_photos/".$_GET['user'].".jpg");
//}
?>
