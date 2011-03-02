<?php
require_once("./functions.php");

if (isset($_SESSION['logged_in'])) {
  if ($_SESSION['load_img']) {
    header("Content-type: image/jpg");
    readfile($photo_dir.$_GET['user'].".jpg");
    $_SESSION['load_img'] = 0;
  }
}
?>
