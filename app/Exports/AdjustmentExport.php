<?php

namespace App\Exports;

use App\Models\Adjustment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdjustmentExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Adjustment::with(['contact', 'warehouse', 'user'])
            ->reportFilter($this->filters)
            ->orderByDesc('id')
            ->get();
    }

    public function map($adjustment): array
    {
        return [
            ++$this->rowNumber,
            $adjustment->reference,
            $adjustment->date ? \Carbon\Carbon::parse($adjustment->date)->format('Y-m-d') : '',
            $adjustment->warehouse->name ?? '',
            $adjustment->user->name ?? '',
            $adjustment->draft == 1 ? 'Yes' : 'No',
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Reference',
            'Tanggal',
            'Warehouse',
            'User',
            'Draft',
        ];
    }
}
