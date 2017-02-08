# Using the Entity class

The entity class abstracts the process of getting information from Reason entities.

## Instantiation

To create an entity object, you need to have a Reason entity ID. IDs are integers that are unique across all Reason entities regardless of type. Basic usage:

```php
$entity = new entity($id);
echo $entity->get_value('name');
```

To find & fetch an entity object based on other parameters, use an [Entity Selector](entity_selector.md).

More sophisticated uses:

## Conditional display

This is particularly useful if you are dealing with a mixed set of entities, each with a different data structure

```php
if($entity->has_value('telephone_number'))
{
	echo $entity->get_value('telephone_number');
}
```

## Get all values

```php
foreach($entity->get_values() as $key => $val)
{
	echo '<strong>'.$key.'</strong>: '.$val.'</br />';
}
```

## Get a "prettied-up" HTML representation of the entity

Some types provide a "pretty" HTML representation, like a combined thumbnail and name for images.

```php
echo $entity->get_display_name();
```
