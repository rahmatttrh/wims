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
    th, td { border: 1px solid #000; padding: 6px; text-align: left; }
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
        <th style="width: 40px;">No</th>
        <th>Reference</th>
        <th>Date</th>
        <th>Contact</th>
        <th>Warehouse</th>
        <th>User</th>
        <th>Draft</th>
        <th>Deleted</th>
      </tr>
    </thead>
    <tbody>
      @forelse($checkouts as $index => $c)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $c->reference }}</td>
          <td>{{ $c->date ? \Carbon\Carbon::parse($c->date)->format('d/m/Y') : '-' }}</td>
          <td>{{ $c->contact->name ?? '-' }}</td>
          <td>{{ $c->warehouse->name ?? '-' }}</td>
          <td>{{ $c->user->name ?? '-' }}</td>
          <td>{{ $c->draft == 1 ? 'Yes' : 'No' }}</td>
          <td>{{ $c->deleted_at ? 'Yes' : 'No' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" style="text-align:center;">No data available</td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="8">Total Data: {{ $checkouts->count() }}</td>
      </tr>
    </tfoot>
  </table>

</body>
</html>
