<?php
/**
 * Plasmature
 *
 * \Plas"ma*ture\, n. Form; mold. [R.]
 *
 * A strong typing method for a weakly typed language and, really, an easier
 * way to deal with DB/PHP/HTML(forms) relations.
 *
 * IMPORTANT NOTE: As of July 2006, the plasmature classes have begun to be
 * modified for better consistency and ease of use (and to fix a few bugs), but
 * the overall system is in desperate need of a facelift. Until we have a
 * chance to clean up these classes, please make sure that you follow the
 * instructions in the documentation to create new classes instead of modeling
 * them off of existing classes.
 *
 * How to create a new plasmature type:
 *   - Extend one of the currently existing classes. Usually you'll want to
 *     extend the {@link defaultType}.
 *   - Name the class whateverType. You need the 'Type' to make it all work.
 *   - Set the {@link type} variable to the name of the new type.
 *   - Overload {@link type_valid_args} and add to it the names of any class
 *     variables that can be set outside of the class (i.e., variables that can
 *     be set through {@link init()} or {@link set_class_var()}.
 *   - Overload or extend functions as necessary. Do NOT overload {@link
 *     init()} if you are mortal; extend or overload the {@link
 *     additional_init_actions} hook instead.
 *   - Document the new type in a way that will be useful for future
 *     developers.
 *
 * @package disco
 * @subpackage plasmature
 *
 * @author Dave Hendler
 * @author Meg Gibbs
 *
 * @todo Remove any remaining references to $_REQUEST and replace them with
 *       {@link request}.
 * @todo Create a better error checking system. Ideally, this should involve
 *       error checks that apply to all plasmature types and error checks that
 *       are specific to particular types. Error checks should be run both for
 *       values set using {@link set_value()} and for values set using {@link
 *       grab()}.
 * @todo Standardize the way that {@link display_style} is used; it's supposed
 *       to determine the way that Disco displays the element, but right now
 *       that's mostly determined by other element properties (like {@link
 *       colspan}) or by the type.
 * @todo Make the {@link defaultType} an abstract class instead of a
 *       functioning type. Disco already uses the {@link textType} as the
 *       default, anyway.
 * @todo Create abstract classes for each "family" of element types. For
 *       example, option types currently extend from the {@link optionType()}
 *       abstract class; it would be nice to have an abstract class for date
 *       types, upload types, etc., as well.
 * @todo Rename abstract plasmature classes so that they don't include 'Type'
 *       in their name; that way, they CAN'T be used as types. Also, give them
 *       more descriptive names; 'default' could become 'plasmature', etc.
 * @todo Create a get_display_name() or get_label() method that returns the
 *       {@link display_name} if the element has one, and otherwise returns the
 *       prettified {@link name}, so that we can stop writing the code to do
 *       this all the time.
 * @todo Standardize whether or not display names are included in the markup in
 *       {@link get_display()}; right now they usually aren't, but they are
 *       sometimes. They probably never should be.
 * @todo Create a variable that determines where labels should be displayed (to
 *       the left, right, or above the element). Create corresponding
 *       functionality in the box class.
 */
/** path constants */
include_once 'paths.php';
/** tidy functions */
include_once CARL_UTIL_INC.'tidy/tidy.php';
/** trim_slashes(), prettify_string(), unhtmlentities() */
include_once CARL_UTIL_INC.'basic/misc.php';
/** date functions */
include_once CARL_UTIL_INC.'basic/date_funcs.php';

if (!defined("PLASMATURE_TYPES_INC")) {
	define("PLASMATURE_TYPES_INC", DISCO_INC."plasmature/types/");
}

require_once PLASMATURE_TYPES_INC."default.php";
require_once PLASMATURE_TYPES_INC."text.php";
require_once PLASMATURE_TYPES_INC."hidden.php";
require_once PLASMATURE_TYPES_INC."checkbox.php";
require_once PLASMATURE_TYPES_INC."presentational.php";
require_once PLASMATURE_TYPES_INC."editors.php";
require_once PLASMATURE_TYPES_INC."thor.php";
require_once PLASMATURE_TYPES_INC."formbuilder.php";
require_once PLASMATURE_TYPES_INC."options.php";
require_once PLASMATURE_TYPES_INC."world.php";
require_once PLASMATURE_TYPES_INC."datetime.php";
require_once PLASMATURE_TYPES_INC."upload.php";
require_once PLASMATURE_TYPES_INC."group.php";
require_once PLASMATURE_TYPES_INC."colorpicker.php";

