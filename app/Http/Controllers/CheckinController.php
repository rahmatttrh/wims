<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
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

class CheckinController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        return Inertia::render('Checkin/Index', [
            'filters'  => $filters,
            'checkins' => new Collection(
                Checkin::with(['contact', 'warehouse', 'user'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Checkin/Form', [
            'contactbs'   => Contact::ofAccount()->get(),
            'contacts'   => TypeBc::where('code', 'bc1.6')->get(),
            // 'contacts'   => ['BC 16', 'BC 28'],
            'warehouses' => Warehouse::ofAccount()->active()->get(),
        ]);
    }

    public function store(CheckinRequest $request)
    {
         // dd($request->type_bc_id['name']);
         // dd($request->all());
        $data = $request->validated();
        $checkin = (new PrepareOrder($data, $request->file('attachments'), new Checkin()))->process()->save();
        event(new \App\Events\CheckinEvent($checkin, 'created'));


         $checkin->no_receive = $request->no_receive;
         $checkin->date_receive = $request->date_receive; 
         $checkin->type_bc_id = $request->type_bc_id;
         // tambahkan manual kalau belum keisi
         $checkin->save();
         
//   dd($checkin);
        if ((get_settings('auto_email') ?? null) && $checkin->contact->email) {
            $checkin->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username,email']);

            // Mail::to($checkin->contact->email)->cc(auth()->user()->email)->queue(new EmailCheckin($checkin));
        }

      //   dd($checkin);

        return redirect()->route('checkins.index')->with('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'created']));
    }

    public function show(Request $request, Checkin $checkin)
    {
        $checkin->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight,photo', 'contact', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

        return $request->json ? $checkin : Inertia::render('Checkin/Show', ['checkin' => $checkin]);
    }

    public function edit(Checkin $checkin)
    {
        $this->authorize('update', $checkin);

        return Inertia::render('Checkin/Form', [
            'contacts'   => Contact::ofAccount()->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'edit'       => $checkin->load(['items.variations', 'items.item.variations', 'attachments']),
        ]);
    }

    public function update(CheckinRequest $request, Checkin $checkin)
    {
        $this->authorize('update', $checkin);
        $data = $request->validated();
        $original = $checkin->load(['items.item', 'items.unit', 'items.variations'])->replicate();
        $checkin = (new PrepareOrder($data, $request->file('attachments'), $checkin))->process()->save();
        event(new \App\Events\CheckinEvent($checkin, 'updated', $original));
        session()->flash('message', __choice('action_text', ['record' => 'Checkin', 'action' => 'updated']));

        return $request->listing == 'yes' ? redirect()->route('checkins.index') : back();
    }

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
