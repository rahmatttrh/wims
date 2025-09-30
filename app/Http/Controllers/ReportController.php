<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Checkin;
use App\Models\CheckinItem;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Models\Adjustment;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\CheckinExport;
use App\Exports\CheckoutExport;
use App\Exports\TransferExport;
use App\Exports\AdjustmentExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function adjustment(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        return Inertia::render('Report/Adjustment', [
            'filters'     => $filters,
            'users'       => User::ofAccount()->get(),
            'categories'  => Category::ofAccount()->get(),
            'warehouses'  => Warehouse::ofAccount()->active()->get(),
            'adjustments' => new Collection(
                Adjustment::with(['warehouse', 'user'])->reportFilter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function checkin(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        return Inertia::render('Report/Checkin', [
            'filters'    => $filters,
            'users'      => User::ofAccount()->get(),
            'contacts'   => Contact::ofAccount()->get(),
            'categories' => Category::ofAccount()->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'checkins'   => new Collection(
                Checkin::with(['contact', 'warehouse', 'user'])->reportFilter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function checkout(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        return Inertia::render('Report/Checkout', [
            'filters'    => $filters,
            'users'      => User::ofAccount()->get(),
            'contacts'   => Contact::ofAccount()->get(),
            'categories' => Category::ofAccount()->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'checkouts'  => new Collection(
                Checkout::with(['contact', 'warehouse', 'user'])->reportFilter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function index()
    {
        $data = Item::selectRaw('COUNT(*) as items')->without('unit')
            ->addSelect(['contacts' => Contact::selectRaw('COUNT(*) as contacts')])
            ->addSelect(['categories' => Category::selectRaw('COUNT(*) as categories')])
            ->addSelect(['warehouses' => Warehouse::selectRaw('COUNT(*) as warehouses')])
            ->addSelect(['checkins' => Checkin::selectRaw('COUNT(*) as checkins')])
            ->addSelect(['checkouts' => Checkout::selectRaw('COUNT(*) as checkouts')])
            ->addSelect(['transfers' => Transfer::selectRaw('COUNT(*) as transfers')])
            ->addSelect(['adjustments' => Adjustment::selectRaw('COUNT(*) as adjustments')])
            ->addSelect(['units' => Unit::selectRaw('COUNT(*) as units')])
            ->addSelect(['users' => User::selectRaw('COUNT(*) as users')])
            ->addSelect(['roles' => Role::selectRaw('COUNT(*) as roles')])
            ->first();

        return Inertia::render('Report/Index', ['data' => $data]);
    }

    public function transfer(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'from_warehouse_id', 'user_id', 'to_warehouse_id', 'draft', 'trashed', 'category_id');

        return Inertia::render('Report/Transfer', [
            'filters'    => $filters,
            'users'      => User::ofAccount()->get(),
            'categories' => Category::ofAccount()->get(),
            'warehouses' => Warehouse::ofAccount()->active()->get(),
            'transfers'  => new Collection(
                Transfer::with(['fromWarehouse', 'toWarehouse', 'user'])->reportFilter($filters)->orderByDesc('id')->paginate()->withQueryString()
            ),
        ]);
    }

    public function exportCheckinXLSX(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        $filename = 'Inbound-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CheckinExport($filters), $filename);
    }

    public function exportCheckoutXLSX(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        $filename = 'Outbound-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CheckoutExport($filters), $filename);
    }

    public function exportTransferXLSX(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'from_warehouse_id', 'user_id', 'to_warehouse_id', 'draft', 'trashed', 'category_id');

        $filename = 'Transfer-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new TransferExport($filters), $filename);
    }

    public function exportAdjustmentXLSX(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        $filename = 'Adjustment-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new AdjustmentExport($filters), $filename);
    }

    // PDF

    public function exportCheckinPDF(Request $request)
    {
        dd($request->all());
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id', 'type_bc_id');

        $start = $request->get('start_date');
        $end   = $request->get('end_date');

        $checkins = Checkin::with([
            'contact',
            'warehouse',
            'user',
            'items.item',
            'items.unit',
            'type_bc'
        ])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        $printedAt = now()->format('Y-m-d H:i:s');

        $warehouseName = $checkins->first()->warehouse->name ?? '-';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.checkin_pdf', [
            'checkins' => $checkins,
            'filters'  => $filters,
            'printedAt' => now()->format('d/m/Y H:i'),
            'start_date' => $start,
            'end_date'   => $end,
            'warehouseName' => $warehouseName,
        ])->setPaper('A4', 'landscape');

        $filename = 'Inbound-report-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportCheckoutPDF(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id', 'type_bc_id');

        $start = $request->get('start_date');
        $end   = $request->get('end_date');

        $checkouts = Checkout::with([
            'contact',
            'warehouse',
            'user',
            'items.item',
            'items.unit',
            'type_bc'
        ])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        // dd($checkouts);

        $printedAt = now()->format('Y-m-d H:i:s');

        $warehouseName = $checkouts->first()->warehouse->name ?? '-';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.checkout_pdf', [
            'checkouts' => $checkouts,
            'filters'  => $filters,
            'printedAt' => now()->format('d/m/Y H:i'),
            'start_date' => $start,
            'end_date'   => $end,
            'warehouseName' => $warehouseName,
        ])->setPaper('A4', 'landscape');

        $filename = 'Outbound-report-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    // public function exportTransferPDF(Request $request)
    // {
    //     $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'from_warehouse_id', 'user_id', 'to_warehouse_id', 'draft', 'trashed', 'category_id');

    //     $transfers = Transfer::with(['fromWarehouse', 'toWarehouse', 'user', 'item'])
    //         ->reportFilter($filters)
    //         ->orderBy('id', 'asc')
    //         ->get();

    //     $warehouseName = $transfers->first()->warehouse->name ?? '-';

    //     $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.transfer_pdf', [
    //         'transfers' => $transfers,
    //         'filters'  => $filters,
    //         'printedAt' => now()->format('d/m/Y H:i'),
    //         'warehouseName' => $warehouseName,
    //     ])->setPaper('A4', 'landscape');

    //     $filename = 'Transfer-report-' . now()->format('Y-m-d') . '.pdf';
    //     return $pdf->download($filename);
    // }

    public function exportTransferPDF(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'from_warehouse_id', 'user_id', 'to_warehouse_id', 'draft', 'trashed', 'category_id');

        $start = $request->get('start_date');
        $end   = $request->get('end_date');

        $transfers = Transfer::with([
            'fromWarehouse',
            'toWarehouse',
            'user',
            'items.item',
            'items.unit'
        ])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        $warehouseName = $transfers->isNotEmpty() && $transfers->first()->toWarehouse
        ? $transfers->first()->toWarehouse->name
        : '-';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.transfer_pdf', [
            'transfers' => $transfers,
            'filters'  => $filters,
            'printedAt' => now()->format('d/m/Y H:i'),
            'warehouseName' => $warehouseName,
            'start_date' => $start,
            'end_date'   => $end,
        ])->setPaper('A4', 'landscape');

        $filename = 'Transfer-report-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportAdjustmentPDF(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        $adjustments = Adjustment::with(['contact', 'warehouse', 'user'])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.adjustment_pdf', [
            'adjustments' => $adjustments,
            'filters'  => $filters,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('A4', 'portrait');

        $filename = 'Adjustment-report-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
