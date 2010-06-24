<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'CampusMapModule';

class CampusMapModule extends DefaultMinisiteModule {
    function init( $args = array() ) {
        
    }
    
    function has_content() {
        return true;
    }
    
    function run() {
        ?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Switch menu</title>
    <style type="text/css">
        #wrapper {
            text-align:left;
            margin:0 auto;
        }
        #wifi img{
            z-index: 1;
        }
        
        #link { color: blue; cursor:pointer; }
        
        #link:hover {
            color: black;
            background-color: #def ;
        }
        .containerdiv { float: left; position: relative; }
        
        .cornerimage { position: absolute; top: 0; left: 0; }
        
    </style>
    <script type="text/javascript">
        
        buildings = new Array();
        buildings.push('wifi','olin','koren','sampson');
        
        function hideAll() {
            var x;
            for (x in buildings) {
                var hide = document.getElementById(buildings[x]);
                hide.style.display = 'none';
            }
        }
        
        function buildingReset() {
            document.getElementById("buildings").selectedIndex = document.getElementById("buildings").getAttribute("default");
        }
        
        function departmentReset() {
            document.getElementById("departments").selectedIndex = document.getElementById("departments").getAttribute("default");
        }
        
        function switchMenu(obj) {
            hideAll();
            var o = document.getElementById(obj);
            o.style.display = '';
        }
        function show(obj) {
            var o = document.getElementById(obj);
            o.style.display = '';
        }
        function hide(obj) {
            var o = document.getElementById(obj);
            o.style.display = 'none';
        }
        
    </script>
</head>
        
<body onload="hideAll();buildingReset();departmentReset()">
        
    <div id="wrapper">
        <table>
                <tr>
                <th align="Left">Buildings:</th>
                <td>
                    <select id="buildings" name="buildings" default="0" onchange="switchMenu(this.options[this.selectedIndex].value); departmentReset()">
                        <option value="blank" selected></option>
                        <option value="olin">Olin</option>
                        <option value="koren">Koren</option>
                        <option value="sampson">Sampson Hoffland</option>
                    </select>
                </td>
                </tr>
                <tr>
                <th>Departments:</th>
                <td>
                    <select id="departments" name="departments" default="0" onchange="switchMenu(this.options[this.selectedIndex].value); buildingReset()">
                        <option value="blank" selected></option>
                        <option value="koren">Africana Studies</option>
                        <option value="koren">Anthropology</option>
                        <option value="koren">Archaeology</option>
                        <option value="sampson">Biology</option>
                        <option value="olin">Business</option>
                        <option value="sampson">Chemistry</option>
                        <option value="olin">Computer Science</option>
                        <option value="olin">Economics</option>
                        <option value="koren">Education</option>
                        <option value="olin">Math</option>
                        <option value="koren">Political Science</option>
                        <option value="koren">Sociology</option>
                        <option value="koren">Social Work</option>
                        <option value="koren">Women's and Gender Studies</option>
                    </select>
                </td>
    </tr>
        </table>
        <p></p>
        
        <div class="containerdiv">
            <!--blank map-->
            <img border="0" src="/images/luther2010/map/redone_luther_map.gif" width="100%" alt=""></img>
        
            <span style="font-size: 12px; padding:0; margin:0;">Luther College Wi-Fi Map <a id="link" onclick="show('wifi');">Show</a> <a id="link" onclick="hide('wifi');">Hide</a></span>
            <div id="wifi">
                <img class="cornerimage" border="0" src="/images/luther2010/map/wifi_map_over.gif" width="100%"></img>
                <p style="font-size: 12px; padding:0; margin:0;"><span style="color: green;">(Green) 802.11G</span> - Speeds up to 54Mbps</p>
                <p style="font-size: 12px; padding:0; margin:0;"><span style="color: purple;">(Purple) 802.11N</span> - Speeds up to 300Mbps</p>
                <p></p>
            </div>
            <div id="olin">
                <img class="cornerimage" border="0" src="/images/luther2010/map/olin_map_over.gif" width="100%"></img>
                <p></p>
                <!--<img src="http://www.luther.edu/system/imagetops/about/campus/tour/olin/Olin20090217104751.jpg?1234889393" width="100%"></img>-->
                <h2>Olin</h2>
                <p>Departments: Business, Computer Science, Economics, Math</p>
                <p>It is a 43,000 square-foot building which contains 33 faculty offices, four networked computer labs, 10 computer classrooms, a 137-seat auditorium/lecture hall, a large seminar room, a student study center, and conference/interview rooms.</p>
            </div>
            <div id="koren">
                <img class="cornerimage" border="0" src="/images/luther2010/map/koren_map_over.gif" width="100%"></img>
                <p></p>
                <!--<img src="http://www.luther.edu/system/imagetops/about/campus/tour/koren/Koren20090217110153.jpg?1234890113" width="100%"></img>-->
                <h2>Koren</h2>
                <p>Departments: Africana Studies, Anthropology, Archaeology, Education, Political Science, Sociology, Social Work, Women's and Gender Studies</p>
                <p>Its third-floor archaeology resource center is the largest archaeology lab in Iowa. Listed on the National Register of Historic Places, Koren was originally Luther's library.</p>
            </div>
            <div id="sampson">
                <img class="cornerimage" border="0" src="/images/luther2010/map/sampson_map_over.gif" width="100%"></img>
                <p></p>
                <!--<img src="http://www.luther.edu/system/imagetops/about/campus/tour/sampson/Sampson120090217105554.jpg?1234889754" width="100%"></img>-->
                <h2>Sampson Hoffland Laboratories</h2>
                <p>Departments: Biology, Chemistry</p>
                <p>Built to environmentally sound LEED standards, Sampson Hoffland is the college's newest building. A special emphasis was placed on creating space for student/faculty research.</p>
            </div>
            <p></p>
        </div>
    </div>
</body>
        <?php
    }
}
?>