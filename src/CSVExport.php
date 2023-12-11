<?php

namespace Nero\BackpackExport;

use Nero\BackpackExport\Contracts\ExportInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

final class CSVExport implements ExportInterface
{
    /**
     * Последняя строка в которую писали данные
     */
    private int $row = 0;

    /**
     * Хранит информацию о том, есть ли колонки в файле
     */
    private bool $is_columns_filled = false;

    private Csv $csv_writer;
    private Spreadsheet $spreadsheet;

    public function __construct(Spreadsheet $spreadsheet, Csv $csv_writer)
    {
        $this->spreadsheet = $spreadsheet;
        $this->csv_writer = $csv_writer;
    }

    /**
     * @inheritdoc
     */
    public function fill(array $data): void
    {
        // Если первую строку не заняли колонками, то просто оставляем строку пустым
        $last_row = $this->isColumnsFilled()
            ? $this->row
            : 1;

        // Присваиваем данные, начиная от А1
        $this->spreadsheet->getActiveSheet()
            ->fromArray($data, null, 'A' . $last_row + 1);

        // Указываем, где последняя строка в которую записали
        $this->row += count($data);
    }

    /**
     * @inheritdoc
     */
    public function fillColumns(array $columns): void
    {
        $columns = array_values($columns);

        $this->spreadsheet->getActiveSheet()
            ->fromArray([$columns], null, 'A1');

        // Делаем марку о том, что заполнили колонки (первую строку)
        $this->is_columns_filled = true;

        // Присваиваем 1, ибо заняли первую строку
        $this->row = 1;
    }

    /**
     * @inheritdoc
     */
    public function save(string $directory_path, ?string $file_name = null): string
    {
        $date = date('Y-m-d_H-i-s');
        $file_name = $file_name ?? "Export_{$date}.csv";

        $file_path = sprintf("%s/%s", $directory_path, $file_name);
        $this->csv_writer->save($file_path);

        return $file_path;
    }

    /**
     * Проверяет заполнены ли колонки
     * @return bool
     */
    public function isColumnsFilled(): bool
    {
        return $this->is_columns_filled;
    }
}
