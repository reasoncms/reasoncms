#Creating, Updating, and Deleting Relationships between Entities

Reason database write functionality is wrapped up in a set of global functions in `function_libraries/admin_actions.php`. To use any of these functions, be sure to include this file, e.g.:

```php
reason_include_once('function_libraries/admin_actions.php');
```

All relationships must have a *relationship type*, which is a row in the `allowable_relationships` table that defines the kind of relationship.

All relationships have an "entity_a" and an "entity_b". Which entity goes where is part of that is defined in `allowable_relationships`.

##Creating a new relationship

Use the function `create_relationship()`.

Simple example:

```php
$relationship_id = create_relationship( $entity_a_id, $entity_b_id, $relationship_type_id);
```

If you want to set a relationship sort order, pass an optional additional parameter:

```php
$relationship_id = create_relationship( $entity_a_id, $entity_b_id, $relationship_type_id, array('rel_sort_order'=>7));
```

##Updating an existing relationship

Use the function `update_relationship()`.

Example:

```php
$updates = array(
	'entity_b' => $entity_b_id,
	'rel_sort_order' => 3,
);
$success = update_relationship($relationship_id, $updates);
```

Reason does not store history for relationships.

##Deleting relationships

###Deleting a single relationship at a time

Use `delete_relationship()`.

Example:

```php
$success = delete_relationship($relationship_id);
```

###Deleting Multiple Relationships at Once

Use `delete_relationships()`.

Example (delete all relationships of a given type from a given entity a):

```php
$conditions = array(
	'type' => $relationship_type_id,
	'entity_a' => $entity_a_id,
);
$success = delete_relationships( $conditions );
```
