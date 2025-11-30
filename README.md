# Model Inspections for Laravel // Filament

## Installation

Publish migrations:

```bash
php artisan vendor:publish --tag=inspector-migrations
php artisan migrate
```

## Usage

```php
$inspector = new Syndicate\Inspector();
echo $inspector->echoPhrase('Hello, Syndicate!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
