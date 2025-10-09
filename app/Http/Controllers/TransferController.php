<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Mail\EmailTransfer;
use Illuminate\Http\Request;
use App\Actions\Tec\PrepareOrder;
use App\Http\Resources\Collection;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\TransferRequest;
use App\Models\Item;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        return Inertia::render('Transfer/Index', [
            'filters'   => $filters,
            'transfers' => new Collection(
                Transfer::with(['fromWarehouse', 'toWarehouse', 'user'])->filter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function create()
    {
        return Inertia::render('Transfer/Form', [
            'warehouses' => Warehouse::ofAccount()->active()->get(),
        ]);
    }

    public function store(TransferRequest $request)
    {
        $data = $request->validated();
        $transfer = (new PrepareOrder($data, $request->file('attachments'), new Transfer()))->process()->save();
        event(new \App\Events\TransferEvent($transfer, 'created'));

        if ((get_settings('auto_email') ?? null) && $transfer->toWarehouse->email) {
            $transfer->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight', 'fromWarehouse', 'toWarehouse', 'items.unit:id,code,name', 'user:id,name:username']);

            Mail::to($transfer->toWarehouse->email)->cc(auth()->user()->email)->queue(new EmailTransfer($transfer));
        }

        return redirect()->route('transfers.index')->with('message', __choice('action_text', ['record' => 'Transfer', 'action' => 'created']));
    }

    public function show(Request $request, Transfer $transfer)
    {
        $transfer->load(['items.variations', 'items.item:id,code,name,track_quantity,track_weight', 'fromWarehouse', 'toWarehouse', 'items.unit:id,code,name', 'user:id,name:username', 'attachments']);

        $transfer->formatted_created_at = Carbon::parse($transfer->created_at)
            ->locale('id')
            ->translatedFormat('d F Y H:i');

        return $request->json ? $transfer : Inertia::render('Transfer/Show', ['transfer' => $transfer]);
    }

    // public function show(Request $request, Transfer $transfer)
    // {
    //     // Ambil data relasi penting
    //     $transfer->load([
    //         'fromWarehouse',
    //         'toWarehouse',
    //         'user:id,name,username',
    //         'attachments',
    //     ]);

    //     // Format tanggal
    //     $transfer->formatted_created_at = Carbon::parse($transfer->created_at)
    //         ->locale('id')
    //         ->translatedFormat('d F Y H:i');

    //     // Ambil semua item yang terlibat (berdasarkan transfer ini)
    //     $items = Item::with(['unit', 'checkinItems', 'checkoutItems'])
    //         ->whereHas('checkoutItems.checkout', fn($q) => $q->where('id', $transfer->id))
    //         ->orWhereHas('checkinItems.checkin', fn($q) => $q->where('id', $transfer->id))
    //         ->get();

    //     // Mapping data item seperti di PDF export
    //     $mappedItems = $items->map(function ($item) {
    //         $saldoAwal   = 0; // placeholder jika belum ada histori stok
    //         $pemasukan   = $item->checkinItems->sum('quantity');
    //         $pengeluaran = $item->checkoutItems->sum('quantity');
    //         $adjustment  = 0; // bisa dihubungkan nanti ke tabel adjustment_items
    //         $saldoAkhir  = $saldoAwal + $pemasukan - $pengeluaran + $adjustment;

    //         return [
    //             'code'         => $item->code,
    //             'name'         => $item->name,
    //             'unit'         => $item->unit->code ?? '-',
    //             'saldo_awal'   => $saldoAwal,
    //             'pemasukan'    => $pemasukan,
    //             'pengeluaran'  => $pengeluaran,
    //             'adjustment'   => $adjustment,
    //             'saldo_akhir'  => $saldoAkhir,
    //             'cacah'        => $saldoAkhir, // placeholder
    //             'selisih'      => $saldoAkhir - $saldoAwal,
    //         ];
    //     });

    //     // Tambahkan hasil mapping ke model menggunakan setRelation
    //     $transfer->setRelation('items', $mappedItems);

    //     return Inertia::render('Transfer/Show', [
    //         'transfer' => $transfer,
    //     ]);
    // }

    public function edit(Transfer $transfer)
    {
        $this->authorize('update', $transfer);

        return Inertia::render('Transfer/Form', [
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'edit'       => $transfer->load(['items.variations', 'items.item.variations', 'attachments']),
        ]);
    }

    public function update(TransferRequest $request, Transfer $transfer)
    {
        $this->authorize('update', $transfer);
        $data = $request->validated();
        $original = $transfer->load(['items.item', 'items.unit', 'items.variations'])->replicate();
        $transfer = (new PrepareOrder($data, $request->file('attachments'), $transfer))->process()->save();
        event(new \App\Events\TransferEvent($transfer, 'updated', $original));
        session()->flash('message', __choice('action_text', ['record' => 'Transfer', 'action' => 'updated']));

        return $request->listing == 'yes' ? redirect()->route('transfers.index') : back();
    }

    public function destroy(Transfer $transfer)
    {
        $transfer->load(['items.item', 'items.unit', 'items.variations']);
        if ($transfer->del()) {
            event(new \App\Events\TransferEvent($transfer, 'deleted'));

            return redirect()->route('transfers.index')->with('message', __choice('action_text', ['record' => 'Transfer', 'action' => 'deleted']));
        }

        return redirect()->route('transfers.index')->with('error', __('The record can not be deleted.'));
    }

    public function restore(Transfer $transfer)
    {
        $transfer->restore();
        $transfer->items->each->restore();
        event(new \App\Events\TransferEvent($transfer, 'restored'));

        return back()->with('message', __choice('action_text', ['record' => 'Transfer', 'action' => 'restored']));
    }

    public function destroyPermanently(Transfer $transfer)
    {
        $deleted = $transfer->deleted_at;
        $transfer->load(['items.item', 'items.unit', 'items.variations']);
        if ($transfer->delP()) {
            if (! $deleted) {
                event(new \App\Events\TransferEvent($transfer, 'deleted'));
            }

            return redirect()->route('transfers.index')->with('message', __choice('action_text', ['record' => 'Transfer', 'action' => 'permanently deleted']));
        }

        return redirect()->route('transfers.index')->with('error', __('The record can not be deleted.'));
    }
}
