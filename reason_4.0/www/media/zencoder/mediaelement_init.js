$(document).ready(function () {
	if (typeof mediaelementparams !== "object") {
		console.error("Missing mediaelement.js parameters. Can't play video.");
		return;
	}
	// mediaelementparams is a json object written out the server
	$("video").mediaelementplayer(mediaelementparams);

	var is_chrome = navigator.userAgent.indexOf("Chrome") > -1;
	var is_safari = navigator.userAgent.indexOf("Safari") > -1;
	var is_mobile = navigator.userAgent.match(/(iPad)|(iPhone)|(iPod)|(android)|(webOS)/i);
	if ((is_chrome && is_safari) || is_mobile) {
		is_safari = false;
	}
	// Safari 6+ enables a default caption user preference of "Auto (Recommended)" 
	// which automatically plays caption files, but not subtitles, in the
	// native video player. Our mediaelement.js player cannot disable or control
	// those native captions. The JS below disables native captions in Safari
	// when a video is played.
	// 
	// There is a bug on webkit for this behavior:
	// https://bugs.webkit.org/show_bug.cgi?id=147951
	// 
	// The same option exists on iOS, but we default to the native player on mobile
	// so it's an acceptable behavior in the mobile environment.
	if (is_safari) {
		$("video").on("playing", function (event) {
			var videoInstance = event.target;
			$.each(videoInstance.textTracks, function (i, track) {
				// Wrap setting in callback so to queue the change
				// after captions are enabled
				setTimeout(function () {
					track.mode = "hidden";
				}, 200);
			});
		});
	}
});