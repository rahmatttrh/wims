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

        return Inertia::render('Checkout/Index', [
            'filters'   => $filters,
            'checkouts' => new Collection(
                Checkout::with(['contact', 'warehouse', 'user'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function import()
    {
        return view('import.outbounds');
    }

    public function importStore(Request $req)
    {
      // dd('import inbound');
      $file = $req->file('file');
      $fileName = $file->getClientOriginalName();
      $file->move('InboundData', $fileName);
      Excel::import(new OutboundImport, public_path('OutboundData/' . $fileName));
      
      return redirect()->route('checkouts.index')->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'imported']));
      //   return view('import.inbounds');
    }

    public function create()
    {
        return Inertia::render('Checkout/Form', [
         
            // 'contacts'   => Contact::ofAccount()->get(),
            // 'contacts'   => ['BC 16', 'BC 28'],
            // 'contactbs'   => Contact::ofAccount()->get(),
            'contactbs'   => Contact::ofAccount()->get(),
            // 'contacts'   => TypeBc::where('code', '!=', 'bc1.6', 'bc4.0')->get(),
            'contacts'  => TypeBc::whereIn('code', ['bc2.7', 'bc2.8', 'bc3.0', 'bc4.1', 'bc2.6.1', 'p3bet'])
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

        $checkout->no_receive = $request->no_receive;
        $checkout->date_receive = $request->date_receive; 
        $checkout->type_bc_id = $request->type_bc_id;
        // tambahkan manual kalau belum keisi
        $checkout->save();

        if ((get_settings('auto_email') ?? null) && $checkout->contact->email) {
            $checkout->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight,photo', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

            Mail::to($checkout->contact->email)->cc(auth()->user()->email)->queue(new EmailCheckout($checkout));
        }

        return redirect()->route('checkouts.index')->with('message', __choice('action_text', ['record' => 'Checkout', 'action' => 'created']));
    }

    // public function show(Request $request, Checkout $checkout)
    // {
    //     $checkout->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight,photo', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

    //     return $request->json ? $checkout : Inertia::render('Checkout/Show', ['checkout' => $checkout]);
    // }

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
