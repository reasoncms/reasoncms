# Using the Entity class

The entity class abstracts the process of getting information from Reason entities.

## Instantiation

To create an entity object, you need to have a Reason entity ID. IDs are integers that are unique across all Reason entities regardless of type. Basic usage:

```php
$entity = new entity(12345);
echo $entity->get_value('name');
```

## Finding/Fetching

To find & fetch an entity object based on other parameters, use an [Entity Selector](entity_selector.md).

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

## Get related entities

You can use the entity class to fetch other entities related to the current one.

```php
echo '<h3>Pages with this image:</h3>';
foreach($e->get_right_relationship( 'page_to_image' ) as $page)
{
	echo $page->get_display_name().'<br />';
}
echo '<h3>This image\'s categories:</h3>';
foreach($e->get_left_relationship( 'image_to_category' ) as $category)
{
	echo $category->get_display_name().'<br />';
}
```

To fetch presorted/filtered related entities, use the [Entity Selector class](entity_selector.md)

## Set a value for later use

The entity can be a key-value bundle with the `set_value()` method. Set a computed value on an entity using `set_value()` and this will be accessible by other code that interacts with the same object.

```php
$image = new entity(12345);
$image->set_value('url', reason_get_image_url($image));
$image->set_value('alt', reason_htmlspecialchars(strip_tags($image->get_value('description'));

render_image($image);

function render_image($image)
{
	if($image->has_value('url') && $image->has_value('alt'))
		echo '<img src="'.$image->get_value('url').'"  alt="'.$image->get_value('alt').'" />';
	else
		echo 'Unable to render an image without a URL or alt text';
}
```
