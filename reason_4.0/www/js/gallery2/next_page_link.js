

/*if (window.attachEvent)
  window.attachEvent('onLoad', checkNumItems);
else
  window.addEventListener('load', checkNumItems, false);
*/

$(document).ready(function()
{
	checkNumItems();
});

var numItems = 0;

function checkNumItems() {
  if (document.getElementById("imageGalleryNextPageItem"))
  {

    var ul = document.getElementById("imageGalleryItemList");
    if (ul)
    {
      var li = ul.getElementsByTagName("li");
      //alert(li.length);
      if (li[0])
      {
        numItems = li.length - 1;
        checkShowOrHide();
        if (window.attachEvent)
          window.attachEvent('onresize', checkShowOrHide);
        else
          window.addEventListener('resize', checkShowOrHide, false);
      }
    }
  }
}

function checkShowOrHide() {
  var ul = document.getElementById("imageGalleryItemList");
  if (ul)
  {
    var li = ul.getElementsByTagName("li");
    if (li[0])
    {
      var top = li[0].offsetTop;
      for(var i=1; i < li.length - 1; i++)
      {
        if (li[i].offsetTop != top)
          break;
      }
      if (numItems % i == 0)
      {
        document.getElementById("imageGalleryNextPageItem").style.display = 'none';
      }
      else
      {
        document.getElementById("imageGalleryNextPageItem").style.display = 'inline-block';
      }
    }
  }
}