<?php

namespace App\Exports;

use App\Models\Checkin;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CheckinExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Checkin::with(['contact', 'warehouse', 'user'])
            ->reportFilter($this->filters)
            ->orderByDesc('id')
            ->get();
    }

    public function map($checkin): array
    {
        return [
            ++$this->rowNumber,
            $checkin->reference,
            // $checkin->date ? $checkin->date->format('Y-m-d') : '',
            $checkin->date ? \Carbon\Carbon::parse($checkin->date)->format('Y-m-d') : '',
            $checkin->contact->name ?? '',
            $checkin->warehouse->name ?? '',
            $checkin->user->name ?? '',
            $checkin->draft == 1 ? 'Yes' : 'No',
            $checkin->deleted_at ? 'Yes' : 'No',
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Reference',
            'Tanggal',
            'Contact',
            'Warehouse',
            'User',
            'Draft',
            'Deleted',
        ];
    }
}
