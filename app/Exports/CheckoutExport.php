<?php

namespace App\Exports;

use App\Models\Checkout;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CheckoutExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithDrawings, WithStyles
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
            $checkout->date ? Carbon::parse($checkout->date)->format('Y-m-d') : '',
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

    /**
     * Tambahkan logo kiri & kanan
     */
    public function drawings()
    {
        $drawingLeft = new Drawing();
        $drawingLeft->setName('Logo Left');
        $drawingLeft->setDescription('Company Logo Left');
        $drawingLeft->setPath(public_path('logos/icon.jpg'));
        $drawingLeft->setHeight(60);
        $drawingLeft->setCoordinates('A1');

        $drawingRight = new Drawing();
        $drawingRight->setName('Logo Right');
        $drawingRight->setDescription('Company Logo Right');
        $drawingRight->setPath(public_path('logos/icon2.jpg'));
        $drawingRight->setHeight(60);
        $drawingRight->setCoordinates('H1');

        return [$drawingLeft, $drawingRight];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Tambah spasi baris untuk header laporan
                $sheet->insertNewRowBefore(1, 3);

                // Judul di tengah baris 2
                $sheet->mergeCells('C1:F1');
                $sheet->setCellValue('C1', 'OUTBOUND REPORT');

                // Style judul
                $sheet->getStyle('C1')->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle('C1')->getAlignment()
                    ->setHorizontal('center')
                    ->setVertical('center');

                // Atur heading tabel (baris 5)
                $sheet->getStyle('A4:H4')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Auto-size kolom
                foreach (range('A', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Tinggi baris untuk logo & judul
                // $sheet->getRowDimension(1)->setRowHeight(45);
                // $sheet->getRowDimension(2)->setRowHeight(30);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}
