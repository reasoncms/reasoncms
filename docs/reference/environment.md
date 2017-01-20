#Making the Reason development environment available

To make the Reason development environment available in a new script, use this example:

`<?php
include_once('reason_header.php');`

Once you have this, a set of standard Reason library files are included, along with appropriate settings, etc.

Example:

`<?php
include_once('reason_header.php');

echo REASON_LOGIN_URL;`

Note that *not everything* in Reason is included, so you may still need to explicitly include files and libraries that your script depends on.

Including files inside `reason_4.0/lib|core/` is via `reason_include_once()`.

Example:

`<?php
include_once('reason_header.php');

reason_include_once('classes/entity.php');

$e - new entity(id_of('type'));
echo $e->get_value('name');`