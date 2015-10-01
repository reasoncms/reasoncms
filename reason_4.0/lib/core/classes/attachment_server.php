<?php
/**
 * A module can use this class if, instead of displaying content to the user, we just want to deliver a file. For instance from
 * an admin page you might want to just provide a link that downloads a zip or a csv or something.
 *
 * @package reason
 * @subpackage classes
 */
	class AttachmentServer {
		// saveableName is the name of the file the user will see.
		// filePath is the actual path to the zip on the filesystem that we want to deliver
		// this fxn written (well really relocated) on 2015-09-30 and is currently only tested with zip files but should
		// work with others
		public function serve_attachment($saveableName, $filePath, $contentType, $deleteFileAfterServing = false) {
			ob_clean();
			header('Content-Type: ' . $contentType);
			header("Content-Disposition: attachment; filename=\"" . utf8_encode($saveableName) . "\"");
			header('Content-Length: ' . filesize($filePath));
			// header("Location: " . $file_www_path); // this only works if the file is web accessible in the first place.
			readfile($filePath); // with this, we can serve a file from anywhere on the filesystem
			// ob_flush();

			if ($deleteFileAfterServing) {
				ignore_user_abort(true);
				if (connection_aborted()) {
					$this->unlink_if_exists($filePath);
				}
				$this->unlink_if_exists($filePath);
			}
		}

		private function unlink_if_exists($path) {
			if (file_exists($path)) { unlink($path); }
		}

		public function serve_zip($saveableName, $filePath, $deleteFileAfterServing = false) {
			$this->serve_attachment($saveableName, $filePath, "application/zip", $deleteFileAfterServing);
		}
	}
 
?>
