<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Checkin;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Checkout;
use App\Models\Transfer;
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

    public function exportCheckinPDF(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        $checkins = Checkin::with(['contact', 'warehouse', 'user', 'item', 'unit'])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.checkin_pdf', [
            'checkins' => $checkins,
            'filters'  => $filters,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('A4', 'landscape');

        $filename = 'Inbound-report-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportCheckoutPDF(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'contact_id', 'user_id', 'warehouse_id', 'draft', 'trashed', 'category_id');

        $checkouts = Checkout::with(['contact', 'warehouse', 'user', 'item', 'unit'])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        // Hitung total nilai (price_item * qty_out)
        $grandTotal = $checkouts->sum(function ($checkout) {
            return ($checkout->item->price_item ?? 0) * ($checkout->qty_out ?? 0);
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.checkout_pdf', [
            'checkouts' => $checkouts,
            'filters'  => $filters,
            'grandTotal' => $grandTotal,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('A4', 'landscape');

        $filename = 'Outbound-report-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    public function exportTransferPDF(Request $request)
    {
        $filters = $request->all('start_date', 'end_date', 'start_created_at', 'end_created_at', 'reference', 'from_warehouse_id', 'user_id', 'to_warehouse_id', 'draft', 'trashed', 'category_id');

        $transfers = Transfer::with(['fromWarehouse', 'toWarehouse', 'user'])
            ->reportFilter($filters)
            ->orderBy('id', 'asc')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.transfer_pdf', [
            'transfers' => $transfers,
            'filters'  => $filters,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('A4', 'portrait');

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
