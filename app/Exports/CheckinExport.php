<?php

namespace App\Exports;

use App\Models\Checkin;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CheckinExport implements FromCollection, WithEvents, WithDrawings, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Checkin::with(['contact', 'warehouse', 'items.item', 'items.unit'])
            ->reportFilter($this->filters)
            ->orderBy('id', 'asc')
            ->get();
    }

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
        $drawingRight->setCoordinates('J1');

        return [$drawingLeft, $drawingRight];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Insert baris kosong untuk header/logo
                $sheet->insertNewRowBefore(1, 4);

                // Judul
                $sheet->mergeCells('C2:H2');
                $sheet->setCellValue('C2', 'INBOUND REPORT');
                $sheet->getStyle('C2')->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle('C2')->getAlignment()
                    ->setHorizontal('center')
                    ->setVertical('center');

                // Tanggal Generate
                $sheet->mergeCells('I2:J2');
                $sheet->setCellValue('I2', 'Tanggal Generate : ' . now()->format('d-M-Y H:i'));
                $sheet->getStyle('I2')->getAlignment()->setHorizontal('right');

                // ==========================
                // HEADER MULTI-BARIS
                // ==========================
                $headerRow = 5;

                // Merge untuk kolom A–G agar konsisten 2 baris
                $sheet->mergeCells('A' . $headerRow . ':A' . ($headerRow + 1));
                $sheet->setCellValue('A' . $headerRow, 'No');

                $sheet->mergeCells('B' . $headerRow . ':B' . ($headerRow + 1));
                $sheet->setCellValue('B' . $headerRow, 'Transaction Number');

                $sheet->mergeCells('C' . $headerRow . ':C' . ($headerRow + 1));
                $sheet->setCellValue('C' . $headerRow, 'Reference / No Aju');

                $sheet->mergeCells('D' . $headerRow . ':D' . ($headerRow + 1));
                $sheet->setCellValue('D' . $headerRow, 'Tanggal');

                $sheet->mergeCells('E' . $headerRow . ':E' . ($headerRow + 1));
                $sheet->setCellValue('E' . $headerRow, 'Jumlah Qty');

                $sheet->mergeCells('F' . $headerRow . ':F' . ($headerRow + 1));
                $sheet->setCellValue('F' . $headerRow, 'Contact');

                $sheet->mergeCells('G' . $headerRow . ':G' . ($headerRow + 1));
                $sheet->setCellValue('G' . $headerRow, 'Warehouse');

                // Merge untuk grup "Item"
                $sheet->mergeCells('H' . $headerRow . ':J' . $headerRow);
                $sheet->setCellValue('H' . $headerRow, 'Item');

                // Sub-header untuk kolom H–J
                $sheet->setCellValue('H' . ($headerRow + 1), 'Description');
                $sheet->setCellValue('I' . ($headerRow + 1), 'Weight');
                $sheet->setCellValue('J' . ($headerRow + 1), 'Qty');

                // Style heading
                $sheet->getStyle("A{$headerRow}:J" . ($headerRow + 1))->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // ==========================
                // ISI DATA
                // ==========================
                $row = $headerRow + 2;
                $no = 1;

                foreach ($this->collection() as $checkin) {
                    // baris utama
                    $sheet->setCellValue("A{$row}", $no++);
                    $sheet->setCellValue("B{$row}", $checkin->transaction_number ?? '-');
                    $sheet->setCellValue("C{$row}", $checkin->reference ?? '-');
                    $sheet->setCellValue("D{$row}", $checkin->date ? Carbon::parse($checkin->date)->format('Y-m-d') : '-');
                    $sheet->setCellValue("E{$row}", $checkin->items->sum('quantity') ?? 0);
                    $sheet->setCellValue("F{$row}", $checkin->contact->name ?? '-');
                    $sheet->setCellValue("G{$row}", $checkin->warehouse->name ?? '-');
                    $sheet->mergeCells("H{$row}:J{$row}");
                    $sheet->setCellValue("H{$row}", 'Packaging List');

                    // style border + alignment center
                    $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'wrapText'   => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                    $row++;

                    // detail item
                    foreach ($checkin->items as $item) {
                        $sheet->setCellValue("H{$row}", $item->item->name ?? '-');
                        $sheet->setCellValue("I{$row}", number_format($item->weight ?? 0, 2) . ' kg');
                        $sheet->setCellValue("J{$row}", number_format($item->quantity ?? 0, 2) . ' ' . ($item->unit->code ?? '-'));

                        // kosongkan kolom lain
                        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
                            $sheet->setCellValue("{$col}{$row}", '');
                        }

                        // style border + alignment center
                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                'wrapText'   => true,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                ],
                            ],
                        ]);

                        $row++;
                    }
                }

                // Auto-size kolom
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}
