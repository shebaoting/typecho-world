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

## Plugin Events

The legacy plugin API is still supported:

```php
\Typecho\Plugin::factory('Widget\Archive')->beforeRender = [Plugin::class, 'beforeRender'];
```

New plugins can register runtime listeners through an event provider:

```php
use Typecho\Event\Dispatcher;
use Typecho\Event\Event;
use Typecho\Plugin as TypechoPlugin;
use Typecho\Plugin\EventProviderInterface;

class Plugin implements EventProviderInterface
{
    public static function register(Dispatcher $events)
    {
        $events->listen(
            TypechoPlugin::eventName('Widget\Archive', 'title'),
            function (Event $event) {
                return $event->getValue() . ' - Typecho';
            }
        );
    }
}
```

Event listeners receive a `Typecho\Event\Event` object. For filter events, return the next value or call
`$event->setValue()`. For action events, return a result or call `$event->setResult()`.
Use `$event->stopPropagation()` when later listeners should not run.

## Screenshots

![Typecho](https://typecho.org/usr/themes/bluecode/img/screenshot/st1.png)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.
