<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\Collection;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('search', 'trashed');

        return Inertia::render('Unit/Index', [
            'filters' => $filters,
            'units'   => new Collection(
                Unit::filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Unit/Form', ['base_units' => Unit::base()->get()]);
    }

    public function store(UnitRequest $request)
    {
        Unit::create($request->validated());

        return redirect()->route('units.index')->with('message', __choice('action_text', ['record' => 'Unit', 'action' => 'created']));
    }

    public function edit(Unit $unit)
    {
        return Inertia::render('Unit/Form', [
            'base_units' => Unit::base()->get(),
            'edit'       => $unit,
        ]);
    }

    public function update(UnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

        return back()->with('message', __choice('action_text', ['record' => 'Unit', 'action' => 'updated']));
    }

    public function destroy(Unit $unit)
    {
        if ($unit->del()) {
            return redirect()->route('units.index')->with('message', __choice('action_text', ['record' => 'Unit', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function restore(Unit $unit)
    {
        $unit->restore();

        return back()->with('message', __choice('action_text', ['record' => 'Unit', 'action' => 'restored']));
    }

    public function destroyPermanently(Unit $unit)
    {
        if ($unit->delP()) {
            return redirect()->route('units.index')->with('message', __choice('action_text', ['record' => 'Unit', 'action' => 'permanently deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }
}
