<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Unit;
use Inertia\Inertia;
use App\Models\Category;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\Collection;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('search', 'trashed', 'category');

        return Inertia::render('Item/Index', [
            'filters'    => $filters,
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'items'      => new Collection(
                Item::with(['categories:id,code,name', 'stock.warehouse'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Item/Form', [
            'units'      => Unit::ofAccount()->base()->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'categories' => Category::ofAccount()->with('children')->get(),
        ]);
    }

    public function store(ItemRequest $request)
    {
        $data = $request->validated();
        Item::create($data)->addVariations()->saveRelations($data);

        return redirect()->route('items.index')->with('message', __choice('action_text', ['record' => 'Item', 'action' => 'created']));
    }

    public function show(Request $request, Item $item)
    {
        $item->load(['categories:id,code,name', 'allStock.warehouse:id,code,name']);
        $item->setRelation('stock', $item->allStock->groupBy('warehouse_id'));

        return $request->json ? $item : Inertia::render('Item/Show', ['item' => $item]);
    }

    public function edit(Item $item)
    {
        $item->load(['categories:id,code,name', 'stock']);

        return Inertia::render('Item/Form', [
            'edit'       => $item,
            'units'      => Unit::ofAccount()->base()->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'categories' => Category::ofAccount()->with('children')->get(),
        ]);
    }

    public function update(ItemRequest $request, Item $item)
    {
        $data = $request->validated();
        $item->update($data);
        $item->addVariations()->saveRelations($data);
        session()->flash('message', __choice('action_text', ['record' => 'Item', 'action' => 'updated']));

        return $request->listing == 'yes' ? redirect()->route('items.index') : back();
    }

    public function destroy(Item $item)
    {
        if ($item->del()) {
            return redirect()->route('items.index')->with('message', __choice('action_text', ['record' => 'Item', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function restore(Item $item)
    {
        $item->restore();

        return back()->with('message', __choice('action_text', ['record' => 'Item', 'action' => 'restored']));
    }

    public function destroyPermanently(Item $item)
    {
        if ($item->delP()) {
            return redirect()->route('items.index')->with('message', __choice('action_text', ['record' => 'Item', 'action' => 'permanently deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function destroyPhoto(Item $item)
    {
        if (Storage::disk('assets')->delete($item->photo)) {
            $item->update(['photo' => null]);

            return back()->with('message', __choice('action_text', ['record' => 'Item photo', 'action' => 'deleted']));
        }

        return back()->with('error', __('The record can not be deleted.'));
    }

    public function trail(Item $item)
    {
        $item->load('stockTrails');

        // Ambil data stock trail + relasi
        $trails = $item->stockTrails()
            ->with(['warehouse', 'unit', 'variation'])
            ->orderByDesc('id')
            ->paginate()
            ;

        // Format tanggal menggunakan Carbon (locale Indonesia)
        $trails->getCollection()->transform(function ($trail) {
            $trail->formatted_created_at = \Carbon\Carbon::parse($trail->created_at)
                ->locale('id')
                ->translatedFormat('d F Y H:i');
            return $trail;
        });

        return Inertia::render('Item/Trail', [
            'item'   => $item->only('id', 'code', 'name'),
            'trails' => new \App\Http\Resources\Collection($trails),
        ]);
    }

}
