<?php

namespace Nero\BackpackExport\Enums;

/**
 * Список поддерживаемых расширений
*/
enum SupportedExtension: string
{
    case EXCEL = 'xlsx';

//    case PDF = 'pdf';

    case CSV = 'csv';
}
