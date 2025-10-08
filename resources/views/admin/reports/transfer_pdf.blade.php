<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Transfer Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 30px; }
    .header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .logo { width: 70px; }
    h2 { text-align: center; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 6px; text-align: center; }
    th { background-color: #f2f2f2; }
  </style>
</head>
<body>

  <div class="header">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border: none;">
      <tr>
        <td style="width: 70px; text-align: left; border: none;">
          <img src="{{ public_path('logos/icon.jpg') }}" style="width: 70px;">
        </td>
        <td style="text-align: center; font-size: 18px; font-weight: bold; border: none;">
          MUTASI REPORT
        </td>
        <td style="width: 70px; text-align: right; border: none;">
          <img src="{{ public_path('logos/bea_cukai.png') }}" style="width: 100px;">
        </td>
      </tr>
    </table>
  </div>

  <table width="100%" style="margin-bottom: 15px; font-size: 12px; border: none;">
    <tr style="border: none;">
      <td style="text-align: left; border: none;">Location: <strong>{{ $warehouseName }}</strong></td>
      <td style="text-align: right; border: none;">Print Date: <strong>{{ $printedAt }}</strong></td>
    </tr>
    <tr style="border: none;">
      <td style="text-align: left; border: none;">
        Data period: <strong>{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</strong>
      </td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Kode Barang</th>
        <th>Nama Barang</th>
        <th>Satuan Barang</th>
        <th>Jumlah Barang</th>
        <th>Saldo Awal</th>
        <th>Jumlah Pemasukan Barang</th>
        <th>Jumlah Pengeluaran Barang</th>
        <th>Penyesuaian</th>
        <th>Saldo Akhir</th>
        <th>Hasil Pencacahan</th>
        <th>Jumlah Selisih</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      @forelse($reportData as $i => $d)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $d['code'] }}</td>
          <td>{{ $d['name'] }}</td>
          <td>{{ $d['unit'] }}</td>
          <td>{{ rtrim(rtrim(number_format($d['jumlah_barang'], 4, '.', ''), '0'), '.') }}</td>
          <td>{{ rtrim(rtrim(number_format($d['saldo_awal'], 4, '.', ''), '0'), '.') }}</td>
          <td>{{ rtrim(rtrim(number_format($d['pemasukan'], 4, '.', ''), '0'), '.') }}</td>
          <td>{{ rtrim(rtrim(number_format($d['pengeluaran'], 4, '.', ''), '0'), '.') }}</td>
          <td>{{ rtrim(rtrim(number_format($d['adjustment'], 4, '.', ''), '0'), '.') }}</td>
          <td>{{ rtrim(rtrim(number_format($d['saldo_akhir'], 4, '.', ''), '0'), '.') }}</td>
          <td>0</td>
          <td>0</td>
          <td>{{ $d['keterangan'] }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="10" style="text-align:center;">No data available</td>
        </tr>
      @endforelse
    </tbody>
  </table>

</body>
</html>
