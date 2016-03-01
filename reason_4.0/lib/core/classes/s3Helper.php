<?php
require_once(SETTINGS_INC.'media_integration/s3_storage_settings.php');
require_once(INCLUDE_PATH . S3_API_INCLUDE_RELATIVE_PATH);
 
class S3Helper {
	private $s3;
	private $bucketName;
	private $tempDir;
	private $debugging;

	public function S3Helper($configKey = "default") {
		$this->debugging = false;
		$keyText = $configKey == "default" ? "" : strtoupper($configKey) . "_";

		$keyConstant = "S3_" . $keyText . "ACCESS_KEY_ID";
		$secretConstant = "S3_" . $keyText . "SECRET_ACCESS_KEY";

		$key = constant($keyConstant);
		$secret = constant($secretConstant);

		$this->s3 = new Aws\S3\S3Client([
			'version' => 'latest',
			'region'  => 'us-east-1',
			'credentials' => [
				'key' => $key,
				'secret' => $secret
			],
		]);

		$this->bucketName = S3_PLUPLOAD_MEDIA_UPLOAD_BUCKET_NAME;
		$this->tempDir = S3_PLUPLOAD_MEDIA_UPLOAD_TEMP_DIR;
	}

	public function debug($doDebug = true) {
		$this->debugging = true;
	}

	public function getTempDir() {
		return $this->tempDir;
	}

	private function printout($msg) {
		if ($this->debugging) {
			echo $msg . "<BR>";
		}
	}

	public function deleteByPrefix($prefix) {
		$this->printout("deleting [$prefix] prefix from bucket [" . $this->bucketName . "]...");
		$objects = $this->s3->getIterator('ListObjects', array("Bucket" => $this->bucketName, "Prefix" => $prefix));
		$numDeleted = 0;
		foreach ($objects as $obj) {
			$keyToDelete = $obj['Key'];
			$this->s3->deleteObject(Array("Bucket" => $this->bucketName, "Key" => $keyToDelete));
			$numDeleted++;
		}
		return $numDeleted;
	}

	public function getFileByPrefix($prefix) {
		$this->printout("fetching [$prefix] prefix from bucket [" . $this->bucketName . "]...");
		$amazon_file = null;

		$objects = $this->s3->getIterator('ListObjects', array("Bucket" => $this->bucketName, "Prefix" => $prefix));
		foreach ($objects as $obj) {
			$nameOfFile = $obj['Key'];

			$tmpPath = S3_BASE_URL . $this->bucketName . "/" . $nameOfFile;

			$amazon_file = array(
				"name" => $nameOfFile,
				"path" => $tmpPath,
				"tmp_name" => $tmpPath
				// "original_path" => $upload->get_original_path(),
				// "modified_path" => $upload->get_temporary_path(),
				// "size" => filesize($this->tmp_web_path),
				// "type" => $upload->get_mime_type("application/octet-stream")
			);

			break;
		}
		return $amazon_file;
	}
}
?>
