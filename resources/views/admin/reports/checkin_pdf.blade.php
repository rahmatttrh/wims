<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inbound Report</title>
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
              Inbound Report
          </td>
          <td style="width: 70px; text-align: right; border: none;">
              <img src="{{ public_path('logos/icon2.jpg') }}" style="width: 70px;" alt="Logo Kanan">
          </td>
      </tr>
  </table>
  
  
  </div>

  <!-- Judul -->
  {{-- <h2>Inbound Report</h2> --}}

  <!-- Meta Info -->
  <div class="meta">
    Dicetak pada: <strong>{{ $printedAt }}</strong>
  </div>

  <!-- Table -->
  <table>
    <thead>
      <tr>
        <th style="width: 20px;" rowspan="2">No</th>
        <th rowspan="2">Transaction Number</th>
        <th rowspan="2">Reference / No Aju</th>
        <th rowspan="2">Tanggal</th>
        <th rowspan="2">Jumlah Qty</th>
        <th rowspan="2">Contact</th>
        <th rowspan="2">Warehouse</th>
        <th>Item</th>
        <th>Weight</th>
        <th>Qty</th>
        {{-- <th>User</th>
        <th>Draft</th>
        <th>Deleted</th> --}}
      </tr>
      <tr>
        <th>Description</th>
        <th>Unit</th>
        <th>Per Item</th>
      </tr>
    </thead>
    <tbody>
      @forelse($checkins as $index => $c)
        <!-- Baris utama transaksi -->
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $c->transaction_number ?? '-' }}</td>
          <td>{{ $c->reference ?? '-' }}</td>
          <td>{{ $c->date ? \Carbon\Carbon::parse($c->date)->format('Y-m-d') : '-' }}</td>
          <td>{{ $c->items->sum('quantity') ?? 0 }}</td>
          <td>{{ $c->contact->name ?? '-' }}</td>
          <td>{{ $c->warehouse->name ?? '-' }}</td>
          <td colspan="3" style="text-align: center; font-weight: bold;">Packaging List</td>
        </tr>
    
        <!-- Detail item -->
        @if(isset($c->items) && count($c->items) > 0)
          @foreach($c->items as $item)
            <tr>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td>{{ $item->item->name ?? '-' }}</td>
              <td>{{ number_format($item->weight ?? 0, 2) ?? '-' }} {{ $settings['weight_unit'] ?? 'kg' }}</td>
              <td>{{ number_format($item->quantity ?? 0, 2) ?? '-' }} {{ $item->unit->code ?? '-' }}</td>
            </tr>
          @endforeach
        @else
          <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
          </tr>
        @endif
      @empty
        <tr>
          <td colspan="10" style="text-align:center;">No data available</td>
        </tr>
      @endforelse
    </tbody>
    
    {{-- <tfoot>
      <tr>
        <td colspan="5">Total Data: {{ $checkins->count() }}</td>
      </tr>
    </tfoot> --}}
  </table>

</body>
</html>
