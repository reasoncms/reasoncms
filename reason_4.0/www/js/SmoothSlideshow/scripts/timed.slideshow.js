var currentIter = 0; // 0 represents 1st image
var lastIter = -1; // initialize to -1 since there is no previous iteration
var maxIter = 0;
var slideShowElement = "";
var slideShowData = new Array();
var slideShowInit = 1;
var slideShowDelay = 6000;
var articleLink = "";

function initSlideShow(element, data) {
	slideShowElement = element;
	slideShowData = data;
	element.style.display="block";
	
	articleLink = document.createElement('a');
	articleLink.className = 'global';
	element.appendChild(articleLink);
	articleLink.href = "";
	
	maxIter = data.length;
	for(i=0;i<data.length;i++)
	{
		var currentImg = document.createElement('img');
		currentImg.setAttribute('id','slideElement' + parseInt(i));
		currentImg.style.position="absolute";
		currentImg.style.left="0px";
		currentImg.style.top="0px";
		currentImg.style.margin="0px";
		currentImg.style.border="0px";
		currentImg.src=data[i][0];
	
		articleLink.appendChild(currentImg);
		currentImg.currentOpacity = new fx.Opacity(currentImg, {duration: 1000});
		currentImg.currentOpacity.setOpacity(0);
	}
	
	currentImg.currentOpacity = new fx.Opacity(currentImg, {duration: 1000});
	currentImg.currentOpacity.setOpacity(0);
	
	var slideInfoZone = document.createElement('div');
	slideInfoZone.setAttribute('id','slideInfoZone');
	slideInfoZone.combo = new fx.Combo(slideInfoZone);
	slideInfoZone.combo.o.setOpacity(0);
	articleLink.appendChild(slideInfoZone);
	
	setTimeout(doSlideShow,10);
}

function nextSlideShow() {
	lastIter = currentIter;
	currentIter++;
	if (currentIter >= maxIter)
	{
	    currentIter = 0;
		lastIter = maxIter - 1;
	}
	doSlideShow();
}

function doSlideShow() {
	if (lastIter > -1) lastImg = $Prototype('slideElement' + parseInt(lastIter));
	curImg = $Prototype('slideElement' + parseInt(currentIter));
	
	//curImg.currentOpacity.options.onComplete = function()     // Uncomment this wrapper for more delayed fade effect
	//{
		if (lastIter >= 0)
		{
			lastImg.currentOpacity = new fx.Opacity(lastImg, {duration: 1000});
			lastImg.currentOpacity.custom(1,0);
		}
	//}
	
	curImg.currentOpacity.custom(0, 1);
	
	setTimeout(showInfoSlideShow, 1000);
	setTimeout(hideInfoSlideShow,slideShowDelay-500);
	setTimeout(nextSlideShow,slideShowDelay);	
}

function showInfoSlideShow() {
	articleLink.removeChild($Prototype('slideInfoZone'));
	var slideInfoZone = document.createElement('div');
	slideInfoZone.setAttribute('id','slideInfoZone');
	slideInfoZone.combo = new fx.Combo(slideInfoZone);
	slideInfoZone.combo.o.setOpacity(0);
	var slideInfoZoneTitle = document.createElement('h2');
	slideInfoZoneTitle.innerHTML = slideShowData[currentIter][2]
	slideInfoZone.appendChild(slideInfoZoneTitle);
	var slideInfoZoneDescription = document.createElement('p');
	slideInfoZoneDescription.innerHTML = slideShowData[currentIter][3]
	slideInfoZone.appendChild(slideInfoZoneDescription);
	articleLink.appendChild(slideInfoZone);
	
	articleLink.href = slideShowData[currentIter][1];
	
	slideInfoZone.combo.o.custom(0, 0.7); // initial and eventual opacity level of caption box
	//slideInfoZone.combo.h.custom(0, slideInfoZone.combo.h.el.offsetHeight); // uncomment to turn slide back on
}

function hideInfoSlideShow() {
	$Prototype('slideInfoZone').combo.o.custom(0.7, 0);
	//$('slideInfoZone').combo.h.custom(slideInfoZone.combo.h.el.offsetHeight, 0);
}