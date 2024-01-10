<?php

namespace Nero\BackpackExport\Application\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Nero\BackpackExport\Application\Jobs\Export;
use Nero\BackpackExport\Enums\SupportedExtension;

trait ExportOperation
{
    /**
     * Setting main routes up for export
     */
    public function setupExportRoutes($segment, $routeName, $controller)
    {
        Route::post($segment . '/export', [
            'as'        => $routeName . '.export',
            'uses'      => $controller . '@setupExportOperation',
            'operation' => 'list',
        ]);
    }

    /**
     * Main method starting exporting
     */
    public function setupExportOperation()
    {
        $user = Auth::user();

        // Type of export that user wants
        $export_type = $this->crud->getRequest()->input('export_type');

        // All columns that will be in export
        $visible_columns = $this->crud->getRequest()->input('columns');

        // All set up columns from setupListOperation. Cleaned up before serialization
        $columns = $this->removeCallbackValuesFromColumns($this->crud->columns());

        // Serialized query to pass to the job
        $serialized_query = \EloquentSerialize::serialize($this->crud->query);

        $filtered_columns = $this->filterColumns($columns, $visible_columns);

        $type = match ($export_type) {
            'csv' => SupportedExtension::CSV,
            'excel' => SupportedExtension::EXCEL,
            default => throw new \InvalidArgumentException("Undefined export type {$export_type}"),
        };

        Export::dispatch($user->id, $serialized_query, $filtered_columns, $type);

        // Returning message to notify user
        return response()->json([
            'message' => $this->message(),
        ]);
    }

    /**
     * This method clears all values that contain callback functions
     * You cannot pass any callback function to a job, because it will try to serialize it and an error will occur
     *
     * @param array $columns
     * @return array
     */
    protected function removeCallbackValuesFromColumns(array $columns): array
    {
        foreach ($columns as $column_key => $column) {
            if (array_key_exists('value', $column) && is_callable($column['value'])) {
                $columns[$column_key]['value'] = 'removed_callback';
            }

            if (array_key_exists('wrapper', $column)) {
                $columns[$column_key]['wrapper'] = null;
            }
        }

        return $columns;
    }

    /**
     * Remove all columns that hidden by user
     * @param array $columns
     * @param array $visible_columns
     * @return array
     */
    public function filterColumns(array $columns, array $visible_columns): array
    {
        return array_filter($columns, function ($item, $key) use ($visible_columns) {
            return in_array($key, $visible_columns);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Message that user will be sent to user after successfully starting export process
     *
     * @return string
     */
    protected function message(): string
    {
        $user = Auth::user();

        return __('backpack_export.successfully_request', ['email' => $user->email]);
    }

    /**
     * Enabling package
     * @return void
     */
    public function enableAdvancedExportButtons(): void
    {
        $this->crud->setOperationSetting('exportButtons', true);
        $this->crud->setOperationSetting('advancedExportButtons', true);
        $this->crud->setOperationSetting('showTableColumnPicker', true);
    }
}
