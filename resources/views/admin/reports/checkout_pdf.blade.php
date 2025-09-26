<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Outbound Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 30px; }
    .header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .logo { width: 70px; }
    .company { margin-left: 15px; }
    .company h1 { margin: 0; font-size: 18px; }
    .company p { margin: 2px 0; font-size: 12px; }
    h2 { text-align: center; margin: 10px 0; }
    .meta { margin-bottom: 15px; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 6px; text-align: center; }
    th { background-color: #f2f2f2; }
    tfoot td { font-weight: bold; }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border: none;">
      <tr>
          <td style="width: 70px; text-align: left; border: none;">
              <img src="{{ public_path('logos/icon.jpg') }}" style="width: 70px;" alt="Logo Kiri">
          </td>
          <td style="text-align: center; font-size: 18px; font-weight: bold; border: none;">
              Outbound Report
          </td>
          <td style="width: 70px; text-align: right; border: none;">
              <img src="{{ public_path('logos/icon2.jpg') }}" style="width: 70px;" alt="Logo Kanan">
          </td>
      </tr>
    </table>
  </div>

  <!-- Meta Info -->
  <div class="meta">
    Dicetak pada: <strong>{{ $printedAt }}</strong>
  </div>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th style="width: 40px" rowspan="2">No</th>
        <th colspan="3">Data dok pabean</th>
        <th colspan="2">Bukti Pengeluaran Barang / dok lain yang sejenis</th>
        <th rowspan="2">Pembeli/Penerima atau pemasok barang</th>
        <th rowspan="2">Nama Pemilik Barang</th>
        <th rowspan="2">Kode barang</th>
        <th rowspan="2">Nama barang</th>
        <th rowspan="2">Satuan barang</th>
        <th rowspan="2">Jumlah barang</th>
        <th rowspan="2">Nilai barang</th>
      </tr>
      <tr>
        <th>Jenis</th>
        <th>No. Daftar</th>
        <th>Tgl. Daftar</th>
        <th>No</th>
        <th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
      @forelse($checkouts as $index => $c)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $c->warehouse->name ?? '-' }}</td>
          <td>{{ $c->transaction_number ?? '-' }}</td>
          <td>{{ $c->date ? \Carbon\Carbon::parse($c->date)->format('Y-m-d') : '-' }}</td>
          <td>{{ $c->reference ?? '-' }}</td>
          <td>{{ $c->date_out ? \Carbon\Carbon::parse($c->date_in)->format('Y-m-d') : '-' }}</td>
          <td>{{ $c->receiver_name ?? '-' }}</td>
          <td>{{ $c->contact->name ?? '-' }}</td>
          <td>{{ $c->item->code ?? '-' }}</td>
          <td>{{ $c->item->name ?? '-' }}</td>
          <td>{{ $c->unit->code ?? '-' }}</td>
          {{-- <td>{{ $c->item->sum('quantity') ?? 0 }}</td> --}}
          <td>{{ $c->qty_out ?? '-'}}</td>
          <td>Rp. {{ number_format(($c->item->price_item ?? 0) * ($c->qty_out ?? 0), 0, ',', '.') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="12" style="text-align:center;">No data available</td>
        </tr>
      @endforelse
    </tbody>
  </table>

</body>
</html>
