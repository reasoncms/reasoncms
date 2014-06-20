// media_api.js
// This defines a javascript api to interact with the html5 media element.
// requires jQuery

$(document).ready(function() {
	if (typeof(parent.iframeLoaded) != "undefined") { 
		// private vars
		var jQueryElement = $('video');
		if (!jQueryElement.length) {
			jQueryElement = $('audio');
		}
		var element = jQueryElement.get(0);
		var volumeBeforeMute = element.volume;
		var muted = false;
	
		function play() {
			element.play();
		}
	
		function pause() {
			element.pause();
		}
		
		function mute() {
			if (!muted) {
				volumeBeforeMute = element.volume;
				element.volume = 0;
				muted = true;
			}
		}
		
		function unmute() {
			if (muted) {
				element.volume = volumeBeforeMute;
				muted = false;
			}
		}
		
		function start() {
			element.currentTime = 0;
		}
		
		function end() {
			element.currentTime = element.duration;
		}
		
		// sourceArray is a jQuery array
		function setSources(sourceArray) {
			jQueryElement.children().each(function() {
				$(this).detach();
			});
			sourceArray.each(function() {
				jQueryElement.append($(this));
			});
			element.load();
		}
		
		function getSources() {
			return jQueryElement.children();
		}
		
		function getPosterImage() {
			return jQueryElement.attr('poster');
		}
		
		function setPosterImage(posterSource) {
			jQueryElement.attr('poster', posterSource);
		}
		
		function getMediaDOMElement() {
			return element;
		}
		
		function volumeChange() {
			if (element.volume == 0) {
				muted = true;
			} else {
				volumeBeforeMute = element.volume;
				muted = false;
			}
		}
		element.onvolumechange = volumeChange;
		
		// Provide a function that fires when the element has ended.
		function addEndedHandler(handler) {
			jQueryElement.bind('ended', function() {
				handler();
			});
		}
		
		function addPlayHandler(handler) {
			jQueryElement.bind('play', function() {
				handler();
			});
		}
		
		function addPauseHandler(handler) {
			jQueryElement.bind('pause', function() {
				handler();
			});
		}
	
		// define the media api
		$.fn.extend({
			play: play,
			pause: pause,
			mute: mute,
			unmute: unmute,
			start: start,
			end: end,
			getMediaDOMElement: getMediaDOMElement,
			getSources: getSources,
			setSources: setSources,
			getPosterImage: getPosterImage,
			setPosterImage: setPosterImage,
			addEndedHandler: addEndedHandler,
			addPlayHandler: addPlayHandler,
			addPauseHandler: addPauseHandler
		});
		
		parent.iframeLoaded($(this));
	}
});