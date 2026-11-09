<?php

namespace App\Exports\Guides;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

abstract class GuideSheet implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(protected int $rows) {}

    /**
     * @return array<int, array<int, int|string>>
     */
    abstract public function array(): array;
}
