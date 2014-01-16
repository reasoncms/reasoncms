$(document).ready(function(){
    var spinner = new Image();
    spinner.src = "/reason/ui_images/spinner_16.gif";
    spinner.alt = "loading";
    $('li.navItem').one('click', function(event) {        
        $(this).find("img").attr(spinner);
    });
});