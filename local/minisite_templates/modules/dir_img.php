<?php
//if (isset($_SESSION['logged_in'])) {
  header("Content-type: image/jpg");
  readfile($photo_dir.$_GET['user'].".jpg");
//}
?>
