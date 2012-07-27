<?php
reason_include_once( 'minisite_templates/modules/default.php' );

$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'MobileEventCalModule';

class MobileEventCalModule extends DefaultMinisiteModule {
    function init( $args = array() ) {
        
    }
    function has_content() {
        return true;
    }
    function run() {
?>

<div id="eventCal">
<iframe scrolling="no" height="600px" frameborder="0" width="300px" style="border-width: 0pt;" src="https://www.google.com/calendar/hosted/luther.edu/embed?showTitle=0&amp;showTabs=0&amp;showPrint=0&amp;showCalendars=0&amp;mode=AGENDA&amp;height=600&amp;wkst=1&amp;bgcolor=%23FFFFFF&amp;src=luther.edu_7c6m91gqfh995svsqo7bqkvrf0%40group.calendar.google.com&amp;color=%232952A3&amp;ctz=America%2FChicago"></iframe>
</div>

<?php
    }
}
?>
