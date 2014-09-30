<?
include_once('reason_header.php');
reason_include_once('minisite_templates/modules/form/views/thor/default.php');
//include_once('disco/boxes/boxes.php');
$GLOBALS[ '_form_view_class_names' ][ basename( __FILE__, '.php') ] = 'ArtScholarshipForm';

/**
 * IndividualVisitForm adds visit request info to Thor form
 * that gets personal info
 *
 * @author Steve Smith
 */

class ArtScholarshipForm extends DefaultThorForm
{

    function on_every_time()
    {
        $portfolio = $this->get_element_name_from_label('Portfolio (pdf)');
        $this->change_element_type($portfolio, 'upload', array('acceptable_types' => 'application/pdf', 'acceptable_extensions' => 'pdf'));
    }
}
?>
