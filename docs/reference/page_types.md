#Page Types

Each page in Reason is given a page type, which specifies which modules should appear on the page.

Core page types are defined in `reason_4.0/lib/core/minisite_templates/page_types.php`, but most of the time when we want to add a page type we should do it locally.

Local page types are defined in `reason_4.0/lib/local/minisite_templates/page_types_local.php`. If you don't have a file in that location, create one from this template:

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

Every page type inherits from the default page type – we only define how our new page type differs from the default. (See `reason_4.0/lib/core/minisite_templates/page_types.php` for the default page type definition.)

To include a module, we add a key-value pair to the page type: the page location and the module's filename. To remove a module defined in the default page type, we use an empty string as the value.

```php
$GLOBALS['_reason_page_types_local'] = array(
	'my_page_type' => array(
		'main_post' => 'siblings',
		'sidebar' => '',
	),
);
```

In the page type array, the keys are page locations ("main_post" in this example) and the values are modules ("siblings" in this example).

##Page locations

Page locations in the default template are:

`pre_bluebar`, `pre_banner`, `banner_xtra`, `post_banner`, `main`, `main_head`, `main_post`, `main_post_2`, `main_post_3`, `pre_sidebar`, `sidebar`, `post_sidebar`, `navigation`, `sub_nav`, `sub_nav_2`, `sub_nav_3`, `footer`, `post_foot`, `edit_link`

Where exactly these page locations appear in a layout depends on the site's template and theme, and for responsive layouts on the screen size. Feel free to play around with putting modules in different page locations and seeeing what happens.

##Modules

Modules are in `reason_4.0/lib/core/minisite_templates/modules/` and `reason_4.0/lib/local/minisite_templates/modules/`. (See [Core vs. Local Files](core_local.md) for how the core and local directories work.)

Modules are included in a page type as the module's file name minus its ".php" extension. For example, "siblings" refers includes the module file at `reason_4.0/lib/core/minisite_templates/modules/siblings.php`.

Modules and their supporting code can be organized into directories within `modules`. A module located at `reason_4.0/lib/local/minisite_templates/modules/random_number/random_number.php` can be included in a page type with the module string "random_number/random_number".

##Passing parameters to a module

Reason modules can be given parameters that alter their behavior. For example, the siblings module allows parameters that enable & size images.

To provide parameters to a module, replace the module value above with an array. Use the key "module" in this array to specify the module, and provide parameters as additional key-value pairs.

Example:

```php
$GLOBALS['_reason_page_types_local'] = array(
	'my_page_type' => array(
		'main_post' => array(
			'module' => 'siblings',
			'provide_images' => true,
			'image_width" => 100,
			'image_height' => 100,
		),
	),
);
```

###What parameters are available?

You need to look at the module's code to know what parameters it supports. Most modules define a class variable `$acceptable_params` near the top of the file, which, if it is well-documented, will often tell you everything you need to know about the module's parameters.

Some modules generate their acceptable parameters list dynamically, which makes things a little more difficult. Look for the `handle_params()` method; often dynamic parameter handling is done there.
