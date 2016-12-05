$(document).ready(function () {

	function promptUserForOverwrite() {
		if ($('textarea[name=content_manual]').val().length > 0) {
			return window.confirm("Do want to overwrite your manual changes with the uploaded file?")
		}
		return true;
	}

	function textIsValidWebVTT(text) {
		return /^WEBVTT/.test(text);
	}

	function handleTextLoaded(event) {
		var fileContent = event.target.result;

		if (textIsValidWebVTT(fileContent)) {
			var okToContinue = promptUserForOverwrite();
			if (!okToContinue) {
				console.log('bailed');
				return;
			}
			$("input[name=content]").val(fileContent);
			// Hide the manual editor, reset its value
			// since the official store is input[name=content]
			// unless the manual editor has text
			$('#contentmanualRow, #contentmanualItem').css("visibility", "hidden");
			$('textarea[name=content_manual]').val("");
		} else {
			alert("invalid file selected")
		}
	}

	function hookupFileChangeHandler() {
		$("input[type=file]").on("change", function (e) {
			// https://developer.mozilla.org/en-US/docs/Web/API/FileReader
			var reader = new FileReader();
			reader.onload = handleTextLoaded;

			var fileFromUser = $(this).get(0).files[0];
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
						"This is the first line of text, displaying from 1-10 seconds\n";
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

