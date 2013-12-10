// media_api_flv.js
// This defines a javascript api to interact with the flv video object.
// requires jQuery

var player;

var endedHandlers = [];
var playHandlers = [];
var pausedHandlers = [];

function playerReady(object)
{
	player = document.getElementById($('div.Wrapper object').attr('id'));
	player.addModelListener("STATE", "stateListener");
}

function stateListener(obj){
	if (obj.newstate == "PLAYING") {
  		for (var i = 0; i < playHandlers.length; i += 1) {
  			playHandlers[i]();
  		}
	} else if (obj.newstate == "PAUSED") {
		for (var i = 0; i < pausedHandlers.length; i += 1) {
  			pausedHandlers[i]();
  		}
  	} else if (obj.newstate == "IDLE") {
  		for (var i = 0; i < endedHandlers.length; i += 1) {
  			endedHandlers[i]();
  		}
	}
}

$(document).ready(function() {
	
	if (typeof(parent.iframeLoaded) != "undefined") { 
	
		function play() {
		}
	
		function pause() {
		}
		
		function mute() {
			if (!muted) {
				// volumeBeforeMute = video.volume;
				// video.volume = 0;
				muted = true;
			}
		}
		
		function unmute() {
			if (muted) {
				muted = false;
			}
		}
		
		function start() {
		}
		
		function end() {
		}
		
		// sourceArray is a jQuery array
		function setSources(sourceArray) {
			
		}
		
		function getSources() {
			// return jQueryVideo.children();
		}
		
		function getPosterImage() {
			// return jQueryVideo.attr('poster');
		}
		
		function setPosterImage(posterSource) {
			// jQueryVideo.attr('poster', posterSource);
		}
		
		function getMediaDOMElement() {
			return player;
		}
		
		function volumeChange() {
			if (true) { // FIIIIIIXIXXIXIXIXIXIXI
				muted = true;
			} else {
				// volumeBeforeMute = video.volume;
				muted = false;
			}
		}
		// video.onvolumechange = volumeChange;
		
		// Provide a function that fires when the video has ended.
		function addEndedHandler(handler) {
			endedHandlers.push(handler);
		}
		
		function addPlayHandler(handler) {
			playHandlers.push(handler);
		}
		
		function addPauseHandler(handler) {
			pausedHandlers.push(handler);
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