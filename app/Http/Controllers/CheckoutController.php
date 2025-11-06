<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Contact;
use App\Models\Checkout;
use App\Imports\OutboundImport;
use App\Models\TypeBc;
use App\Models\Warehouse;
use App\Mail\EmailCheckout;
use Illuminate\Http\Request;
use App\Actions\Tec\PrepareOrder;
use App\Http\Resources\Collection;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CheckoutRequest;
use Maatwebsite\Excel\Facades\Excel;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        // $checkouts = Checkout::with(['contact', 'warehouse', 'user', 'typeBc'])
        //     ->filter($filters)
        //     ->orderByDesc('id')
        //     ->paginate()
        //     ->withQueryString();

        // // Transform data agar menambahkan type_bc_name dan type_bc_code
        // $checkouts->getCollection()->transform(function ($item) {
        //     $item->type_bc_name = $item->typeBc->name ?? '-';
        //     $item->type_bc_code = $item->typeBc->code ?? '-';
        //     return $item;
        // });

        $checkouts = Checkout::with(['contact', 'warehouse', 'user', 'type_bc'])
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate()
            ->withQueryString();

        // Transform data agar menambahkan type_bc_name dan type_bc_code
        $checkouts->getCollection()->transform(function ($item) {
            $item->type_bc_name = $item->type_bc->name ?? '-';
            $item->type_bc_code = $item->type_bc->code ?? '-';
            return $item;
        });

        // return Inertia::render('Checkout/Index', [
        //     'filters'   => $filters,
        //     'checkouts' => new Collection(
        //         Checkout::with(['contact', 'warehouse', 'user', 'type_bc'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
        //     ),
        // ]);

        return Inertia::render('Checkout/Index', [
            'filters'   => $filters,
            'checkouts' => new Collection($checkouts),
        ]);
    }

    public function import()
    {
        return view('import.outbounds');
    }

    public function importStore(Request $req)
    {
        $file = $req->file('file');
        $fileName = $file->getClientOriginalName();
        $file->move('InboundData', $fileName);
        Excel::import(new OutboundImport, public_path('OutboundData/' . $fileName));

        return redirect()->route('checkouts.index')->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'imported']));
    }

    public function create()
    {
        return Inertia::render('Checkout/Form', [

            // 'contacts'   => Contact::ofAccount()->get(),
            // 'contacts'   => ['BC 16', 'BC 28'],
            // 'contactbs'   => Contact::ofAccount()->get(),
            'contactbs'   => Contact::ofAccount()->get(),
            // 'contacts'   => TypeBc::where('code', '!=', 'bc1.6', 'bc4.0')->get(),
            // 'contacts'  => TypeBc::whereIn('code', ['bc2.7', 'bc2.8', 'bc3.0', 'bc4.1', 'bc2.6.1', 'p3bet'])
            'contacts'  => TypeBc::whereIn('code', ['bc2.7', 'bc2.8', 'bc3.0', 'bc3.3', 'bc4.1'])
                ->orderBy('code', 'asc')
                ->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
        ]);
    }

    public function store(CheckoutRequest $request)
    {
        // dd($request->all());
        $data = $request->validated();

        $checkout = (new PrepareOrder($data, $request->file('attachments'), new Checkout()))->process()->save();
        event(new \App\Events\CheckoutEvent($checkout, 'created'));

        // Tambah Manual
        $checkout->no_receive = $request->no_receive;
        $checkout->date_receive = $request->date_receive;
        $checkout->type_bc_id = $request->type_bc_id;
        $checkout->save();

        // ✅ Tandai data inbound terkait (hidden)
        $relatedCheckin = \App\Models\Checkin::where('no_receive', $checkout->no_receive)->first();
        if ($relatedCheckin) {
            $relatedCheckin->update(['status' => 'outbound']);
        }

        // ✅ Flash alert agar muncul di halaman checkin
        session()->flash('alert', [
            'type' => 'info',
            'message' => 'Barang dengan nomor aju ' . $checkout->reference . ' telah keluar dari gudang.',
        ]);

        if ((get_settings('auto_email') ?? null) && $checkout->contact->email) {
            $checkout->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight,photo', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

            Mail::to($checkout->contact->email)->cc(auth()->user()->email)->queue(new EmailCheckout($checkout));
        }

        return redirect()->route('checkouts.index')->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'created']));
    }

    public function show(Request $request, Checkout $checkout)
    {
        $checkout->load([
            'items.variations',
            'items.item:id,code,name,track_quantity,track_weight,photo',
            'contact',
            'warehouse',
            'items.unit:id,code,name',
            'user:id,name,username',
            'attachments'
        ]);

        // lokal Indonesia
        $checkout->formatted_created_at = Carbon::parse($checkout->created_at)
            ->locale('id')
            ->translatedFormat('d F Y H:i');

        // Default gudang kalau kosong (ambil gudang pertama aktif)
        // if (!$checkout->warehouse_id) {
        //     $checkout->warehouse = \App\Models\Warehouse::ofAccount()->active()->first();
        // }

        return $request->json
            ? $checkout
            : Inertia::render('Checkout/Show', [
                'checkout' => $checkout,
            ]);
    }

    public function edit(Checkout $checkout)
    {
        $this->authorize('update', $checkout);

        return Inertia::render('Checkout/Form', [
            'contactbs'   => Contact::ofAccount()->get(),
            'contacts'   => TypeBc::where('code', '!=', 'bc1.6')->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'edit'       => $checkout->load(['items.variations', 'items.item.variations', 'attachments']),
        ]);
    }

    public function update(CheckoutRequest $request, Checkout $checkout)
    {
        $this->authorize('update', $checkout);
        $data = $request->validated();
        $original = $checkout->load(['items.item', 'items.unit', 'items.variations'])->replicate();
        $checkout = (new PrepareOrder($data, $request->file('attachments'), $checkout))->process()->save();
        event(new \App\Events\CheckoutEvent($checkout, 'updated', $original));
        session()->flash('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'updated']));

        return $request->listing == 'yes' ? redirect()->route('checkouts.index') : back();
    }

    public function destroy(Checkout $checkout)
    {
        $checkout->load(['items.item', 'items.unit', 'items.variations']);
        if ($checkout->del()) {
            event(new \App\Events\CheckoutEvent($checkout, 'deleted'));

            return redirect()->route('checkouts.index')->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'deleted']));
        }

        return redirect()->route('checkouts.index')->with('error', __('The record can not be deleted.'));
    }

    public function restore(Checkout $checkout)
    {
        $checkout->restore();
        $checkout->items->each->restore();
        event(new \App\Events\CheckoutEvent($checkout, 'restored'));

        return back()->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'restored']));
    }

    public function destroyPermanently(Checkout $checkout)
    {
        $deleted = $checkout->deleted_at;
        $checkout->load(['items.item', 'items.unit', 'items.variations']);
        if ($checkout->delP()) {
            if (! $deleted) {
                event(new \App\Events\CheckoutEvent($checkout, 'deleted'));
            }

            return redirect()->route('checkouts.index')->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'permanently deleted']));
        }

        return redirect()->route('checkouts.index')->with('error', __('The record can not be deleted.'));
    }
}
