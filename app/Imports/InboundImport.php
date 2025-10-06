<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use App\Models\Checkin;
use App\Models\CheckinItem;
use App\Models\TypeBc;
use App\Models\Contact;
use App\Models\Warehouse;
use App\Models\Item;
use App\Models\Unit;

class InboundImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
      foreach ($rows as $key => $row) {
         if($row->filter()->isNotEmpty()){

            $currentInbound = Checkin::where('reference', $row['no_aju'])->first();

            // if ($currentInbound != null) {
            //    $inbound = $currentInbound;
            // } else {
               $bcType = TypeBc::where('name', $row['jenis'])->first();
               $contact = Contact::where('name', $row['contact'])->first();
               $warehouse = Warehouse::where('name', $row['warehouse'])->first();
               // dd($row['no_aju']);
               // dd($bcType);
               $inbound = Checkin::create([
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



            CheckinItem::create([
               'checkin_id' => $inbound->id,
               'item_id' => $item->id,
               'sender' => $row['pengirim'],
               'owner' => $row['pemilik'],
               'quantity' => $row['quantity'],
               'unit_id' => $unit->id,

               'warehouse_id' => $inbound->warehouse_id,
               'account_id' => $inbound->account_id
            ]);

            

         }
      }
    }
}
