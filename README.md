Typecho Blogging Platform
=========================

Typecho is a PHP-based blog software and is designed to be the most powerful blog engine in the world.
Typecho is released under the GNU General Public License 2.0.

## Main Features

* Multiple databases support (MariaDB, MySQL, SQLite, PostgreSQL)
* Markdown Support
* Plugin Support
* Theme Support
* Custom Fields
* Custom Pages

## Requirements

* PHP 8.3.0 or higher
* Database (MariaDB, MySQL, SQLite, PostgreSQL)
  * MariaDB or MySQL 5.5.3 or higher
  * SQLite 3.7.11 or higher
  * PostgreSQL 9.1 or higher
* Node.js 24.0.0 or higher (for building admin assets)

## Optional Cache

Typecho ships with a small cache interface. Persistent cache is disabled by default.
To cache global options between requests, add the following constants to `config.inc.php`:

```php
define('__TYPECHO_CACHE__', 'file');
define('__TYPECHO_CACHE_DIR__', __TYPECHO_ROOT_DIR__ . '/usr/cache');
define('__TYPECHO_CACHE_TTL__', 3600);
```

## Screenshots

![Typecho](https://typecho.org/usr/themes/bluecode/img/screenshot/st1.png)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.
