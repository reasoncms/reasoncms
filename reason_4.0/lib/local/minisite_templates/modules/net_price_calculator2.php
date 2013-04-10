<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'netPriceCalculator2Module';

class netPriceCalculator2Module extends DefaultMinisiteModule {

    function run() {
        echo '<div id="npc_container"></div>
            <script type="text/javascript">

            var
            /* Update these three variables immediately */
            NPC_CLIENT_DOMAIN = "luther",
            NPC_CLIENT_HEIGHT = 1200,
            NPC_CLIENT_WIDTH  = 700, /* minimum: 740 */



            /* Do NOT edit the following code */
            NPC_CONTAINER_PROTOCOL  = "https:",
            NPC_EMBEDDED_PROTOCOL   = "https:",
            NPC_IGNITION_VERSION    = "1";

            (function(){var d=document,s=d.createElement("script");s.type="text/javascript";s.src=NPC_EMBEDDED_PROTOCOL+"//"+NPC_CLIENT_DOMAIN+".aidcalculator.com/scripts/embed/ignition_v"+NPC_IGNITION_VERSION+".js";d.getElementsByTagName("head")[0].appendChild(s);})();

            </script>
            <noscript><p>Please enable Javascript to use this tool.</p></noscript>';
    }

}
?>