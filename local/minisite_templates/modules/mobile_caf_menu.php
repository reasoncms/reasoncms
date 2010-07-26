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
        /**

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
        // Example string
        
         * 
         * 
         */
        //First, open the file. Change your filename
        $file = "https://reasondev.luther.edu/images/luther2010/mobile/WeeklyMenu_old.htm";
        $word1='<';
        $word2='>';


        $handle = fopen($file, "r");
        $contents = fread($handle, filesize($file));
        fclose($handle);

        $between=substr($contents, strpos($contents, $word1), strpos($contents, $word2) - strpos($contents, $word1));

        echo $between;

        /**

        $do = preg_match("/<!-- MONDAY -->(.*)<!-- TUESDAY -->/", $output, $matches);

        // Check if regex was successful
        if ($do = true) {
            // Matched something, show the matched string
            echo htmlentities($matches['0']);

            // Also how the text in between the tags
            echo '<br />' . $matches['1'];
        } else {
            // No Match
            echo "Couldn't find a match";
        }
        //echo strip_tags($output);
        echo "<p><b>Done</b></p>";

        // Allow <p> and <a>
        //echo strip_tags($text, '<p><a>');

         *
         */

        ?>

        <?php
    }
}
?>
