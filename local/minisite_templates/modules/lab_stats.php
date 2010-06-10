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
<p><b>Available</b> = Computer turned on, not logged in.<br /><b>Offline</b> = Computer turned off.<br /><b>In Use</b> = User logged in.</p>
Last Updated: Thursday, June 10, 2010, 10:50 am<table border="2" cellspacing="6" cellpadding="6">
  <tr>
    <td><b>Lab Name </b></td>

    <td><b>Available</b></td>

    <td><b>Offline</b></td>
    <td><b>In Use </b></td>
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

var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 30 ;

var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 30 ;

var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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

var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 39 ;

var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 39 ;

var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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

var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 44 ;

var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 44 ;

var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 44 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 31;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 31 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 31 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 31 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 31 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 26;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 26 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 26 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 26 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 26 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 20;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 20 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 20 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 20 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 20 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 37;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 37 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 37 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 37 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 37 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 28;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 28 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 28 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 28 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 28 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 27;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 27 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 27 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 27 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 27 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>

<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 11;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 11 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 11 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 11 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 11 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>

  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 10;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 10 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 10 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 10 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 10 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 21;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 21 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 21 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 21 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 21 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 22;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 22 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 22 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 22 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 22 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 19;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 19 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 19 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 19 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 19 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 12;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 12 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 12 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 12 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 12 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 13;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 13 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 13 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 13 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 13 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 14;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 14 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 14 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 14 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 14 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>
<tr>
<td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 15;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 15 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 15 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 15 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 15 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
  </tr>

<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 32;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 32 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 32 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 32 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 32 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_total';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
  </tr>
<tr>
<td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 38;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 38 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>

    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 38 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 38 ;

//substitute the string below with the lab property you wish to retrieve.
//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
//substitute the number below with the lab ID you want to use for the query
//if the lab ID you use is not valid, this function will not print anything to your page
var lab_id = 38 ;

//substitute the string below with the lab property you wish to retrieve.

//valid values are:
//   'lab_name'     -> returns the lab name
//   'lab_id'       -> returns the lab ID
//   'lab_inuse'    -> returns the number of computers In Use
//   'lab_available'-> returns the number of computers Available
//   'lab_offline'  -> returns the number of computers Offline
//   'lab_total'    -> returns the Total number of computers in the lab
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
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 16 ;
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 16 ;
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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
var lab_id = 34;
var lab_property = 'lab_name';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 34 ;
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 34 ;
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 34 ;
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 33 ;
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 33 ;
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 35 ;
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 35 ;
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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
var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 36 ;
var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>

</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">
var lab_id = 36 ;
var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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

var lab_property = 'lab_available';

var text = lookup(lab_id,lab_property)
document.write (text);
</SCRIPT>
	</td>
    <td>
	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 29 ;

var lab_property = 'lab_offline';

var text = lookup(lab_id,lab_property)
document.write (text);

</SCRIPT>
</td>
    <td>

	<SCRIPT TYPE="TEXT/JAVASCRIPT">

var lab_id = 29 ;

var lab_property = 'lab_inuse';

var text = lookup(lab_id,lab_property)
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