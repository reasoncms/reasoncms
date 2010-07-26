<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileCafMenuModule';

class MobileCafMenuModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {


        // file example 1: read a text file into a string with fgets
        $filename="https://reasondev.luther.edu/images/luther2010/mobile/WeeklyMenu_old.htm";
        $output="";
        $file = fopen($filename, "r");
        while(!feof($file)) {

            //read file line by line into variable
            $output = $output . fgets($file, 4096);

        }
        fclose ($file);
        //echo $output;
        // striping text
        //$text = '<p>Test paragraph.</p><!-- Comment --> <a href="#fragment">Other text</a>';
        echo strip_tags($output);
        echo "<p></p>";

        // Allow <p> and <a>
        //echo strip_tags($text, '<p><a>');


        ?>

        <?php
    }
}
?>
