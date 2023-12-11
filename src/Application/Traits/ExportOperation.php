<?php

namespace Nero\BackpackExport\Application\Traits;

use Nero\BackpackExport\Application\Jobs\Export;
use Nero\BackpackExport\Enums\SupportedExtension;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

trait ExportOperation
{
    public function setupExportRoutes($segment, $routeName, $controller)
    {
        Route::post($segment . '/export', [
            'as'        => $routeName . '.export',
            'uses'      => $controller . '@setupExportOperation',
            'operation' => 'list',
        ]);
    }

    public function setupExportOperation()
    {
        $user = Auth::user();

        // объявленные колонки из setupListOperation
        $columns = $this->removeCallbackValuesFromColumns($this->crud->columns());

        // Засериализированный запрос для передачи в джоб
        $serialized_query = \EloquentSerialize::serialize($this->crud->query);

        $export_type = $this->crud->getRequest()->input('export_type');

        $type = match ($export_type) {
            'csv' => SupportedExtension::CSV,
            'excel' => SupportedExtension::EXCEL,
            default => throw new \InvalidArgumentException(),
        };

        // Отправка джоба на создание
        Export::dispatch($user->id, $serialized_query, $columns, $type);

        return response()->json([
            'message' => $this->message(),
        ]);
    }

    /**
     * Метод очищает все value которые имеют в себе callback функции
     * В джоб нельзя передать массив или callback функцию, ибо он попытается это сериализировать и выйдет ошибка
     */
    protected function removeCallbackValuesFromColumns(array $columns)
    {
        foreach ($columns as $column_key => $column) {
            if (array_key_exists('value', $column) && is_callable($column['value'])) {
                $columns[$column_key]['value'] = 'removed_callback';
            }
        }

        return $columns;
    }

    public function enableAdvancedExportButtons()
    {
        $this->crud->setOperationSetting('advancedExportButtons', true);
    }

    protected function message()
    {
        $user = Auth::user();

        return "Сформированный отчет будет отправлен на {$user->email}";
    }
}
