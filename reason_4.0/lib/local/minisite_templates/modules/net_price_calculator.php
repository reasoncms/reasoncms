<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'netPriceCalculatorModule';

class netPriceCalculatorModule extends DefaultMinisiteModule {

    function run() {
        echo '<script type="text/javascript">
				var
				NPC_CLIENT_DOMAIN		= "luther",
				NPC_CLIENT_HEIGHT		= 1400,
				NPC_CLIENT_WIDTH		= 700,
				NPC_CONTAINER_PROTOCOL	= "https:",
				
				/*Do NOT edit the following code */
				NPC_EMBEDDED_PROTOCOL	= "https:",
				NPC_IGNITION_LOCATION	= "birch";
				(function() {var
				d=document,s=d.createElement("script");s.type="text/javascript";
				s.src=NPC_EMBEDDED_PROTOCOL+"//"+NPC_IGNITION_LOCATION+".aidcalculator.com/ignition/key.js";
				d.getElementsByTagName("head")[0].appendChild(s);})();</script><noscript>Please enable Javascript to use this tool.</noscript>
				<div id="npc_container"></div>';
    }

}
?>