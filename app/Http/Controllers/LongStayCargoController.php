<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Imports\InboundImport;
use App\Models\Checkin;
use App\Models\Contact;
use App\Models\Warehouse;
use App\Models\TypeBc;
use App\Mail\EmailCheckin;
use App\Actions\Tec\PrepareOrder;
use App\Http\Resources\Collection;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CheckinRequest;
use Maatwebsite\Excel\Facades\Excel;

class LongStayCargoController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        // Ambil semua checkin
        $longstaycargo = Checkin::with(['contact', 'warehouse', 'user', 'items.item'])
            ->filter($filters)
            ->whereNotNull('date_receive')
            ->orderByDesc('id')
            ->paginate()
            ->withQueryString();

        // Transformasi data: hitung status expired & lama waktu
        $longstaycargo->getCollection()->transform(function ($item) {
            if ($item->date_receive) {
                $receive = Carbon::parse($item->date_receive);
                $expired = $receive->copy()->addMonths(33);
                $diffMonths = $receive->diffInMonths(now());
            
                // Status expired
                $item->date_expired = $expired->format('Y-m-d');
                $item->status_expired = $diffMonths >= 33
                    ? 'expired'
                    : ($diffMonths >= 6
                        ? 'warning'
                        : 'normal');
            
                // Lama waktu (tahun/bulan/hari)
                $diff = $receive->diff(now());
                $parts = [];
                if ($diff->y > 0) $parts[] = "{$diff->y} Tahun";
                if ($diff->m > 0) $parts[] = "{$diff->m} Bulan";
                if ($diff->d > 0 || empty($parts)) $parts[] = "{$diff->d} Hari";
                $item->lama_total = implode(', ', $parts);
            } else {
                $item->date_expired = '-';
                $item->status_expired = 'normal';
                $item->lama_total = '-';
            }

            // Ambil info tambahan dari item pertama
            $firstItem = $item->items->first();
            $item->sender = $firstItem->sender ?? '-';
            $item->owner = $firstItem->owner ?? '-';
            $item->item_name = $firstItem?->item?->name ?? '-';
            $item->item_code = $firstItem?->item?->code ?? '-';

            return $item;
        });

        // Filter hasil hanya yang status warning / expired
        $filteredData = $longstaycargo->getCollection()->filter(function ($item) {
            return in_array($item->status_expired, ['warning', 'expired']);
        })->values();

        // Replace collection di pagination dengan hasil filter
        $longstaycargo->setCollection($filteredData);

        return Inertia::render('LongStayCargo/Index', [
            'filters' => $filters,
            'longstaycargo' => new Collection($longstaycargo),
        ]);
    }
}
