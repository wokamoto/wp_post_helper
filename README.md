# megumi/wp-post-helper

Helper class of the `wp_insert_post()`

## Installation

Create a composer.json in your project root.

```
{
    "require": {
        "megumi/wp-post-helper": "*"
    }
}
```

## Documentation

### Basic usage

```
$args = array(
    'post_name'    => 'slug',                  // slug
    'post_author'  => '1',                     // author's ID
    'post_date'    => '2012-11-15 20:00:00',   // post date and time
    'post_type'    => 'posts',                 // post type (you can use custom post type)
    'post_status'  => 'publish',               // post status, publish, draft and so on
    'post_title'   => 'title',                 // post title
    'post_content' => 'content',               // post content
    'post_category'=> array( 1, 4 ),           // category IDs in an array
    'post_tags'    => array( 'tag1', 'tag2' ), // post tags in an array
);

$helper = new Helper( $args );
$post_id = $helper->insert();
```

### Attachements

```
$args = array(
    ...
);

$helper = new Helper( $args );
$post_id = $helper->insert();

$attachment_id = $helper->add_media(
    'http://placehold.jp/100x100.png', // path or url
    'title',
    'description',
    'caption',
    false
);
```

### Adding a value to a custom field

```
$args = array(
    ...
);

$helper = new Helper( $args );
$post_id = $helper->insert();

$post->add_meta(
   'meta_key',  // meta key
   'meta_val',  // meta value
   true         // add it as unique (true) or not (false)
);
```

### Aadding a value as a format of Advanced Custom Field

```
$args = array(
    ...
);

$helper = new Helper( $args );
$post_id = $helper->insert();

$post->add_field(
   'field_xxxxxxxxxxxxx',   // key
   'field_val'          // value
);
```

## Contributing

### Run testing

```
$ composer install
$ vendor/bin/phpunit
```
