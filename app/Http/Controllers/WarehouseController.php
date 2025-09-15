<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Http\Resources\Collection;
use App\Http\Requests\WarehouseRequest;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('search', 'trashed');

        return Inertia::render('Warehouse/Index', [
            'filters'    => $filters,
            'warehouses' => new Collection(
                Warehouse::filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Warehouse/Form');
    }

    public function store(WarehouseRequest $request)
    {
        Warehouse::create($request->validated());

        return redirect()->route('warehouses.index')->with('message', __choice('action_text', ['record' => 'Warehouse', 'action' => 'created']));
    }

    public function edit(Warehouse $warehouse)
    {
        return Inertia::render('Warehouse/Form', ['edit' => $warehouse]);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->update($request->validated());

        return back()->with('message', __choice('action_text', ['record' => 'Warehouse', 'action' => 'updated']));
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->del()) {
            return redirect()->route('warehouses.index')->with('message', __choice('action_text', ['record' => 'Warehouse', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function restore(Warehouse $warehouse)
    {
        $warehouse->restore();
        $warehouse->stock()->restore();

        return back()->with('message', __choice('action_text', ['record' => 'Warehouse', 'action' => 'restored']));
    }

    public function destroyPermanently(Warehouse $warehouse)
    {
        if ($warehouse->delP()) {
            return redirect()->route('warehouses.index')->with('message', __choice('action_text', ['record' => 'Warehouse', 'action' => 'permanently deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }
}
