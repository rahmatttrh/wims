<?php

namespace App\Exports;

use App\Models\Checkout;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CheckoutExport implements FromCollection, WithEvents, WithDrawings, WithStyles
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Checkout::with(['contact', 'warehouse', 'items.item', 'items.unit'])
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
        $right->setCoordinates('K1');

        return [$left, $right];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Tambahkan ruang untuk logo dan judul
                $sheet->insertNewRowBefore(1, 4);

                // Judul laporan
                $sheet->mergeCells('C2:H2');
                $sheet->setCellValue('C2', 'LAPORAN PENGELUARAN BARANG');
                $sheet->getStyle('C2')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('C2')->getAlignment()->setHorizontal('center');

                $sheet->mergeCells('I2:L2');
                $sheet->setCellValue('I2', 'Tanggal Cetak: ' . now()->format('d-m-Y H:i'));
                $sheet->getStyle('I2')->getAlignment()->setHorizontal('right');

                $headerRow = 5;

                // === Header baris pertama ===
                $sheet->mergeCells("A{$headerRow}:A" . ($headerRow + 1));
                $sheet->setCellValue("A{$headerRow}", 'No');

                $sheet->mergeCells("B{$headerRow}:D{$headerRow}");
                $sheet->setCellValue("B{$headerRow}", 'Data dok pabean');

                $sheet->mergeCells("E{$headerRow}:F{$headerRow}");
                $sheet->setCellValue("E{$headerRow}", 'Bukti Pengeluaran Barang / dok lain yang sejenis');

                $sheet->mergeCells("G{$headerRow}:G" . ($headerRow + 1));
                $sheet->setCellValue("G{$headerRow}", 'Pembeli/Penerima atau pemasok barang');

                $sheet->mergeCells("H{$headerRow}:H" . ($headerRow + 1));
                $sheet->setCellValue("H{$headerRow}", 'Nama Pemilik Barang');

                $sheet->mergeCells("I{$headerRow}:I" . ($headerRow + 1));
                $sheet->setCellValue("I{$headerRow}", 'Kode barang');

                $sheet->mergeCells("J{$headerRow}:J" . ($headerRow + 1));
                $sheet->setCellValue("J{$headerRow}", 'Nama barang');

                $sheet->mergeCells("K{$headerRow}:K" . ($headerRow + 1));
                $sheet->setCellValue("K{$headerRow}", 'Satuan barang');

                $sheet->mergeCells("L{$headerRow}:L" . ($headerRow + 1));
                $sheet->setCellValue("L{$headerRow}", 'Jumlah barang');

                $sheet->mergeCells("M{$headerRow}:M" . ($headerRow + 1));
                $sheet->setCellValue("M{$headerRow}", 'Nilai barang');

                // === Header baris kedua ===
                $sheet->setCellValue("B" . ($headerRow + 1), 'Jenis');
                $sheet->setCellValue("C" . ($headerRow + 1), 'No. Daftar');
                $sheet->setCellValue("D" . ($headerRow + 1), 'Tgl. Daftar');
                $sheet->setCellValue("E" . ($headerRow + 1), 'No');
                $sheet->setCellValue("F" . ($headerRow + 1), 'Tanggal');

                // Style untuk header
                $sheet->getStyle("A{$headerRow}:M" . ($headerRow + 1))->applyFromArray([
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

                foreach ($this->collection() as $checkout) {
                    foreach ($checkout->items as $item) {
                        $sheet->setCellValue("A{$row}", $no++);
                        $sheet->setCellValue("B{$row}", $checkout->type ?? 'BC 2.7');
                        $sheet->setCellValue("C{$row}", $checkout->reference ?? '-');
                        $sheet->setCellValue("D{$row}", $checkout->date ? Carbon::parse($checkout->date)->format('d/m/Y') : '-');
                        $sheet->setCellValue("E{$row}", $checkout->transaction_number ?? '-');
                        $sheet->setCellValue("F{$row}", $checkout->date_receive ? Carbon::parse($checkout->date_receive)->format('d/m/Y') : '-');
                        $sheet->setCellValue("G{$row}", $checkout->contact->name ?? '-');
                        $sheet->setCellValue("H{$row}", $checkout->warehouse->name ?? '-');
                        $sheet->setCellValue("I{$row}", $item->item->code ?? '-');
                        $sheet->setCellValue("J{$row}", $item->item->name ?? '-');
                        $sheet->setCellValue("K{$row}", $item->unit->code ?? '-');
                        $sheet->setCellValue("L{$row}", $item->quantity ?? 0);
                        $sheet->setCellValue("M{$row}", $item->price ? 'Rp. ' . number_format($item->price, 0, ',', '.') : '-');

                        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
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

                // Auto width kolom
                foreach (range('A', 'M') as $col) {
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
