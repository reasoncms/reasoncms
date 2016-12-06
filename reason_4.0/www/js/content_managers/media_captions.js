$(document).ready(function () {

	var statusNode = $("<div class='file-status-block'></div>");
	$("form *[id^=upload] .file_upload").append(statusNode);
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

	function promptUserForOverwrite() {
		if ($('textarea[name=content_manual]').val().length > 0) {
			return window.confirm("Do want to overwrite your manual changes with the uploaded file?")
		}
		return true;
	}

	function textIsValidWebVTT(text) {
		return /^WEBVTT/.test(text);
	}

	function handleTextLoaded(event, fileNode) {
		var reader = event.target,
				fileContent = reader.result;

		if (textIsValidWebVTT(fileContent)) {
			var okToContinue = promptUserForOverwrite();
			if (!okToContinue) {
				return;
			}
			$("input[name=content]").val(fileContent);
			// Hide the manual editor, reset its value
			// since the official store is input[name=content]
			// unless the manual editor has text
			$('#contentmanualRow, #contentmanualItem').css("visibility", "hidden");
			$('textarea[name=content_manual]').val("");

			statusNode.clearStatus().addClass("status-complete").html("Upload Complete!");
		} else {
			statusNode.clearStatus().addClass("status-invalid").html("Invalid File Type: text isn't WEBVTT, please try again");
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
				handleTextLoaded(event, fileNode);
			};
			reader.onloadstart = function (event) {
				$("form input[type=submit]").prop("disabled", true);
				// Make sure file is less than 100M
				console.log(event, event.total >= 100000000);
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
				statusNode.clearStatus().addClass("status-invalid").html("Upload or Type error, please try again");
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
			if ($('#contentmanualRow, #contentmanualItem').css("visibility") == "visible") {
				return;
			}

			$('#contentmanualRow, #contentmanualItem').css("visibility", "visible");

			// If we have 'content' already, copy it to the manual editor
			var text = $('input[name=content][type=hidden]').val();
			if (text.length > 0) {
				$('textarea[name=content_manual]').val("Loading...");
			} else {
				// If no 'content' value, spawn some boilerplate WEBVTT text
				text = "WEBVTT" + "\n\n" + "1\n" +
						"00:00:01.000 --> 00:00:10.000\n" +
						"This is the first line of demo text, displaying from 1-10 seconds\n" +
						"This is the second line\n";
			}

			// Since the text value may be large and slow to load,
			// wrap this setter to force some webkit-y browsers to display
			// Loading... since large strings may be slow to render 
			// and look like a freeze
			setTimeout(function () {
				$('textarea[name=content_manual]').val(text);
			}, 1);
		});
	}

	hookupManualEditToggle();
	hookupFileChangeHandler();
});

