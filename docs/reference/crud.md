#Creating, Updating, and Deleting Entities

Database write functionality is wrapped up in a set of global functions in `function_libraries/admin_actions.php`.

##Creating a new entity

Use the function `reason_create_entity()`.

##Updating an existing entity

Use the function `reason_update_entity()` to update values on an entity.

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
