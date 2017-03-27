<?php
$entityId = isset($_REQUEST["id"]) ? $_REQUEST["id"] : -1;
$rv = Array();

if ($entityId == -1) {
	$rv["error"] = "missing required parameter";
} else {
	reason_include_once('classes/s3Helper.php');
	$s3h = new S3Helper("plupload_media_upload");
	$numDeleted = $s3h->deleteByPrefix(($s3h->getTempDir() == "" ? "" : $s3h->getTempDir() . "/") . $entityId . ".");
	$rv["num_items_deleted"] = $numDeleted;
}


echo json_encode($rv);
?>
