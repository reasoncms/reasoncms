<?php
reason_include_once( 'minisite_templates/modules/default.php' );
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileMapHomeModule';

class MobileMapHomeModule extends DefaultMinisiteModule {
    function init( $args = array() ) {

    }

    function has_content() {
        return true;
    }

    function run() {
        ?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
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

        function buildingReset() {
            document.getElementById("buildings").selectedIndex = document.getElementById("buildings").getAttribute("default");
        }

        function departmentReset() {
            document.getElementById("departments").selectedIndex = document.getElementById("departments").getAttribute("default");
        }

        function officeReset() {
            document.getElementById("offices").selectedIndex = document.getElementById("offices").getAttribute("default");
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

        function WinOpen1() {
            var url=document.redirect1.buildings.value
            document.location.href=url
        }

        function WinOpen2() {
            var url=document.redirect2.departments.value
            document.location.href=url
        }

        function WinOpen3() {
            var url=document.redirect3.offices.value
            document.location.href=url
        }

    </script>
</head>

<body onload="officeReset();buildingReset();departmentReset()">
<p><i>Select a building or a department to display on map.</i></p>
<th><b>Buildings:</b> <i>(Main, Olin, etc.)</i></th>
<form name="redirect1">
    <select id="buildings" name="buildings" onchange="officeReset(); departmentReset()">
        <option value=" " selected></option>
        <option value="ashmore/">Ashmore-Jewell Barn</option>
        <option value="baker/">Baker Village</option>
        <option value="brandt/">Brandt Hall</option>
        <option value="bergen/">Bergen</option>
        <option value="campushouse/">Campus House</option>
        <option value="carlson/">Carlson Stadium</option>
        <option value="cfa/">Center for the Arts</option>
        <option value="cfl/">Center for Faith and Life</option>
        <option value="apts/">College Apartments</option>
        <option value="union/">Dahl Centennial Union</option>
        <option value="dieseth/">Dieseth Hall</option>
        <option value="facilities/">Facilities Services</option>
        <option value="farwell/">Farwell Hall</option>
        <option value="gjerset/">Gjerset House</option>
        <option value="jefferson/">Jefferson Prairie House</option>
        <option value="jenson/">Jenson-Noble Hall of Music</option>
        <option value="koren/">Koren</option>
        <option value="korsrud/">Korsrud Heating Plant</option>
        <option value="larsen/">Larsen Hall</option>
        <option value="lillehammer/">Lillehammer</option>
        <option value="loyalty/">Loyalty Hall</option>
        <option value="main/">Main Building</option>
        <option value="miller/">Miller Hall</option>
        <option value="norby/">Norby House</option>
        <option value="ockham/">Ockham House</option>
        <option value="olin/">Franklin W. Olin Building</option>
        <option value="olson/">Olson Hall</option>
        <option value="oslo/">Oslo</option>
        <option value="president/">President's Residence</option>
        <option value="preus/">Preus Library</option>
        <option value="regents/">Regents Center</option>
        <option value="rock/">Rock Prairie House</option>
        <option value="sampson/">Sampson Hoffland</option>
        <option value="shirley/">Shirley Baker Commons</option>
        <option value="sperati/">Sperati Guest House</option>
        <option value="spring/">Spring Prairie House</option>
        <option value="storre/">Storre</option>
        <option value="sustainability/">Sustainability House</option>
        <option value="trondheim/">Trondheim</option>
        <option value="valders/">Valders Hall of Science</option>
        <option value="ylvisaker/">Ylvisaker Hall</option>
    </select>
    <input type=button value="Go" onClick="WinOpen1();">
</form>
<p></p>
<th><b>Departments:</b> <i>(Biology, Music, etc.)</i></th>
<form name="redirect2">
    <select id="departments" name="departments" onchange="buildingReset(); officeReset()">
        <option value=" " selected></option>
        <option value="koren/">Africana Studies</option>
        <option value="cfa/">Art</option>
        <option value="koren/">Anthropology</option>
        <!--<option value="koren/">Archaeology</option>-->
        <option value="sampson/">Biology</option>
        <option value="sampson/">Chemistry</option>
        <option value="main/">Classics</option>
        <option value="campushouse/">Communication Studies</option>
        <option value="olin/">Computer Science</option>
        <option value="olin/">Economics and Business</option>
        <option value="koren/">Education</option>
        <option value="main/">English</option>
        <option value="valders/">Environmental Studies</option>
        <option value="regents/">Health and Physical Education</option>
        <option value="koren/">History</option>
        <option value="larsen/">International Studies</option>
        <option value="preus/">Library Information Studies</option>
        <option value="olin/">Mathematics</option>
        <option value="main/">Modern Languages/Literatures</option>
        <option value="koren/">Museum Studies</option>
        <option value="jenson/">Music</option>
        <option value="valders/">Nursing</option>
        <option value="main/">Paideia</option>
        <option value="ockham/">Philosophy</option>
        <option value="valders/">Physics</option>
        <option value="koren/">Political Science</option>
        <option value="valders/">Psychology</option>
        <option value="main/">Religion</option>
        <option value="koren/">Sociology</option>
        <option value="koren/">Social Work</option>
        <option value="cfa/">Theatre/Dance</option>
        <option value="koren/">Women's and Gender Studies</option>
    </select>
    <input type=button value="Go" onClick="WinOpen2();">
</form>
<p></p>
<th><b>Offices and Other Areas:</b> <i>(Admissions, Book Shop, etc.)</i></th>
<form name="redirect3">
    <select id="offices" name="offices" onchange="buildingReset(); departmentReset()">
        <option value=" " selected></option>
        <option value="union/">Administrative Services</option>
        <option value="union/">Admissions</option>
        <option value="loyalty/">Alumni Office</option>
        <option value="preus/">Archives</option>
        <option value="union/">Assessment and Institutional Research</option>
        <option value="regents/">Athletics Office</option>
        <option value="union/">Book Shop</option>
        <option value="cfl/">Box Office</option>
        <option value="union/">Cafeteria</option>
        <option value="cfl/">Campus Programming</option>
        <option value="union/">Career Center</option>
        <option value="union/">Catering</option>
        <option value="campushouse/">Celebration Iowa</option>
        <option value="union/">Chips Student Newspaper</option>
        <option value="cfl/">College Ministries</option>
        <option value="union/">Communications and Marketing</option>
        <option value="larsen/">Counseling Service</option>
        <option value="facilities/">Custodial Office</option>
        <option value="union/">Deans Office</option>
        <option value="loyalty/">Developement Office</option>
        <option value="union/">Dining Services</option>
        <option value="union/">Diversity Center</option>
        <option value="main/">Document Center</option>
        <option value="facilities/">Facilities Services</option>
        <option value="main/">Financial Aid</option>
        <option value="main/">Financial Services</option>
        <option value="larsen/">Health Services</option>
        <option value="main/">Human Resources</option>
        <option value="union/">Information Desk</option>
        <option value="larsen/">International Educations</option>
        <option value="union/">International Student Coordinate</option>
        <option value="union/">KWLC Radio</option>
        <option value="preus/">Library and Information Services</option>
        <option value="union/">Mail Center/Student Post Office (SPO)</option>
        <option value="union/">Marty's</option>
        <option value="jenson/">Music Organizations and Marketing</option>
        <option value="union/">Oneota Market</option>
        <option value="union/">Pioneer Yearbook</option>
        <option value="union/">President's Office</option>
        <option value="union/">Public Information</option>
        <option value="union/">Publications</option>
        <option value="union/">Recreational Services</option>
        <option value="main/">Registrar's Office</option>
        <option value="union/">Residence Life</option>
        <option value="union/">Safety and Security</option>
        <option value="main/">Sense of Vocation Office</option>
        <option value="regents/">Sports Information</option>
        <option value="preus/">Student Academic Support Center</option>
        <option value="union/">Student Activities</option>
        <option value="main/">Student Employment</option>
        <option value="union/">Student Life</option>
        <option value="preus/">Student Support Services</option>
        <option value="larsen/">Study Abroad Office</option>
        <option value="valders/">Sustainability</option>
        <option value="preus/">Technology Help Desk</option>
        <option value="gjerset/">Upward Bound</option>
        <option value="union/">Wellness</option>
    </select>
    <input type=button value="Go" onClick="WinOpen3();">
</form>

</body>
        <?php
    }
}
?>