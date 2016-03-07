<?php
	class ModuleGroupLayoutManager {
		public function runModules($modules) {
			echo "<div class='submodule_wrapper'>";
			foreach ($modules as $idx => $module) {
				echo "<div class='submodule' data-submodule-idx='$idx'>";
				$module->run();
				echo "</div>";
			}
			echo "</div>";
		}
	}
?>
