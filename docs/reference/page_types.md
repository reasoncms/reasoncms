#Page Types

Each page in Reason is given a page type, which specifies which modules should run where on the page.

Core page types are defined in `reason_4.0/lib/core/minisite_templates/page_types.php`, but most of the time when we want to add a page type we should do it in locally.

Local page types are defined in `reason_4.0/lib/local/minisite_templates/page_types_local.php`. If you don't have a file in that location, create one from this template.

```php
<?php

$GLOBALS['_reason_page_types_local'] = array(
	
);
```

##Adding a new page type

To add a page type, add a key-value pair to the array in `page_types_local.php`.

Example:

```php
$GLOBALS['_reason_page_types_local'] = array(
	'my_page_type' => array(
	),
);
```

We now have a new page type named "my_page_type"; this string will be stored on the page entity when this page type is chosen for a page.

For now this page type does nothing beyond the default page type.

##Defining a page type

By default the page type inherits from the default page type. This means we only have to define what's different between the default page type and our new page type. (See `reason_4.0/lib/core/minisite_templates/page_types.php` for the default page type definition.)

To place a module on the page, we employ two strings: the page location and the module's filename.

```php
$GLOBALS['_reason_page_types_local'] = array(
	'my_page_type' => array(
		'main_post' => 'siblings',
	),
);
```

In the page type array, the keys are page locations ("main_post" in this example) and the values are module filenames with the ".php" stripped off ("siblings" in this example, which will pull in the `siblings.php` module).

Page locations in the default template are:
- pre_bluebar
- pre_banner
- banner_xtra
- post_banner
- main
- main_head
- main_post
- main_post_2
- main_post_3
- pre_sidebar
- sidebar
- post_sidebar
- navigation
- sub_nav
- sub_nav_2
- sub_nav_3
- footer
- post_foot
- edit_link

##Passing parameters to a module

Reason modules can be given parameters that alter their behavior. For example, if the siblings module is passed a true value for "provide_images" if will output the first image for each sibling page.

To do this, the string value used above for this page location is converted into an array. The module filename is given the key "module", and other parameters are key-value pairs on this array.

Example:


```php
$GLOBALS['_reason_page_types_local'] = array(
	'my_page_type' => array(
		'main_post' => array(
			'module' => 'siblings',
			'provide_images' => true,
		),
	),
);
