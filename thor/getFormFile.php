<?php
	// simple standalone script that retrieves files that were uploaded as part of a thor form. ensures that
	// accessing user has correct permissions. Either retrieves single files, or serves up (and then deletes) zip files
	// that carl_util/db/table_admin.php generated

	include_once('reason_header.php');
	include_once( 'thor/thor.php' );
	reason_include_once('function_libraries/user_functions.php');
	reason_include_once('classes/entity_selector.php');

	class FormFileDownloader {
		function getParam($n, $def = null) {
			if (isset($_REQUEST[$n])) {
				return $_REQUEST[$n];
			} else {
				return $def;
			}
		}

		function setup() {
			// var_dump("<PRE>", $_REQUEST, "</PRE>");
			$this->mode = $this->getParam("mode", "file");
			$this->zipfile = $this->getParam("zipfile");
			$this->table = $this->getParam("table");
			$this->row = $this->getParam("row", -1);
			$this->col = $this->getParam("col");
			$this->filename = $this->getParam("filename");
		}

		function missingParams() {
			if ($this->mode == "fetch_zip") {
				return $this->table == null || $this->zipfile == null;
			} else {
				return $this->table == null || $this->row == -1 || $this->col == null || $this->filename == null;
			}
		}

		function checkPermissions() {
			// first, make sure user is logged in
			$username = reason_check_authentication();

			if (!$username) {
				$this->error("This page requires authentication.");
			} else {
				// next, figure out the form id
				$matches = Array();
				$res = preg_match("/form_(\d*)/", $this->table, $matches);

				if (count($matches) != 2) {
					$this->error("invalid table name.");
				} else {
					$formId = $matches[1];

					// now that we've got the form id, find out what site it belongs to
					$form = new entity($formId);
					$site = $form->get_owner();

					// and finally, make sure the logged in user has access to the site, and is an admin
					$hasSiteAccess = reason_username_has_access_to_site($username, $site->id());
					// $isAdmin = user_is_a(get_user_id($username), id_of("admin_role"));
					// return $hasSiteAccess && $isAdmin;
					return $hasSiteAccess;
				}
			}

			return false;
		}

		function serveFile() {
			if ($this->missingParams()) {
				$this->error("Required parameters are missing.");
			} else if ($this->checkPermissions()) {
				$tc = new ThorCore("", $this->table);

				if ($this->mode == "fetch_zip") {
					$path = $this->zipfile;

					$matches = Array();
					$res = preg_match("/.*_(form_.*)/", $this->zipfile, $matches);

					$attachmentFilename = count($matches) == 2 ? $matches[1] : "thor_archive.zip";
				} else {
					$path = $tc->construct_file_storage_location($this->row, $this->col, $this->filename);
					$attachmentFilename = $this->filename;
				}

				if (!file_exists($path)) {
					$this->error("Unable to find this file.");
				} else {
					header('Content-disposition: attachment; filename="' . $attachmentFilename.'"');
					// header('Content-type: text/plain');
					readfile($path);

					if ($this->mode == "fetch_zip") {
						ignore_user_abort(true);
						if (connection_aborted()) {
							unlink($this->zipfile);
						}
						unlink($this->zipfile);
					}
				}
			} else {
				$this->error("You do not have permissions to access this file.");
			}
		}

		function error($s) {
			echo '<p>'.$s.'</p>';
		}
	}

	// If this file is being accessed directly, instantiate and run the downloader. 
	// Otherwise the file has been included, and we should let the includer decide what
	// to do.
	if (realpath(__FILE__) == realpath($_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME']))
	{
		$ffd = new FormFileDownloader();
		$ffd->setup();
		$ffd->serveFile();
	}
?>
