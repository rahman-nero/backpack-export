<?php

namespace Nero\BackpackExport;

use Nero\BackpackExport\Enums\SupportedExtension;

final class ExportFactory
{
    public function make(SupportedExtension $extension)
    {
        return match ($extension) {
//            SupportedExtension::PDF => new (),
            SupportedExtension::CSV => app(CSVExport::class),
            SupportedExtension::EXCEL => app(ExcelExport::class),
        };
    }

}
