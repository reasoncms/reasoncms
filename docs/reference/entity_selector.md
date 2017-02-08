# Using the Entity Selector class

Use entity selectors to fetch entities from the database.

This basic example fetches all image entities from a given site:

```php
$es = new entity_selector($site_id);
$es->add_type(id_of('image'));
$images = $es->run_one();
```

(more to come)
