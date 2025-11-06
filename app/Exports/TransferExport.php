<?php

namespace App\Exports;

use App\Models\Item;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class TransferExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles
{
    protected $filters;
    protected $data;
    protected $rowNumber = 0;

    public function __construct($filters)
    {
        $this->filters = $filters;
        $this->data = $this->buildReportData();
    }

    /**
     * 🔹 Ambil data laporan mutasi (disamakan dengan versi PDF)
     */
    protected function buildReportData(): Collection
    {
        $filters = $this->filters;

        $start = $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d');
        $end   = $filters['end_date'] ?? now()->endOfMonth()->format('Y-m-d');

        $defaultWarehouse = Warehouse::first();
        $fromWarehouseId  = $filters['from_warehouse_id'] ?? $defaultWarehouse?->id;
        $toWarehouseId    = $filters['to_warehouse_id'] ?? $defaultWarehouse?->id;

        $items = Item::with(['unit', 'checkinItems', 'checkoutItems'])
            ->when($filters['category_id'] ?? null, fn($q, $cat) => $q->ofCategory($cat))
            ->get();

        return $items->map(function ($item) use ($start, $end, $fromWarehouseId, $toWarehouseId) {

            // Jumlah pemasukan barang
            $checkinQty = $item->checkinItems()
                ->whereHas('checkin', function ($q) use ($start, $end, $toWarehouseId) {
                    $q->whereBetween('date', [$start, $end])
                        ->where('warehouse_id', $toWarehouseId);
                })
                ->sum('quantity');

            // Jumlah pengeluaran barang
            $checkoutQty = $item->checkoutItems()
                ->whereHas('checkout', function ($q) use ($start, $end, $fromWarehouseId) {
                    $q->whereBetween('date', [$start, $end])
                        ->where('warehouse_id', $fromWarehouseId);
                })
                ->sum('quantity');

            // Penyesuaian (adjustment)
            $adjustmentQty = $item->hasMany(\App\Models\AdjustmentItem::class)
                ->whereHas('adjustment', function ($q) use ($start, $end, $toWarehouseId) {
                    $q->whereBetween('date', [$start, $end])
                        ->where('warehouse_id', $toWarehouseId);
                })
                ->sum('quantity');

            // Saldo awal (stok sebelum periode)
            $saldoAwal = $item->stock()
                ->where('warehouse_id', $toWarehouseId)
                ->where('created_at', '<', $start)
                ->sum('quantity');

            // Saldo akhir = saldo awal + pemasukan - pengeluaran - penyesuaian
            $saldoAkhir = $saldoAwal + $checkinQty - $checkoutQty - $adjustmentQty;

            // Jumlah barang = saldo awal + pemasukan
            $jumlahBarang = $saldoAwal + $checkinQty;

            return [
                'code'              => $item->code,
                'name'              => $item->name,
                'unit'              => $item->unit->code ?? '-',
                'jumlah_barang'     => $jumlahBarang,
                'saldo_awal'        => $saldoAwal,
                'pemasukan'         => $checkinQty,
                'pengeluaran'       => $checkoutQty,
                'adjustment'        => $adjustmentQty,
                'saldo_akhir'       => $saldoAkhir,
                'hasil_pencacahan'  => 0,
                'jumlah_selisih'    => 0,
                'keterangan'        => $item->details ?? '-',
            ];
        })->filter(function ($row) {
            return $row['pemasukan'] > 0
                || $row['pengeluaran'] > 0
                || $row['adjustment'] > 0
                || $row['saldo_awal'] != $row['saldo_akhir'];
        })->values();
    }

    /**
     * Koleksi data untuk export
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Mapping kolom ke baris Excel
     */
    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row['code'],
            $row['name'],
            $row['unit'],
            $row['jumlah_barang'],
            $row['saldo_awal'],
            $row['pemasukan'],
            $row['pengeluaran'],
            $row['adjustment'],
            $row['saldo_akhir'],
            $row['hasil_pencacahan'],
            $row['jumlah_selisih'],
            $row['keterangan'],
        ];
    }

    /**
     * Header kolom
     */
    public function headings(): array
    {
        return [
            'No',
            'Kode Barang',
            'Nama Barang',
            'Satuan Barang',
            'Jumlah Barang',
            'Saldo Awal',
            'Jumlah Pemasukan Barang',
            'Jumlah Pengeluaran Barang',
            'Penyesuaian',
            'Saldo Akhir',
            'Hasil Pencacahan',
            'Jumlah Selisih',
            'Keterangan',
        ];
    }

    /**
     * Styling dan judul sheet
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Tambahkan 3 baris kosong di atas
                $sheet->insertNewRowBefore(1, 3);

                // Judul laporan
                $sheet->mergeCells('C1:H1');
                $sheet->setCellValue('C1', 'MUTASI REPORT');
                $sheet->getStyle('C1')->getFont()->setSize(14)->setBold(true);
                $sheet->getStyle('C1')->getAlignment()->setHorizontal('center');

                // Style header tabel
                $sheet->getStyle('A4:M4')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center'
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Auto size semua kolom
                foreach (range('A', 'M') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}



