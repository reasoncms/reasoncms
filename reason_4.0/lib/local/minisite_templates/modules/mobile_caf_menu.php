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

        echo '<a href="http://www.luther.edu/mobile/menu/hours/">Hours of Operation</a><br></br>';

        // file example 1: read a text file into a string with fgets
        $filename="http://www.luther.edu/caf/WeeklyMenu.htm";
        $output1="";
        $file = fopen($filename, "r");
        while(!feof($file)) {

            //read file line by line into variable
            $output1 = $output1 . fgets($file, 4096);

        }
        fclose ($file);

        //$dontwant = array("&#0149;", "<td colspan=\"3\" bgcolor=\"#c0c0c0\" style=\"height:1px;\"></td>");
        //$removedoutput = str_replace($dontwant, "", $output1);

        $monday='<!-- MONDAY -->';
        $tuesday='<!-- TUESDAY -->';
        $wednesday='<!-- WEDNESDAY -->';
        $thursday='<!-- THURSDAY -->';
        $friday='<!-- FRIDAY -->';
        $saturday='<!-- SATURDAY -->';
        $sunday='<!-- SUNDAY -->';
        $end='<!-- END DAY DATA -->';
        $dateof='<!-- WEEK OF START -->';
        $enddateof='<!-- WEEK OF END -->';

        $dateoutput = str_replace('<span style="font-size:140%; font-weight:bold; color:#D08080;">Week of', '<!-- WEEK OF START --><span class=weekof>Week of', $output1);
        $dateoutput2 = str_replace('<a class="jumpto" href="#sunday">Sunday</a><br>', '<a class="jumpto" href="#sunday">Sunday</a><br><!-- WEEK OF END -->', $dateoutput);

        $monoutputx = str_replace("M<br>O<br>N<br>D<br>A<br>Y<br>", "<hr><div class=daytitles>Monday</div><br>", $dateoutput2);
        $monoutput = str_replace("M<br />O<br />N<br />D<br />A<br />Y<br />", "<hr><div class=daytitles>Monday</div><br>", $monoutputx);
        $tueoutputx = str_replace("T<br>U<br>E<br>S<br>D<br>A<br>Y<br>", "<br><hr><div class=daytitles>Tuesday</div>", $monoutput);
        $tueoutput = str_replace("T<br />U<br />E<br />S<br />D<br />A<br />Y<br />", "<br><hr><div class=daytitles>Tuesday</div>", $tueoutputx);
        $wedoutputx = str_replace("W<br>E<br>D<br>N<br>E<br>S<br>D<br>A<br>Y<br>", "<hr><div class=daytitles>Wednesday</div>", $tueoutput);
        $wedoutput = str_replace("W<br />E<br />D<br />N<br />E<br />S<br />D<br />A<br />Y<br />", "<hr><div class=daytitles>Wednesday</div>", $wedoutputx);
        $thuoutputx = str_replace("T<br>H<br>U<br>R<br>S<br>D<br>A<br>Y<br>", "<hr><div class=daytitles>Thursday</div>", $wedoutput);
        $thuoutput = str_replace("T<br />H<br />U<br />R<br />S<br />D<br />A<br />Y<br />", "<hr><div class=daytitles>Thursday</div>", $thuoutputx);
        $frioutputx = str_replace("F<br>R<br>I<br>D<br>A<br>Y<br>", "<hr><div class=daytitles>Friday</div>", $thuoutput);
        $frioutput = str_replace("F<br />R<br />I<br />D<br />A<br />Y<br />", "<hr><div class=daytitles>Friday</div>", $frioutputx);
        $satoutputx = str_replace("S<br>A<br>T<br>U<br>R<br>D<br>A<br>Y<br>", "<hr><div class=daytitles>Saturday</div>", $frioutput);
        $satoutput = str_replace("S<br />A<br />T<br />U<br />R<br />D<br />A<br />Y<br />", "<hr><div class=daytitles>Saturday</div>", $satoutputx);
        $sunoutputx = str_replace("S<br>U<br>N<br>D<br>A<br>Y<br>", "<hr><div class=daytitles>Sunday</div>", $satoutput);
        $sunoutput = str_replace("S<br />U<br />N<br />D<br />A<br />Y<br />", "<hr><div class=daytitles>Sunday</div>", $sunoutputx);

        $dontwant = array("&#0149;", "<td colspan=\"3\" bgcolor=\"#c0c0c0\" style=\"height:1px;\"></td>", "<br>","<br />", "Meal(s) to Display:&nbsp;&nbsp;");
        $output = str_replace($dontwant, "", $sunoutput);

        //$handle = fopen($file, "r");
        //$contents = fread($handle, filesize($file));
        //fclose($handle);

        //commented below out because I created a tester that prints it all in one
        //EVERYTING BETWEEN:
        // <!-- WEEK OF START --> and <!-- END DAY DATA -->
        /*
        $betweendateof=substr($output, strpos($output, $dateof), strpos($output, $enddateof) - strpos($output, $dateof));

        $betweenmon=substr($output, strpos($output, $monday), strpos($output, $tuesday) - strpos($output, $monday));
        $betweentues=substr($output, strpos($output, $tuesday), strpos($output, $wednesday) - strpos($output, $tuesday));
        $betweenwed=substr($output, strpos($output, $wednesday), strpos($output, $thursday) - strpos($output, $wednesday));
        $betweenthurs=substr($output, strpos($output, $thursday), strpos($output, $friday) - strpos($output, $thursday));
        $betweenfri=substr($output, strpos($output, $friday), strpos($output, $saturday) - strpos($output, $friday));
        $betweensat=substr($output, strpos($output, $saturday), strpos($output, $sunday) - strpos($output, $saturday));
        $betweensun=substr($output, strpos($output, $sunday), strpos($output, $end) - strpos($output, $sunday));
        */
        $tester=substr($output, strpos($output, $dateof), strpos($output, $end) - strpos($output, $dateof));

        echo $tester;
        //echo $betweendateof;
        //echo $betweenmon;
        //echo $betweentues;
        //echo $betweenwed;
        //echo $betweenthurs;
        //echo $betweenfri;
        //echo $betweensat;
        //echo $betweensun;

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
