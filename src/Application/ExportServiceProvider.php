<?php

namespace Nero\BackpackExport\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Nero\BackpackExport\CSVExport;
use Nero\BackpackExport\ExcelExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExportServiceProvider extends ServiceProvider
{

    public function boot()
    {
        Carbon::setLocale(config('app.locale'));

        setlocale(LC_TIME, config('app.locale'));

        // Publishing config
        $this->publishes([
            __DIR__ . '/config/backpack_export.php' => config_path('backpack_export.php'),
        ]);

        // Publishing views
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/backpack/crud/inc/'),
        ]);

        // Publishing languages
        $this->publishes([
            __DIR__ . '/resources/lang' => $this->app->langPath('vendor/backpack-export'),
        ]);
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ExcelExport::class, function () {
            $spreadsheet = new Spreadsheet();
            $writer = new Xlsx($spreadsheet);
            return new ExcelExport($spreadsheet, $writer);
        });

        $this->app->bind(CSVExport::class, function () {
            $spreadsheet = new Spreadsheet();
            $writer = new Csv($spreadsheet);
            return new CSVExport($spreadsheet, $writer);
        });
    }
}
