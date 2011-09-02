    <?php

reason_include_once('minisite_templates/modules/default.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'admissionsApplicationExportModule';

class admissionsApplicationExportModule extends DefaultMinisiteModule {

    function  init($args = array()) {
        parent::init($args);

        // run the script that will create the export file for download
        include('admissions_application/admissions_application_export.php');
        connectDB(REASON_DB);
    }

    function run() {
        
        $base_path = '/var/reason_admissions_app_exports/application_exports/';
        if ($application_directory = opendir($base_path)) {
            while (false !== ($entryName = readdir($application_directory))) {
                if ($entryName != "." && $entryName != "..") {
                    $dirArray[] = $entryName;
                }
            }
            closedir($application_directory);

            $indexCount = count($dirArray);
            echo ("$indexCount files<br>\n");

            // sort 'em
            sort($dirArray);

            // echo 'em
            echo '<TABLE border="0" cellpadding="0" cellspacing="0" class=tablesorter>'. "\n";
            echo "<thead>" .
                "<TR>" .
                    "<TH>Filename</TH>" .
                    "<th>Records</th>" .
                    "<th>Filesize</th>" .
                "</TR></thead>\n";
            // loop through the array of files and echo them all
            for ($index = 0; $index < $indexCount; $index++) {
                if (substr("$dirArray[$index]", 0, 1) != ".") { // don't list hidden files
                    echo("<TR><TD><a href=/reason/scripts/admissions_retrieve_app_export_file.php?file_name=" . $dirArray[$index] . ">" . $dirArray[$index] . "</a></td>");
                    echo("<td>" .  (count(file($base_path . $dirArray[$index])) -1) . "</td>");
                    echo("<td>" . format_bytes_as_human_readable(filesize($base_path . $dirArray[$index])). "</td>");
                    echo("</TR>\n");
                }
            }
            echo("</TABLE>\n");
            echo '<p></p>';
            echo "\n";
        }
    }

}

?>
