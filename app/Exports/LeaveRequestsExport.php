<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<LeaveRequest>
 */
class LeaveRequestsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, LeaveRequest>  $leaveRequests
     */
    public function __construct(private readonly Collection $leaveRequests) {}

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function collection(): Collection
    {
        return $this->leaveRequests;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama Santri', 'Jenis Izin', 'Rentang Tanggal', 'Alasan', 'Status'];
    }

    /**
     * @param  LeaveRequest  $leaveRequest
     * @return array<int, string|null>
     */
    public function map($leaveRequest): array
    {
        return [
            $leaveRequest->student?->name,
            $leaveRequest->type,
            "{$leaveRequest->start_date} s/d {$leaveRequest->end_date}",
            $leaveRequest->reason,
            $leaveRequest->status,
        ];
    }
}
