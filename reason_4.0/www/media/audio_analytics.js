(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-42728260-1', 'carleton.edu');

$(document).ready(function() {
	var played = false;
	var audio = $("audio");
	var id = audio.attr("id");
	var id_num = id.match(/[0-9]+/);
	var category = 'media_'+id_num;
	ga('send', 'event', category, 'view');
	audio.bind("play", function() {
		if (!played) {
			ga('send', 'event', category, 'play');
		}
		played = true;
	});
});