/**
 * The workflow:
 * Using a input[type=file], users select a file to be read with a FileReader
 * The text content of the selected file is stored as the value
 * of the input[type=hidden][name=content] field. This node is hidden 
 * for dom performance issues, browsers aren't super fast at working with
 * multi-megabyte strings (like a 2hr caption file).
 * As of late 2016, Firefox was ok, but Chrome was questionable handling 
 * a huge textarea to the point that making the input hidden was a good idea.
 * 
 * If users click the toggle to show the textarea for manual edits, the textarea is
 * a separate element (name=content_manual) which is shown to users and populated
 * with the current caption text from the database or this session's file.
 * 
 * Since caption text is stored in the database, we never upload any selected files
 * to the server since that's duplicating work. The code below massages the UI
 * for working with the file upload & manual text edits.  
 */

$(document).ready(function () {

	function promptUserForOverwrite() {
			if ($('textarea[name=content_manual]').val().length > 0) {
			return window.confirm("Do want to overwrite your manual changes with the selected file?")
		}
		return true;
	}

	function textIsValidWebVTT(text) {
		return /^WEBVTT/.test(text);
	}

	function handleFileLoaded(event, fileNode) {
		var reader = event.target,
				fileContent = reader.result;

		if (textIsValidWebVTT(fileContent)) {
			// To ensure users don't lose changes
			var okToContinue = promptUserForOverwrite();
			if (!okToContinue) {
				return;
			}
			$("input[name=content]").val(fileContent);
			// Hide the manual editor and reset its value
			// since the official store is input[name=content],
			// unless the manual editor has text
			$('#contentmanualItem').css("display", "none");
			$('textarea[name=content_manual]').val("");

			statusNode.clearStatus().addClass("status-complete").html("Complete!");
		} else {
			statusNode.clearStatus().addClass("status-invalid").html("Invalid File Type: text isn't VTT, please try again");
			fileNode.value = "";
		}
	}

	function hookupFileChangeHandler() {
		$("input[type=file]").on("change", function () {
			var fileFromUser = $(this).get(0).files[0];
			var fileNode = this;

			// https://developer.mozilla.org/en-US/docs/Web/API/FileReader
			var reader = new FileReader();
			reader.onload = function (event) {
				handleFileLoaded(event, fileNode);
			};
			reader.onloadstart = function (event) {
				$("form input[type=submit]").prop("disabled", true);
				// Make sure file is less than 100M
				if (event.total >= 100000000) {
					reader.abort();
					statusNode.clearStatus().addClass("status-invalid").html("File is too large, please try again");
					fileNode.value = "";
				}
			};
			reader.onloadend = function (event) {
				$("form input[type=submit]").prop("disabled", false);
			};
			reader.onerror = function (event) {
				statusNode.clearStatus().addClass("status-invalid").html("Error, please try again");
				fileNode.value = "";
			};
			reader.onprogress = function (event) {
				statusNode.html("Loading captions from file...");
			};
			reader.readAsText(fileFromUser);
		});
	}

	function hookupManualEditToggle() {
		$("a.toggle-manual-content").on("click", function (event) {

			// Don't overwrite changes if users click more than 1x
			if ($('#contentmanualItem').css("display") == "block") {
				return;
			}

			$('#contentmanualItem').css("display", "block");

			// If we have content already, copy it to the manual editor
			// for manual edits
			var text = $('input[name=content][type=hidden]').val();
			if (text.length > 0) {
				$('textarea[name=content_manual]').val("Loading...");
			} else {
				// If content is empty, spawn some boilerplate WEBVTT text
				text = "WEBVTT" + "\n\n" + "1\n" +
						"00:00:01.000 --> 00:00:10.000\n" +
						"This is the first line of demo text, displaying from 1-10 seconds\n" +
						"This is the second line\n";
			}

			// Since the text value may be large and slow to load,
			// wrap this setter to force some browsers to display
			// Loading... for a split second first, since large strings
			// may be slow to render  and look like a freeze
			setTimeout(function () {
				$('textarea[name=content_manual]').val(text);
			}, 1);
		});
	}


	// Create & attach a status message container which is updated
	// at various points of the FileReader data processing
	var statusNode = $("<div class='file-status-block'></div>");
	$("form *[id^=upload] .file_upload").append(statusNode);
	// Add a helper method to the jquery object to remove "status-*" classes
	$.fn.extend({
		clearStatus: function () {
			return $(this).removeClass(function (i, str) {
				var toRemove = "";
				$.each(str.split(" "), function (j, singleclass) {
					if (/^status-/.test(singleclass)) {
						toRemove += " " + singleclass;
					}
				});
				return toRemove;
			});
		}
	});
	if (!window.FileReader) {
		var sorry = "Your browser can't select caption files. Click the add/edit link instead.";
		statusNode.clearStatus().addClass("status-invalid").html(sorry);
	}
	hookupManualEditToggle();
	hookupFileChangeHandler();
});

