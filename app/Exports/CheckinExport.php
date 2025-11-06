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
        $left = new Drawing();
        $left->setPath(public_path('logos/icon.jpg'));
        $left->setHeight(60);
        $left->setCoordinates('A1');

        $right = new Drawing();
        $right->setPath(public_path('logos/icon2.jpg'));
        $right->setHeight(60);
        $right->setCoordinates('J1');

        return [$left, $right];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Tambah spasi untuk header
                $sheet->insertNewRowBefore(1, 4);

                // Judul laporan
                $sheet->mergeCells('C2:H2');
                $sheet->setCellValue('C2', 'LAPORAN PENERIMAAN BARANG');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('C2')->getAlignment()
                    ->setHorizontal('center')
                    ->setVertical('center');

                $sheet->setCellValue('I2', 'Tanggal Cetak : ' . now()->format('d-m-Y H:i'));
                $sheet->getStyle('I2')->getAlignment()->setHorizontal('right');

                // Baris header
                $headerRow = 5;

                // === Baris pertama header ===
                $sheet->setCellValue("A{$headerRow}", 'No');
                $sheet->mergeCells("B{$headerRow}:D{$headerRow}");
                $sheet->setCellValue("B{$headerRow}", 'Data Dokumen Pabean');
                $sheet->mergeCells("E{$headerRow}:F{$headerRow}");
                $sheet->setCellValue("E{$headerRow}", 'Bukti Penerimaan Barang / GRN / Dok lain sejenis');
                $sheet->mergeCells("G{$headerRow}:L{$headerRow}");
                $sheet->setCellValue("G{$headerRow}", 'Detail Barang');

                // === Baris kedua header ===
                $sheet->setCellValue("B" . ($headerRow + 1), 'Jenis');
                $sheet->setCellValue("C" . ($headerRow + 1), 'No. Daftar');
                $sheet->setCellValue("D" . ($headerRow + 1), 'Tgl. Daftar');
                $sheet->setCellValue("E" . ($headerRow + 1), 'No');
                $sheet->setCellValue("F" . ($headerRow + 1), 'Tanggal');
                $sheet->setCellValue("G" . ($headerRow + 1), 'Pengirim / Pemasok');
                $sheet->setCellValue("H" . ($headerRow + 1), 'Pemilik Barang');
                $sheet->setCellValue("I" . ($headerRow + 1), 'Kode Barang');
                $sheet->setCellValue("J" . ($headerRow + 1), 'Nama Barang');
                $sheet->setCellValue("K" . ($headerRow + 1), 'Satuan');
                $sheet->setCellValue("L" . ($headerRow + 1), 'Jumlah');

                // Merge No di dua baris
                $sheet->mergeCells("A{$headerRow}:A" . ($headerRow + 1));

                // Style header
                $sheet->getStyle("A{$headerRow}:L" . ($headerRow + 1))->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // === Data rows ===
                $row = $headerRow + 2;
                $no = 1;

                foreach ($this->collection() as $checkin) {
                    foreach ($checkin->items as $item) {
                        $sheet->setCellValue("A{$row}", $no++);
                        $sheet->setCellValue("B{$row}", $checkin->type ?? 'BC 1.6');
                        $sheet->setCellValue("C{$row}", $checkin->reference ?? '-');
                        $sheet->setCellValue("D{$row}", $checkin->date ? Carbon::parse($checkin->date)->format('d/m/Y') : '-');
                        $sheet->setCellValue("E{$row}", $checkin->transaction_number ?? '-');
                        $sheet->setCellValue("F{$row}", $checkin->date_receive ? Carbon::parse($checkin->date_receive)->format('d/m/Y') : '-');
                        $sheet->setCellValue("G{$row}", $checkin->contact->name ?? '-');
                        $sheet->setCellValue("H{$row}", $checkin->warehouse->name ?? '-');
                        $sheet->setCellValue("I{$row}", $item->item->code ?? '-');
                        $sheet->setCellValue("J{$row}", $item->item->name ?? '-');
                        $sheet->setCellValue("K{$row}", $item->unit->code ?? '-');
                        $sheet->setCellValue("L{$row}", $item->quantity ?? 0);

                        // border tiap baris
                        $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                ],
                            ],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                'wrapText' => true,
                            ],
                        ]);
                        $row++;
                    }
                }

                // Auto-size kolom
                foreach (range('A', 'L') as $col) {
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
