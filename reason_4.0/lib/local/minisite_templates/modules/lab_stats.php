<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'LabStatsModule';

class LabStatsModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
?>


<script type="text/javascript" src="http://labstats.luther.edu/public/data2.jsp">
</script>

<SCRIPT>
    Stamp = new Date();
    year = Stamp.getYear();
    if (year < 2000) year = 1900 + year;
    var Hours;
    var Mins;
    var Time;
    var timeFormat;
    Hours = Stamp.getHours();
    if (Hours >= 12) {
        Time = "pm";
    }
    else {
        Time = "am";
    }
    if (Hours > 12) {
        Hours -= 12;
    }
    if (Hours == 0) {
        Hours = 12;
    }
    Mins = Stamp.getMinutes();
    if (Mins < 10) {
        Mins = "0" + Mins;
    }
    timeFormat = Hours+':'+Mins+Time
    document.write('Last Updated on '+(Stamp.getMonth() + 1) +"/"+Stamp.getDate()+ "/"+ year+' at '+timeFormat.bold());

</SCRIPT>
<p></p><i>(Refresh to Update)</i>
<!--<p><b>In Use</b> = User logged in.</p>-->

<table border="1" cellspacing="3" cellpadding="3">
    <tr>
        <td><b>Lab Name </b></td>
        <td><b>Available </b></td>
        <td><b>Total</b></td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 30;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 30 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 30 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 39;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 39 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 39 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 44;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 44 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 44 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 31;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 31 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 31 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 26;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 26 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 26 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 20;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 20 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 20 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 37;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 37 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 37 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 28;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 28 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 28 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 11;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 11 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 11 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 10;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 10 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 10 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 21;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 21 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 21 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 22;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 22 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 22 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 19;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 19 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 19 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 12;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 12 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 12 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 13;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 13 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 13 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 14;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 14 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 14 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 15;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 15 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 15 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 32;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 32 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 32 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 38;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 38 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 38 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 16;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 16 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 16 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 49;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 49 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 49 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 34;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 34 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 34 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 33;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 33 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 33 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 35;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 35 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 35 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 36;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 36 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 36 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>
    <tr>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 29;
                var lab_property = 'lab_name';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 29 ;
                var lab_property = 'lab_inuse';
                var t1 = lookup(lab_id,lab_property);
                var lab_property = 'lab_total';
                var t2 = lookup(lab_id,lab_property);
                var text = t2 - t1;
                document.write (text);
            </SCRIPT>
        </td>
        <td>
            <SCRIPT TYPE="TEXT/JAVASCRIPT">
                var lab_id = 29 ;
                var lab_property = 'lab_total';
                var text = lookup(lab_id,lab_property)
                document.write (text);
            </SCRIPT>
        </td>
    </tr>

</table>
<?php
    }
}
?>