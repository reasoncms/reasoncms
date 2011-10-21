<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'netPriceCalculatorModule';

class netPriceCalculatorModule extends DefaultMinisiteModule {

    function run() {

        echo '<a style="display:none" id="npclink" href="https://npc.collegeboard.org/student/app/luther?iframe=true" target="npcframe"></a>';
        echo '<iframe style="display:none" src = "https://npc.collegeboard.org/student/app/luther?iframe=true" id="npcframe" name="npcframe" width="695px" height="700px" scrolling="auto"></iframe>';
        echo '<script type="text/javascript" src="https://npc.collegeboard.org/student/static/js/iframe_display.js"></script>';
    }

}
?>