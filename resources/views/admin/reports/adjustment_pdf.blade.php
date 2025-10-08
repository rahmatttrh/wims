<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mutasi Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 30px; }
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
              MUTASI REPORT
          </td>
          <td style="width: 70px; text-align: right; border: none;">
            <img src="{{ public_path('logos/bea_cukai.png') }}" style="width: 100px;" alt="Logo Kanan">
          </td>
      </tr>
    </table>
  </div>

  <table width="100%" style="margin-bottom: 15px; font-size: 12px; border: none;">
    <tr style="border: none;">
      <td style="text-align: left; border: none;">Location: <strong>{{ $warehouseName }}</strong></td>
    </tr>
    <tr style="border: none;">
      <td style="text-align: left; border: none;">
        Data period: 
        <strong>
          {{ $start_date ? \Carbon\Carbon::parse($start_date)->format('d/m/Y') : '-' }}
          s/d
          {{ $end_date ? \Carbon\Carbon::parse($end_date)->format('d/m/Y') : '-' }}
        </strong>
      </td>
      <td style="text-align: right; border: none;">
        Print Date: <strong>{{ $printedAt }}</strong>
      </td>
    </tr>
  </table>


  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th style="width: 40px">No</th>
        <th>Kode Barang</th>
        <th>Nama Barang</th>
        <th>Satuan Barang</th>
        <th>Jumlah Barang</th>
        <th>Saldo Awal</th>
        <th>Jumlah Pemasukan Barang</th>
        <th>Jumlah Pengeluaran Barang</th>
        <th>Penyesuaian (Adjustment)</th>
        <th>Saldo Akhir</th>
        <th>Hasil Pencacahan</th>
        <th>Jumlah Selisih</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      @php $no = 1; @endphp
      @forelse($adjustments as $c)
        @foreach($c->items as $ci)
          <tr>
            <td>{{ $no++ }}</td>
            {{-- <td>{{ $c->type_bc == null ? '-' : $c->type_bc->name }}</td> --}}
            <td>{{ $ci->item->code ?? '-' }}</td>
            <td>{{ $ci->item->name ?? '-' }}</td>
            <td>{{ $ci->unit->code ?? '-' }}</td>
            <td>{{ $ci->item->track_quantity ?? '-' }}</td>
            <td>{{ $ci->item->track_quantity ?? '-' }}</td>
            <td>{{ rtrim(rtrim(number_format($ci->checkinItem->quantity, 4, '.', ''), '0'), '.') }}</td>
            <td>{{ rtrim(rtrim(number_format($ci->checkoutItem->quantity, 4, '.', ''), '0'), '.') }}</td>
            <td>{{ rtrim(rtrim(number_format($ci->quantity, 4, '.', ''), '0'), '.') }}</td>
            <td>{{ $ci->pencacahan ?? '-' }}</td>
            <td>{{ $ci->saldo_akhir ?? '-' }}</td>
            <td>{{ $ci->selisih ?? '-' }}</td>
            <td>{{ $c->details ?? '-' }}</td>
          </tr>
        @endforeach
      @empty
        <tr>
          <td colspan="12" style="text-align:center;">No data available</td>
        </tr>
      @endforelse
    </tbody>
  </table>

</body>
</html>
