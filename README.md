# Introduction

There is a problem with exporting data from pages in Backpack, it exports only the data that you see on page, and it's limited up to 100 rows.

So the main purpose of this package is to allow you to export not only data you see on page but all rows. Also, it works even if you have applied filters.


## Install

To install this package, you have to run this command:

```bash
composer require nero/backpack-export
```

Next step is to run:

```bash
php artisan vendor:publish --provider="Nero\BackpackExport\Application\ExportServiceProvider"
```

As result of command, there will be 2 files added in your application:

1. in app/config, there will be `backpack_export.php` config
2. in your resource/views/vendor/backpack/crud/inc, there will be `export_buttons.blade.php`

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

> IMPORTANT: You MUST call either `$this->crud->enableExportButtons();` or `$this->enableAdvancedExportButtons();`, but not both!


It's done!. Now you can export all data from table, not only the displayed ones

## Uninstall

Uninstalling process is not complicated. It consists of two steps:
1. removing package from composer
2. removing config, views and language files

To remove package, execute this command

```bash
composer remove nero/backpack-export
```

Then we have to delete config. It can be found in config, full path `config/backpack_export.php`:

```bash
rm config/backpack_export.php
```

Now we have to delete `export_buttons.blade.php`. It can be found as `resource/views/vendor/backpack/crud/inc/export_buttons.blade.php`:
```bash
rm resource/views/vendor/backpack/crud/inc/export_buttons.blade.php
```

As last step is deleting language packages:
```bash
rm resources/lang/en/backpack_export.php
rm resources/lang/ru/backpack_export.php
```

## TODO

- [x] Write a better documentation
- [ ] Make PDF compatibility
- [x] Rewrite all comments from Russian to English
- [ ] Improve handling custom_html columns
- [ ] Refactoring code
    - [x] Fix fetch in view (list.blade.php)
    - [x] Remove unnecessary code
- [ ] Convert date format in row ("Monday 11 December 2023 14:00:00") to application chosen format
- [ ] Agility to except any columns from export

