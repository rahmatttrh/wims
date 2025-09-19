<?php

namespace App\Exports;

use App\Models\Transfer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransferExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Transfer::with(['fromWarehouse', 'toWarehouse', 'user'])
            ->reportFilter($this->filters)
            ->orderByDesc('id')
            ->get();
    }

    public function map($transfer): array
    {
        return [
            ++$this->rowNumber,
            $transfer->reference,
            $transfer->date ? \Carbon\Carbon::parse($transfer->date)->format('Y-m-d') : '',
            $transfer->fromWarehouse->name ?? '',
            $transfer->toWarehouse->name ?? '',
            $transfer->user->name ?? '',
            $transfer->draft == 1 ? 'Yes' : 'No',
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'Reference',
            'Tanggal',
            'Dari Warehouse',
            'Tujuan Warehouse',
            'User',
            'Draft',
        ];
    }
}
