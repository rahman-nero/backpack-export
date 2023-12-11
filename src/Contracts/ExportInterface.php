<?php

namespace Nero\BackpackExport\Contracts;


interface ExportInterface
{
    /**
     * Метод заполнения данными в файл. Работает в режиме "append".
     * Другие словами метод может вызываться многократно, с каждым вызовов переданные данные будут записываться в конец
     * @param array $data
     * @return void
     */
    public function fill(array $data): void;

    /**
     * Метод для заполнения колонок (первый ряд файла)
     * При каждом вызове перезаписывает первую строку.
     * @param array $columns
     * @return void
     */
    public function fillColumns(array $columns): void;

    /**
     * Метод сохранения файла. После сохранения возвращает полный путь до файла, включая самое имя файла
     *
     * @param string $directory_path - путь до директорий в котором будет сохранен файл
     * @param string|null $file_name - имя файла, необязательное
     * @return string
     */
    public function save(string $directory_path, ?string $file_name = null): string;

}
