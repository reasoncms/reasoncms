<?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'netPriceCalculatorModule';

class netPriceCalculatorModule extends DefaultMinisiteModule {

    function run() {
        echo '<div id="npc_container"></div> 
			<script type="text/javascript"> 

			var 
			/* Update these three variables immediately */ 
			NPC_CLIENT_DOMAIN = "luther", 
			NPC_CLIENT_HEIGHT = 1200, 
			NPC_CLIENT_WIDTH = 700, /* minimum: 740 */ 



			/* Do NOT edit the following code */ 
			NPC_CONTAINER_PROTOCOL = "https:", 
			NPC_EMBEDDED_PROTOCOL = "https:", 
			NPC_IGNITION_VERSION = "1"; 

			(function(){var d​=​d​o​c​u​m​e​n​t​,​s​=​d​.​c​r​e​a​t​e​E​l​e​m​e​n​t​(​&​q​u​o​t​;​s​c​r​i​p​t​&​q​u​o​t​;​)​;​s​.​t​y​p​e​=​&​q​u​o​t​;​t​e​x​t​/​j​a​v​a​s​c​r​i​p​t​&​q​u​o​t​;​;​s​.​s​r​c​=​N​P​C​_​E​M​B​E​D​D​E​D​_​P​R​O​T​O​C​O​L​+​&​q​u​o​t​;​/​/​&​q​u​o​t​;​+​N​P​C​_​C​L​I​E​N​T​_​D​O​M​A​I​N​+​&​q​u​o​t​;​.​a​i​d​c​a​l​c​u​l​a​t​o​r​.​c​o​m​/​s​c​r​i​p​t​s​/​e​m​b​e​d​/​i​g​n​i​t​i​o​n​_​v​&​q​u​o​t​;​+NPC_IGNITION_VERSION+".js";d.getElementsByTagName("head")[0].appendChild(s);})(); 

			</script> 
			<noscript><p>Please enable Javascript to use this tool.</p></noscript>';
    }

}
?>