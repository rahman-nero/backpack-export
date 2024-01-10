<?php

namespace Nero\BackpackExport\Contracts;


interface ExportInterface
{
    /**
     * Method to fill data in the file. Works as mode "append".
     * In other words, method can be called multiple times, each time it will add data in the end
     *
     * @param array $data
     * @return void
     */
    public function fill(array $data): void;

    /**
     * Method to fill first row of the file. Usually first row used for columns.
     * Each call will overwrite first row in the file
     *
     * @param array $columns
     * @return void
     */
    public function fillColumns(array $columns): void;

    /**
     * Method to save file. After successfully saving the file, return full path to file, including the file name
     *
     * @param string $directory_path - absolute path to directory where the file will be saved
     * @param string|null $file_name - custom file name, it isn't required
     * @return string
     */
    public function save(string $directory_path, ?string $file_name = null): string;
}
