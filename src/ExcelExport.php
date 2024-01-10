<?php

namespace Nero\BackpackExport;

use Nero\BackpackExport\Contracts\ExportInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ExcelExport implements ExportInterface
{
    /**
     * Index of last written row
     * @var int
     */
    private int $row = 0;

    /**
     * True if columns from file is filled with data
     * @var bool
     */
    private bool $is_columns_filled = false;

    private Spreadsheet $spreadsheet;

    private Xlsx $xlsx_writer;

    public function __construct(Spreadsheet $spreadsheet, Xlsx $xlsx_writer)
    {
        $this->spreadsheet = $spreadsheet;
        $this->xlsx_writer = $xlsx_writer;
    }

    /**
     * @inheritDoc
     */
    public function fill(array $data): void
    {
        // If columns from the file is not empty, return 1 as last written row otherwise 0
        $last_row = $this->isColumnsFilled()
            ? $this->row
            : 1;

        // Filling the file starting from last written row + 1
        $this->spreadsheet->getActiveSheet()
            ->fromArray($data, null, 'A' . $last_row + 1);

        // Updating last written row to actual value
        $this->row += count($data);
    }

    /**
     * @inheritDoc
     */
    public function fillColumns(array $columns): void
    {
        $columns = array_values($columns);

        $this->spreadsheet->getActiveSheet()
            ->fromArray([$columns], null, 'A1');

        // Making font bold
        $this->spreadsheet->getActiveSheet()
            ->getStyle('1:1')->getFont()->setBold(true);

        // Noting that the columns is written in file
        $this->is_columns_filled = true;

        // Assign 1 as last written row
        $this->row = 1;
    }

    /**
     * @inheritDoc
     */
    public function save(string $directory_path, ?string $file_name = null): string
    {
        $date = date('Y-m-d_H-i-s');
        $file_name = $file_name ?? "Export_{$date}.xlsx";

        $file_path = sprintf("%s/%s", $directory_path, $file_name);
        $this->xlsx_writer->save($file_path);

        return $file_path;
    }

    /**
     * Checks if the columns from file is filled with text
     * @return bool
     */
    public function isColumnsFilled(): bool
    {
        return $this->is_columns_filled;
    }
}
