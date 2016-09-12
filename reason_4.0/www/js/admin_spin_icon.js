$(document).ready(function(){
    var spinner = new Image();
    spinner.src = "[[REASON_HTTP_BASE_PATH]]ui_images/spinner_16.gif";
    spinner.alt = "loading";
    $('li.navItem').one('click', function(event) {        
        $(this).find("img").attr(spinner);
    });
});