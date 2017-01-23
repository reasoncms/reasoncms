#Creating, Updating, and Deleting Entities

Reason database write functionality is wrapped up in a set of global functions in `function_libraries/admin_actions.php`. To use any of these functions, be sure to include this file, e.g.:

```php
reason_include_once('function_libraries/admin_actions.php');
```

##Creating a new entity

Use the function `reason_create_entity()`.

Example:

```php
$entity_id = reason_create_entity( $site_id, $type_id, $user_id, 'My trip to the grand canyon', array('content'=>'<p>It started with along drive.</p>'));
```

##Updating an existing entity

Use the function `reason_update_entity()`.

Example:

```php
$success = reason_update_entity( $entity_id, $user_id, array('name'=>'My trip to the Grand Canyon','content'=>'<p>It started with along drive.</p>'));
```

By default Reason stores an archive entity on each call to `reason_update_entity()`. These entities are available for history and rollback in the administrative interface. If you *don't* want to save an archive entity, pass `false` as the third argument:

```php
$success = reason_update_entity( $entity_id, $user_id, array('name'=>'My trip to the Grand Canyon','content'=>'<p>It started with a long drive.</p>'), false);
```

##Deleting an entity

There are two approaches to deleting an entity.

###Mark as deleted

To "soft delete" an entity, simply change its `state` value to `Deleted` using `reason_update_entity()`.

Example:

```php
$success = reason_update_entity( $entity_id, $user_id, array('state'=>'Deleted'));
```

The entity will remain in the database but will no longer be selected using standard entity selectors. it will appear in a list of deleted entities in its site until Reason's garbage collection runs or it is manually expunged.

###Explunge from the database

To fully remove an entity from the database, use `reason_expunge_entity()`.

Example:

```php
$success = reason_expunge_entity( $entity_id, $user_id );
```
