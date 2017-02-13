# Entity Delegates (Type-specific functionality on entities)

Entity delegates add type-specific functionality to the entity class.

For example, image entities support this interface for retrieving image URLs:

```php
$url = $image->get_image_url();
```

Or publications support retrieval of published issues:

```php
$issues = $publication->get_published_issues();
```

## What type-specific methods are available?

Generally speaking you can look at the delegate definitions in `reason_4.0/lib/core/entity_delegates/` to see what methods each delegate supports. However, since the delegates framework permits multiple delegates per type, and since they are locally configurable, you may wish to consult the entity delegates configuration files or use introspection, like:

```php
$delegates = $entity->get_delegates();
foreach($delegates as $delegate) {
    print_r($delegate->get_declared_methods());
}
```

## Entity delegate configuration

Reason supports run-time configuration of entity delegates via the [entityDelegatesConfig](../../reason_4.0/lib/core/classes/entity_delegates_config.php) class.

### Global configuration (preferred)

When a delegate should be applied to every entity of a given type, add it to `reason_4.0/lib/core/config/entity_delegates/config.php` or `reason_4.0/lib/local/config/entity_delegates/config_local.php`.

Example for `config_local.php`:

```php
$entity_delegates_config_setting_local = array(
	'my_type_unique_name' => array(
		'append' => array(
			'entity_delegates/my_type.php',
		),
	),
);
```

### Entity-by-entity configuration

When a delegate should be applied only in a specific context, it can be added to en entity manually, like so:

```php
reason_include_once('path/to/my/delegate.php');
$delegate = new myVerySpecialDelegate();
$entity->add_delegate('path/to/my/delegate.php', $delegate);
```

