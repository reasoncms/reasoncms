<?php
require_once(SETTINGS_INC.'media_integration/s3_storage_settings.php');
require_once(INCLUDE_PATH . S3_API_INCLUDE_RELATIVE_PATH);
 
class S3Helper {
	private $s3;
	private $bucketName;
	private $tempDir;
	private $debugging;

	private static $cache;

	public function S3Helper($configKey = "default") {
		if (self::$cache == null) {
			self::$cache = Array();
		}

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

	public function getFileByPrefix($prefix, $useCacheIfPossible = true) {
		$cachedVal = $useCacheIfPossible ? $this->getCacheVal("fileByPrefix", $prefix) : null;

		if ($cachedVal == null) {
			$this->printout("fetching [$prefix] prefix from bucket [" . $this->bucketName . "]...");
			$amazon_file = null;

			$objects = $this->s3->getIterator('ListObjects', array("Bucket" => $this->bucketName, "Prefix" => $prefix));
			foreach ($objects as $obj) {
				$nameOfFile = $obj['Key'];

				// echo "<PRE>"; var_dump($obj); echo "</PRE>";

				$tmpPath = S3_BASE_URL . $this->bucketName . "/" . $nameOfFile;

				$amazon_file = array(
					"name" => $nameOfFile,
					"path" => $tmpPath,
					"tmp_name" => $tmpPath,
					// "original_path" => $upload->get_original_path(),
					// "modified_path" => $upload->get_temporary_path(),
					"size" => $obj["Size"]
					// "type" => $upload->get_mime_type("application/octet-stream")
				);

				break;
			}
			$this->setCacheVal("fileByPrefix", $prefix, $amazon_file);
			return $amazon_file;
		} else {
			return $cachedVal;
		}
	}

	public function getMetadataForKey($key, $useCacheIfPossible = true) {
		$cachedVal = $useCacheIfPossible ? $this->getCacheVal("metadata", $key) : null;

		if ($cachedVal == null) {
			$headers = $this->s3->headObject(array(
				"Bucket" => $this->bucketName,
				"Key" => $key
			));

			$rv = $headers->toArray()["Metadata"];
			$this->setCacheVal("metadata", $key, $rv);
			return $rv;
		} else {
			return $cachedVal;
		}
	}

	// simple request-based caching, as otherwise we'll hit the S3 API multiple times in a single request
	public function getCache($cacheType) {
		if (!isset(self::$cache[$cacheType])) {
			self::$cache[$cacheType] = Array();
		}
		return self::$cache[$cacheType];
	}

	public function setCacheVal($cacheType, $key, $val) {
		$c = $this->getCache($cacheType);
		$c[$key] = $val;
		self::$cache[$cacheType] = $c;
	}

	public function getCacheVal($cacheType, $key) {
		$c = $this->getCache($cacheType);
		if (isset($c[$key])) {
			return $c[$key];
		} else {
			return null;
		}
	}
}
?>
