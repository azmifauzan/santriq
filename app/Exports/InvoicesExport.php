<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Invoice>
 */
class InvoicesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    public function __construct(private readonly Collection $invoices) {}

    /**
     * @return Collection<int, Invoice>
     */
    public function collection(): Collection
    {
        return $this->invoices;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Periode', 'Nama Santri', 'Kelas', 'Jumlah', 'Jatuh Tempo', 'Status'];
    }

    /**
     * @param  Invoice  $invoice
     * @return array<int, string|null>
     */
    public function map($invoice): array
    {
        return [
            $invoice->period,
            $invoice->student?->name,
            $invoice->student?->classroom?->name,
            (string) $invoice->amount,
            $invoice->due_date,
            $invoice->status,
        ];
    }
}
