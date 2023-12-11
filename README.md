## Install

To install this package, you have to run this command:

```
composer require nero/backpack-export
```

Next step is to run:

```
php artisan vendor:publish --provider="Nero\BackpackExport\Application\ExportServiceProvider"
```

As result of command, there will be 2 files added in your application:

1. in app/config, there will be `backpack_export.php` config
2. in your resource/views/vendor/backpack/crud, there will be `list.blade.php`

## How to use

After installing you have to make 3 steps:

1. Go to the crud class where you want to enable an export
2. Include `Nero\BackpackExport\Application\Traits\ExportOperation` trait. Example:

```php
use \Nero\BackpackExport\Application\Traits\ExportOperation;
```

3. Go to the setupListOperation and call `enableAdvancedExportButtons` method:

```php
    protected function setupListOperation()
    {
        $this->enableAdvancedExportButtons();
        // ...
    }
```

It's done!. Now you can export all data from table, not only the displayed ones


## TODO

- [ ] Write a better documentation
- [ ] Make PDF compatibility
- [ ] Rewrite all comments from Russian to English
- [ ] Improve handling custom_html columns
- [ ] Refactoring code
- [ ] Convert date format in row ("Monday 11 December 2023 14:00:00") to application chosen format
- [ ] Agility to except any columns from export
