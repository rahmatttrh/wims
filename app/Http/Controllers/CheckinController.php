<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Imports\InboundImport;
use App\Models\Checkin;
use App\Models\Contact;
use App\Models\Warehouse;
use App\Models\TypeBc;
use App\Mail\EmailCheckin;
use Illuminate\Http\Request;
use App\Actions\Tec\PrepareOrder;
use App\Http\Resources\Collection;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CheckinRequest;
use Maatwebsite\Excel\Facades\Excel;
// use App\Models\CategoryLogistics;

class CheckinController extends Controller
{
    // public function index(Request $request)
    // {
    //     $filters = $request->all('draft', 'search', 'trashed');

    //     return Inertia::render('Checkin/Index', [
    //         'filters'  => $filters,
    //         'checkins' => new Collection(
    //             Checkin::with(['contact', 'warehouse', 'user'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
    //         ),
    //     ]);
    // }

    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        $checkins = Checkin::with(['contact', 'warehouse', 'user', 'items.item'])
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate()
            ->withQueryString();

        $checkins->getCollection()->transform(function ($item) {
            if ($item->date_receive) {
                $receive = Carbon::parse($item->date_receive);
                $expired = $receive->copy()->addMonths(36);
                $diffMonths = $receive->diffInMonths(now());
            
                // Status expired
                $item->date_expired = $expired->format('Y-m-d');
                $item->status_expired = $diffMonths >= 33
                    ? 'expired'
                    : ($diffMonths >= 6
                        ? 'warning'
                        : 'normal');
            
                // Hitung lama waktu (tahun/bulan/hari)
                $diff = $receive->diff(now());
            
                $parts = [];
                if ($diff->y > 0) $parts[] = "{$diff->y} Tahun";
                if ($diff->m > 0) $parts[] = "{$diff->m} Bulan";
                if ($diff->d > 0 || empty($parts)) $parts[] = "{$diff->d} Hari";
            
                $item->lama_total = implode(', ', $parts);
            } else {
                $item->date_expired = '-';
                $item->status_expired = 'normal';
                $item->lama_total = '-';
            }            

            // Ambil info tambahan dari item pertama (kalau ada)
            $firstItem = $item->items->first();
            $item->sender = $firstItem->sender ?? '-';
            $item->owner = $firstItem->owner ?? '-';
            $item->item_name = $firstItem?->item?->name ?? '-';
            $item->item_code = $firstItem?->item?->code ?? '-';

            return $item;
        });

