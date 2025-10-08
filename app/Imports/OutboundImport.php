<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\TypeBc;
use App\Models\Contact;
use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Unit;

class OutboundImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
      foreach ($rows as $key => $row) {
         if($row->filter()->isNotEmpty()){

            $currentOutbound = Checkout::where('reference', $row['no_aju'])->first();

            // if ($currentInbound != null) {
            //    $inbound = $currentInbound;
            // } else {
               $bcType = TypeBc::where('name', $row['jenis'])->first();
               $contact = Contact::where('name', $row['contact'])->first();
               $warehouse = Warehouse::where('name', $row['warehouse'])->first();
               // dd($row['no_aju']);
               // dd($bcType);
               $outbound = Checkout::create([
                  'type_bc_id' => $bcType->id,
                  'reference' => $row['no_aju'],
                  'date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_aju'])->format('Y-m-d'),
                  'no_receive' => $row['no_penerimaan'],
                  'date_receive' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['tanggal_penerimaan'])->format('Y-m-d'),
                  'contact_id' => $contact->id,
                  'warehouse_id' => $warehouse->id
               ]);
            // }


            // dd('ok');

            
            $item = Item::where('name', $row['item'])->first();
            $unit = Unit::where('name', $row['unit'])->first();



            CheckoutItem::create([
               'checkout_id' => $outbound->id,
               'item_id' => $item->id,
               'buyer' => $row['pembeli'],
               'owner' => $row['pemilik'],
               'quantity' => $row['quantity'],
               'unit_id' => $unit->id,
               'value' => $row['nilai'],

               'warehouse_id' => $outbound->warehouse_id,
               'account_id' => $outbound->account_id
            ]);

            

         }
      }
    }
}
