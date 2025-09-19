<?php

namespace App\Exports;

use App\Models\Checkout;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CheckoutExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Checkout::with(['contact', 'warehouse', 'user'])
            ->reportFilter($this->filters)
            ->orderByDesc('id')
            ->get();
    }

    public function map($checkout): array
    {
        return [
            ++$this->rowNumber,
            $checkout->reference,
            $checkout->date ? \Carbon\Carbon::parse($checkout->date)->format('Y-m-d') : '',
            $checkout->contact->name ?? '',
            $checkout->warehouse->name ?? '',
            $checkout->user->name ?? '',
            $checkout->draft == 1 ? 'Yes' : 'No',
            $checkout->deleted_at ? 'Yes' : 'No',
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
