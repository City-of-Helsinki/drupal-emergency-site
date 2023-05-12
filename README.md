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


#### Setup

There is a database dump containing the first page and the secondary page. Installation steps:
1. Clone this repo and run `make new`.
2. Import the database dump: `drush sqlc` and run `source ./database-backup.sql`

### Next steps

You can run `make help` to list all available commands for you.
