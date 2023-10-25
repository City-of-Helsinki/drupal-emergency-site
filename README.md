# City-of-Helsinki/drupal-emergency-site

This is the repository for the new drupal emergency website.

## Includes

- Drupal 9.x
- Drush 11.x
- Docker setup for development using [Stonehenge](https://github.com/druidfi/stonehenge)
- [druidfi/tools](https://github.com/druidfi/tools)
- Web root is `/public`
- Configuration is in `/conf/cmi`
- Custom modules are created in `/public/modules/custom`

## Documentation

See [documentation](/documentation).

## Changelog

See [CHANGELOG.md](/CHANGELOG.md)


## Get started

#### Requirements

- PHP and Composer
- [Docker and Stonehenge](https://github.com/druidfi/guidelines/blob/master/docs/local_dev_env.md)

Now you need to have Stonehenge up & running. See [Docker and Stonehenge](https://github.com/druidfi/guidelines/blob/master/docs/local_dev_env.md).


#### Short presentation about the project

Project is developed based on the Drupal Helfi platform base. It contains the following custom functionalities:

1. Helfi static trigger module - module that provides a way of triggering the httrack static files generations
It offers the possibility to trigger it manually via a form and also a cron is set to handle the generation.
2. Helfi emergency general - module that contains small functionalities - check module's readme.
3. A custom view is used for displaying the news items created with the existing helfi_node_news_item functionalities
Please check HDBT subtheme preprocesses.


### Next steps

You can run `make help` to list all available commands for you.
