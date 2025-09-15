<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Warehouse;
use App\Models\Adjustment;
use Illuminate\Http\Request;
use App\Mail\EmailAdjustment;
use App\Actions\Tec\PrepareOrder;
use App\Http\Resources\Collection;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\AdjustmentRequest;

class AdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        return Inertia::render('Adjustment/Index', [
            'filters'     => $filters,
            'adjustments' => new Collection(
                Adjustment::with(['warehouse', 'user'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Adjustment/Form', [
            'warehouses' => Warehouse::ofAccount()->active()->get(),
        ]);
    }

    public function store(AdjustmentRequest $request)
    {
        $data = $request->validated();
        $adjustment = (new PrepareOrder($data, $request->file('attachments'), new Adjustment()))->process()->save();
        event(new \App\Events\AdjustmentEvent($adjustment, 'created'));

        if ((get_settings('auto_email') ?? null) && $adjustment->warehouse->email) {
            $adjustment->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username']);

            Mail::to($adjustment->warehouse->email)->cc(auth()->user()->email)->queue(new EmailAdjustment($adjustment));
        }

        return redirect()->route('adjustments.index')->with('message', __choice('action_text', ['record' => 'Adjustment', 'action' => 'created']));
    }

    public function show(Request $request, Adjustment $adjustment)
    {
        $adjustment->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight', 'warehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

        return $request->json ? $adjustment : Inertia::render('Adjustment/Show', ['adjustment' => $adjustment]);
    }

    public function edit(Adjustment $adjustment)
    {
        $this->authorize('update', $adjustment);

        return Inertia::render('Adjustment/Form', [
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'edit'       => $adjustment->load(['items.variations', 'items.item.variations', 'attachments']),
        ]);
    }

    public function update(AdjustmentRequest $request, Adjustment $adjustment)
    {
        $this->authorize('update', $adjustment);
        $data = $request->validated();
        $original = $adjustment->load(['items.item', 'items.unit', 'items.variations'])->replicate();
        $adjustment = (new PrepareOrder($data, $request->file('attachments'), $adjustment))->process()->save();
        event(new \App\Events\AdjustmentEvent($adjustment, 'updated', $original));
        session()->flash('message', __choice('action_text', ['record' => 'Adjustment', 'action' => 'updated']));

        return $request->listing == 'yes' ? redirect()->route('adjustments.index') : back();
    }

    public function destroy(Adjustment $adjustment)
    {
        $adjustment->load(['items.item', 'items.unit', 'items.variations']);
        if ($adjustment->del()) {
            event(new \App\Events\AdjustmentEvent($adjustment, 'deleted'));

            return redirect()->route('adjustments.index')->with('message', __choice('action_text', ['record' => 'Adjustment', 'action' => 'deleted']));
        }

        return redirect()->route('adjustments.index')->with('error', __('The record can not be deleted.'));
    }

    public function restore(Adjustment $adjustment)
    {
        $adjustment->restore();
        $adjustment->items->each->restore();
        event(new \App\Events\AdjustmentEvent($adjustment, 'restored'));

        return back()->with('message', __choice('action_text', ['record' => 'Adjustment', 'action' => 'restored']));
    }

    public function destroyPermanently(Adjustment $adjustment)
    {
        $deleted = $adjustment->deleted_at;
        $adjustment->load(['items.item', 'items.unit', 'items.variations']);
        if ($adjustment->delP()) {
            if (! $deleted) {
                event(new \App\Events\AdjustmentEvent($adjustment, 'deleted'));
            }

            return redirect()->route('adjustments.index')->with('message', __choice('action_text', ['record' => 'Adjustment', 'action' => 'permanently deleted']));
        }

        return redirect()->route('adjustments.index')->with('error', __('The record can not be deleted.'));
    }
}
