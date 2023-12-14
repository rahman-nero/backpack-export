<?php

namespace Nero\BackpackExport\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nero\BackpackExport\Enums\SupportedExtension;
use Nero\BackpackExport\ExportFactory;

/**
 * Job to export data from page
 */
final class Export implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Tries until export will be marked as failed
     */
    public int $tries = 1;

    /**
     * Timeout for execution
     */
    private int $timeout;

    /**
     * Contains QueryBuilder that has been serialized
     */
    private string $serialized_query;

    /**
     * Columns that has been defined in setupOperationList
     * Contains all data about columns
     */
    private array $columns;

    /**
     * ID User that started export
     */
    private ?int $user_id;
    private SupportedExtension $extension;

    /**
     * @param int $user_id
     * @param string $serialized_query
     * @param array $columns
     * @param SupportedExtension $extension
     */
    public function __construct(
        int                $user_id,
        string             $serialized_query,
        array              $columns,
        SupportedExtension $extension
    )
    {
        $this->onQueue(config('backpack_export.queue'));
        $this->timeout = config('backpack_export.timeout');

        $this->user_id = $user_id;
        $this->serialized_query = $serialized_query;
        $this->columns = $columns;
        $this->extension = $extension;
    }

    public function handle(ExportFactory $export_factory): void
    {
        /**
         * @var Builder $query
         */
        $query = \EloquentSerialize::unserialize($this->serialized_query);

        // Getting specific exporter class based on extension
        $writer = $export_factory->make($this->extension);

        // Defined names of columns
        $labels = array_column($this->columns, 'label');

        // Filling first row of export file with columns
        $writer->fillColumns($labels);

        // Chunking rows from database to not load database
        $query->chunk(config('backpack_export.chunk'), function ($entries) use ($writer) {

            $data = [];

            $columns = $this->columns;

            // "Casts" from model
            $casts = $entries->first()->getCasts();

            foreach ($entries as $item) {
                $fill = [];

                foreach ($columns as $column_settings) {

                    // Column's name
                    $column_name = $column_settings['name'];

                    // Type of column
                    $type = $column_settings['type'];

                    // Attribute for column
                    $attribute = $column_settings['attribute'] ?? null;

                    // If column type is 'relationship' or 'custom_html', then just treat it like relationship
                    if ($type === 'relationship' || $type === 'custom_html') {

                        $relationship_result = null;

                        // If it's collecting, we join all data with ;
                        if ($item->{$column_name} instanceof Collection) {
                            $relationship_result = $item->{$column_name}->pluck($attribute)?->join(';');
                        } elseif ($item->{$column_name} instanceof Model) {
                            // If it's just model, then it means it's one-to-one relationship, so we mustn't join data
                            $relationship_result = $item->{$column_name}->{$attribute};
                        } elseif (array_key_exists($column_name, $casts) && $casts[$column_name] === 'array') {
                            // If our columns is 'array' in model casts, then we join it with native php function
                            $relationship_result =
                                $item->{$column_name} !== null
                                    ? implode(';', $item->{$column_name})
                                    : null;
                        }

                        $fill[] = $relationship_result;
                    } elseif ($type === 'date') {
                        $fill[] = $item->{$column_name}?->format('l j F Y');
                    } elseif ($type === 'datetime') {
                        $fill[] = $item->{$column_name}?->format('l j F Y H:i:s');
                    } elseif (array_key_exists($column_name, $casts) && $casts[$column_name] === 'array') {
                        // If our columns is 'array' in model casts, then we join it with native php function
                        $fill[] = implode(';', $item->{$column_name} ?? []);
                    } else {
                        // All unhandled types will not be handled in any type
                        $fill[] = $item->{$column_name};
                    }

                }

                $data[] = $fill;
            }

            // Filling export file up
            $writer->fill($data);
        });

        // Saving file
        $path_to_file = $writer->save($this->getOrCreatePath());

        // Notifying user that the export is done
        $this->notifyUser($path_to_file);
    }

    /**
     * Method to notify the user that the export is done
     * @param string $path_to_file
     * @return void
     */
    private function notifyUser(string $path_to_file): void
    {
        // Getting Notification class
        $notify = config('backpack_export.notify');

        // Getting User model
        $user_model = config('backpack_export.user_model');

        // File name of export
        $file_name = basename($path_to_file);

        $user_model::query()
            ->findOrFail($this->user_id)
            ->notify(new $notify($path_to_file, $file_name));
    }

    /**
     * @return string
     */
    public function getOrCreatePath(): string
    {
        // Path where to save file
        $path = config('backpack_export.directory_to_save');

        // If the path don't exist, we will try to create
        if (!file_exists($path)) {
            mkdir($path, 0755);
        }

        return $path;
    }
}

