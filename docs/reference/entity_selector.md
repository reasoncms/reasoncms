# Using the Entity Selector class

Use entity selectors to fetch entities from the database.

## Basic Usage

This basic example fetches all sites:

```php
$es = new entity_selector();
$es->add_type(id_of('site'));
$sites = $es->run_one();
```

The results of `run_one()` are always an associative array of `$entity_id => $entity`. So you can then iterate across them like:

```php
foreach($sites as $site_id => $site)
{
	echo '<a href="'.$site->get_value('base_url').'">'.$site->get_value('name').'</a><br />';
}
```

## Just entities belonging to a given site

This example fetches all images belonging to a given site:

```php
$es = new entity_selector($site_id);
$es->add_type(id_of('image'));
$images = $es->run_one();
```

Multiple sites can be included by passing an array of site ids to the constructor.

## Specifying owned vs. borrowed

By default, when a site or sites are specified, both owned and borrowed entities are fetched.

You can specify fetching just owned entities or just borrowed entities with `set_sharing()`:

```php
$es = new entity_selector($site_id);
$es->add_type(id_of('image'));
$es->set_sharing('owns'); // Only returns images actually owned by the site. use 'borrows' to fetch just images borrowed from other sites
$images = $es->run_one();
```

## Just entities with a given value

This example fetches images whose names end in "headshot":

```php
$es = new entity_selector($site_id);
$es->add_type(id_of('image'));
$es->add_relation('name LIKE "%headshot"');
$images = $es->run_one();
```

## Limiting by relationships

You can limit the entities fetched based on relationships. This is extremely common usage.

To fetch images associated with a given page:

```php
$es = new entity_selector();
$es->add_type(id_of('image'));
$es->add_left_relationship($page_id, relationship_id_of('page_to_image'));
$images = $es->run_one();
```

## Fetching related entity data in single query

You can also join across the relationship table to fetch data associated with related entities.

To get a set of images *and* the id of the pages each image appears on, in a given site:

```php
$es = new entity_selector($site_id);
$es->add_type(id_of('image'));
$es->add_left_relationship_field('page_to_image', 'entity', 'id', 'page_id' );
$es->enable_multivalue_results(); // Without this we would only get the first page for each image, and images not placed on a page would be excluded from the results
$images = $es->run_one();

foreach($images as $image)
	echo $image->get_display_name().' is on page ids: '.implode(', ',$image->get_value('page_id'));
```

## Sorting options

(coming soon)