        return Inertia::render('Checkin/Index', [
            'filters'  => $filters,
            'checkins' => new \App\Http\Resources\Collection($checkins),
        ]);
    }

    public function import()
    {
        return view('import.inbounds');
    }

    public function importStore(Request $req)
    {
      // dd('import inbound');
      $file = $req->file('file');
      $fileName = $file->getClientOriginalName();
      $file->move('InboundData', $fileName);
      Excel::import(new InboundImport, public_path('InboundData/' . $fileName));
      
      return redirect()->route('checkins.index')->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'imported']));
      //   return view('import.inbounds');
    }

    public function create()
    {
        return Inertia::render('Checkin/Form', [
            'contactbs'   => Contact::ofAccount()->get(),
            // 'contacts'   => TypeBc::where('code', 'bc1.6', 'bc2.7', 'bc4.0')->get(),
            // 'category_logistics'   => CategoryLogistics::where('code', '001')->get(),
            // 'contacts'   => ['BC 16', 'BC 28'],
            // 'contacts'  => TypeBc::whereIn('code', ['bc1.6', 'bc2.7', 'bc3.3', 'bc4.0', 'bc2.6.2'])
            'contacts'  => TypeBc::whereIn('code', ['bc1.6', 'bc2.7',  'bc4.0'])
                ->orderBy('code', 'asc')
                ->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
        ]);
    }

    // public function store(CheckinRequest $request)
    // {
    //     // dd($request->type_bc_id['name']);
    //     // dd($request->all());
    //     $data = $request->validated();
    //     $checkin = (new PrepareOrder($data, $request->file('attachments'), new Checkin()))->process()->save();
    //     event(new \App\Events\CheckinEvent($checkin, 'created'));


    //     $checkin->no_receive = $request->no_receive;
    //     $checkin->date_receive = $request->date_receive;
    //     $checkin->type_bc_id = $request->type_bc_id;
    //     //  $checkin->category_logistic_id = $request->category_logistic_id;
    //     // tambahkan manual kalau belum keisi
    //     $checkin->save();

    //     //   dd($checkin);
    //     if ((get_settings('auto_email') ?? null) && $checkin->contact->email) {
    //         $checkin->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username,email']);

    //         // Mail::to($checkin->contact->email)->cc(auth()->user()->email)->queue(new EmailCheckin($checkin));
    //     }

    //     //   dd($checkin);

    //     return redirect()->route('checkins.index')->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'created']));
    // }

    public function store(CheckinRequest $request)
    {
        $data = $request->validated();

        $checkin = (new PrepareOrder($data, $request->file('attachments'), new Checkin()))
            ->process()
            ->save();

        event(new \App\Events\CheckinEvent($checkin, 'created'));

        // isi data tambahan
        $checkin->no_receive = $request->no_receive;
        $checkin->date_receive = $request->date_receive;
        $checkin->type_bc_id = $request->type_bc_id;

        // ✅ Tambahkan logika otomatis date_expired +6 bulan
        if ($request->date_receive) {
            $checkin->date_expired = Carbon::parse($request->date_receive)->addMonths(33)->format('Y-m-d');
        }

        $checkin->save();

        // kirim email jika fitur aktif
        if ((get_settings('auto_email') ?? null) && $checkin->contact->email) {
            $checkin->load([
                'items.variations',
                'items.item:id,code,name,track_quantity,track_weight',
                'contact',
                'warehouse',
                'items.unit:id,code,name',
                'user:id,name:username,email'
            ]);

            // Mail::to($checkin->contact->email)
            //     ->cc(auth()->user()->email)
            //     ->queue(new EmailCheckin($checkin));
        }

        return redirect()
            ->route('checkins.index')
            ->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'created']));
    }

    public function update(CheckinRequest $request, Checkin $checkin)
    {
        $this->authorize('update', $checkin);

        $data = $request->validated();
        $original = $checkin->load(['items.item', 'items.unit', 'items.variations'])->replicate();

        $checkin = (new PrepareOrder($data, $request->file('attachments'), $checkin))
            ->process()
            ->save();

        // ✅ Update otomatis date_expired +6 bulan juga di update
        if ($request->date_receive) {
            $checkin->date_receive = $request->date_receive;
            $checkin->date_expired = Carbon::parse($request->date_receive)->addMonths(6)->format('Y-m-d');
            $checkin->save();
        }

        event(new \App\Events\CheckinEvent($checkin, 'updated', $original));
        session()->flash('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'updated']));

        return $request->listing == 'yes'
            ? redirect()->route('checkins.index')
            : back();
    }



    public function show(Request $request, Checkin $checkin)
    {
        $checkin->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight,photo', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

        $checkin->formatted_created_at = Carbon::parse($checkin->created_at)
            ->locale('id')
            ->translatedFormat('d F Y H:i');

        return $request->json ? $checkin : Inertia::render('Checkin/Show', ['checkin' => $checkin]);
    }

    public function edit(Checkin $checkin)
    {
        $this->authorize('update', $checkin);

        return Inertia::render('Checkin/Form', [
            'contactbs'   => Contact::ofAccount()->get(),
            'contacts'   => TypeBc::where('code', 'bc1.6')->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'edit'       => $checkin->load(['items.variations', 'items.item.variations', 'attachments']),
        ]);
    }

    // public function update(CheckinRequest $request, Checkin $checkin)
    // {
    //     $this->authorize('update', $checkin);
    //     $data = $request->validated();
    //     $original = $checkin->load(['items.item', 'items.unit', 'items.variations'])->replicate();
    //     $checkin = (new PrepareOrder($data, $request->file('attachments'), $checkin))->process()->save();
    //     event(new \App\Events\CheckinEvent($checkin, 'updated', $original));
    //     session()->flash('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'updated']));

    //     return $request->listing == 'yes' ? redirect()->route('checkins.index') : back();
    // }

    public function destroy(Checkin $checkin)
    {
        $checkin->load(['items.item', 'items.unit', 'items.variations']);
        if ($checkin->del()) {
            event(new \App\Events\CheckinEvent($checkin, 'deleted'));

            return redirect()->route('checkins.index')->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'deleted']));
        }

        return redirect()->route('checkins.index')->with('error', __('The record can not be deleted.'));
    }

    public function restore(Checkin $checkin)
    {
        $checkin->restore();
        $checkin->items->each->restore();
        event(new \App\Events\CheckinEvent($checkin, 'restored'));

        return back()->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'restored']));
    }

    public function destroyPermanently(Checkin $checkin)
    {
        $deleted = $checkin->deleted_at;
        $checkin->load(['items.item', 'items.unit', 'items.variations']);
        if ($checkin->delP()) {
            if (! $deleted) {
                event(new \App\Events\CheckinEvent($checkin, 'deleted'));
            }

            return redirect()->route('checkins.index')->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'permanently deleted']));
        }

        return redirect()->route('checkins.index')->with('error', __('The record can not be deleted.'));
    }
}
