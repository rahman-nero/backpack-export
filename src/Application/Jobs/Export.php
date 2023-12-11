<?php

namespace Nero\BackpackExport\Application\Jobs;

use Nero\BackpackExport\Enums\SupportedExtension;
use Nero\BackpackExport\ExportFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job для экспорта данных со страниц
 */
final class Export implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Количество попыток выполнения job.
     * Если при истечений всех попыток не удалось выполнить, job отправиться в failed_jobs
     */
    public int $tries = 1;

    private int $timeout;

    /**
     * Содержит QueryBuilder которого сериализовали
     * Нужен для получения корректных записей.
     */
    private string $serialized_query;

    /**
     * Колонки из таблицы на странице, с которого делается экспорт
     */
    private array $columns;

    /**
     * ID-пользователя, кто хочет получить отчет
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
        // Получение отфильтрованных моделей которые выводятся на странице List
        /**
         * @var Builder $query
         */
        $query = \EloquentSerialize::unserialize($this->serialized_query);

        // Получаем нужный вид экспорт-сервиса
        $writer = $export_factory->make($this->extension);

        // Названия всех колонок
        $labels = array_column($this->columns, 'label');

        // Заполняем колонки
        $writer->fillColumns($labels);

        $query->chunk(config('backpack_export.chunk'), function ($entries) use ($writer) {

            $data = [];

            // Информация о колонках которые выводятся на странице List
            $columns = $this->columns;

            // $casts свойство у модели
            $casts = $entries->first()->getCasts();

            // Перебор данных из бд
            foreach ($entries as $item) {
                $fill = [];

                foreach ($columns as $column_settings) {

                    // Название колонки
                    $column_name = $column_settings['name'];

                    // Тип колонки, может быть 'string', 'text', 'relationship' и 'custom_html'
                    $type = $column_settings['type'];

                    // Аттрибут модели, обычной нужен для связей
                    $attribute = $column_settings['attribute'] ?? null;

                    // Если тип 'relationship', то ищем attribute
                    // Если тип 'relationship', но там находится 'custom_html', то делаем простой вывод как будто это обычный relationship
                    if ($type === 'relationship' || $type === 'custom_html') {

                        $relationship_result = null;

                        // Если это связь выдает коллекцию, то перебираем и делаем конкатенацию через ;
                        if ($item->{$column_name} instanceof Collection) {
                            $relationship_result = $item->{$column_name}->pluck($attribute)?->join(';');
                        } elseif ($item->{$column_name} instanceof Model) {
                            // Если это простая модель, то есть связь один к одному
                            $relationship_result = $item->{$column_name}->{$attribute};
                        } elseif (array_key_exists($column_name, $casts) && $casts[$column_name] === 'array') {
                            // Если текущая колонка в casts отмечена как массив, то делаем join
                            $relationship_result =
                                $item->{$column_name} !== null
                                    ? implode(';', $item->{$column_name})
                                    : null;
                        }

                        // Получаем значения по связи, получаем колонки по $attribute и делаем джоин с ; и получаем строку
                        $fill[] = $relationship_result;
                    } elseif ($type === 'date') {
                        $fill[] = $item->{$column_name}?->format('l j F Y');
                    } elseif ($type === 'datetime') {
                        $fill[] = $item->{$column_name}?->format('l j F Y H:i:s');
                    } elseif (array_key_exists($column_name, $casts) && $casts[$column_name] === 'array') {
                        // Если текущая колонка в casts отмечена как массив, то делаем join
                        $fill[] = implode(';', $item->{$column_name} ?? []);
                    } else {
                        // На все остальные типы, применяем просто
                        $fill[] = $item->{$column_name};
                    }

                }

                $data[] = $fill;
            }

            $writer->fill($data);
        });

        // Сохранение файла
        $path_to_file = $writer->save($this->getOrCreatePath());

        $this->notifyUser($path_to_file);
    }

    /**
     * Метод для уведомления пользователя о завершении экcпорта и отправка ссылки ему
     * @param string $path_to_file
     * @return void
     */
    private function notifyUser(string $path_to_file): void
    {
        $notify = config('backpack_export.notify');
        $user_model = config('backpack_export.user_model');

        // Имя файла экспорта
        $file_name = basename($path_to_file);

        $user_model::query()
            ->findOrFail($this->user_id)
            ->notify(new $notify($path_to_file, $file_name));
    }

    public function getOrCreatePath()
    {
        $path = config('backpack_export.directory_to_save');

        if (!file_exists($path)) {
            mkdir($path, 0755);
        }

        return $path;
    }
}

