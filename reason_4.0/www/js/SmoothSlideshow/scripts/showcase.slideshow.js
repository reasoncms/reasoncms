var currentIter = 0;
var lastIter = -1;
var maxIter = 0;
var slideShowElement = "";
var slideShowData = new Array();
var slideShowInit = 1;

function initSlideShow(element, data) {
	slideShowElement = element;
	slideShowData = data;
	element.style.display="block";
	maxIter = data.length;
	for(i=0;i<data.length;i++)
	{
		var currentImg = document.createElement('img');
		currentImg.setAttribute('id','slideElement' + parseInt(i));
		currentImg.style.position="absolute";
		currentImg.style.left="0px";
		currentImg.style.bottom="0px";
		currentImg.style.margin="0px";
		currentImg.src=data[i][0];
		
		element.appendChild(currentImg);
		currentImg.currentOpacity = new fx.Opacity(currentImg, {duration: 800});
		currentImg.currentOpacity.setOpacity(0);
	}
	
	var leftArrow = document.createElement('a');
	leftArrow.className = 'left';
	leftArrow.onclick = function () { pushPrevSlideShow(); };
	element.appendChild(leftArrow);
	
	var rightArrow = document.createElement('a');
	rightArrow.className = 'right';
	rightArrow.onclick = function () { pushNextSlideShow(); };
	element.appendChild(rightArrow);
	
	currentImg.currentOpacity = new fx.Opacity(currentImg, {duration: 800});
	currentImg.currentOpacity.setOpacity(0);
	
	var slideInfoZone = document.createElement('div');
	slideInfoZone.setAttribute('id','slideInfoZone');
	element.appendChild(slideInfoZone);
	
	//setTimeout(nextSlideShow,10);
	
	doSlideShow(1);
}

function destroySlideShow(element) {
	var myClassName = element.className;
	var newElement = document.createElement('div');
	newElement.className = myClassName;
	element.parentNode.replaceChild(newElement, element);
}

function pushNextSlideShow () {
	setTimeout(hideInfoSlideShow,10);
	setTimeout(nextSlideShow,500);
}

function pushPrevSlideShow () {
	setTimeout(hideInfoSlideShow,10);
	setTimeout(prevSlideShow,500);
}

function nextSlideShow() {
	lastIter = currentIter;
	currentIter++;
	if (currentIter >= maxIter)
	{
		currentIter = 0;
		lastIter = maxIter - 1;
	}
	slideShowInit = 0;
	doSlideShow(1);
}

function prevSlideShow() {
	lastIter = currentIter;
	currentIter--;
	if (currentIter <= -1)
	{
		currentIter = maxIter - 1;
		lastIter = 0;
	}
	slideShowInit = 0;	
	doSlideShow(2);
}

function doSlideShow(position) {
	//alert(currentIter);
	curImg = $Prototype('slideElement' + parseInt(currentIter));
	lastImg = $Prototype('slideElement' + parseInt(lastIter));
	if (position == 1)
	{
		if (lastIter >= 0)
		{
			lastImg.currentOpacity = new fx.Opacity(lastImg, {duration: 1000});
			lastImg.currentOpacity.custom(1,0);
		}
		curImg.currentOpacity.custom(0, 1);
	} else {
		/*if (currentIter != maxIter - 1) {
			curImg.currentOpacity.setOpacity(1);
			lastImg.currentOpacity.custom(1, 0);
		} else {
			curImg.currentOpacity.options.onComplete = function() {
				lastImg.currentOpacity.setOpacity(0);
			}
			curImg.currentOpacity.custom(0, 1);	
		} */
		lastImg.currentOpacity = new fx.Opacity(lastImg, {duration: 1000});
		lastImg.currentOpacity.custom(1,0);
		curImg.currentOpacity.custom(0, 1);
	}
	setTimeout(showInfoSlideShow,1000);
}

function showInfoSlideShow() {
	slideShowElement.removeChild($Prototype('slideInfoZone'));
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
	slideShowElement.appendChild(slideInfoZone);
	slideInfoZone.combo.o.custom(0, 0.7);
	//slideInfoZone.combo.h.custom(0, slideInfoZone.combo.h.el.offsetHeight);
}

function hideInfoSlideShow() {
	$Prototype('slideInfoZone').combo.o.custom(0.7, 0);
	//$('slideInfoZone').combo.h.custom(slideInfoZone.combo.h.el.offsetHeight, 0);
}
