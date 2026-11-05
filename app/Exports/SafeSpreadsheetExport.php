<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Base class for every spreadsheet export.
 *
 * Strings are always written as text, so a staff-entered value starting with
 * "=" can never run as a formula when an admin opens the file (formula
 * injection). Numbers, booleans and blanks keep their native types, so numeric
 * columns stay summable and empty cells stay empty — pass numbers as int/float,
 * not as strings.
 */
abstract class SafeSpreadsheetExport extends StringValueBinder implements WithCustomValueBinder
{
    protected $convertNull = false;

    protected $convertBoolean = false;

    protected $convertNumeric = false;
}
