# Disco Form Manager

Disco is used throughout Reason to manage and render forms, and to collect submitted data.

This documentation will not go into great detail regarding how Disco works. See Disco's [in-code documentation](../disco/disco.php) for details.

In addition to simple standalone uses of Disco in various modules, it is also the basis for several forms systems in Reason:

## Thor forms (e.g. forms created using the wysiwyg formbuilder tool)

Disco is the library used to render and process Thor forms. When a Thor "view" is specified in the administrative interface it is a pointer to a file containing a Disco form.

## Content managers (e.g. standard entity editing interface in the Reason admin)

Each content manager is a Disco form extending ContentManager. ContentManager (and its parent ReasonDisco2) extend Disco to provide functionality specific to editing Reason entities. Loading and saving Reason entity values, guessing at appropriate plasmature types based on the database structure, and other convenience functionality is baked in to all content managers.

## Administrative list filtering

## Custom forms using the Forms MVC framework
