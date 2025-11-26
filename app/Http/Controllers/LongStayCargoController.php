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
use Illuminate\Pagination\LengthAwarePaginator;

class LongStayCargoController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all('draft', 'search', 'trashed');

        // 🔹 Ambil semua data terlebih dahulu (tanpa pagination dulu)
        $data = Checkin::with(['contact', 'warehouse', 'user', 'items.item'])
            ->filter($filters)
            ->whereNotNull('date_receive')
            ->orderByDesc('id')
            ->get();

        // 🔹 Transformasi data (tambahkan status_expired & lama_total)
        $data = $data->map(function ($item) {
            if ($item->date_receive) {
                $receive = Carbon::parse($item->date_receive);
                $expired = $receive->copy()->addMonths(33);
                $diffMonths = $receive->diffInMonths(now());

                // Tentukan status expired
                $item->date_expired = $expired->format('Y-m-d');
                $item->status_expired = $diffMonths >= 33
                    ? 'expired'
                    : ($diffMonths >= 6 ? 'warning' : 'normal');

                // Hitung lama waktu (tahun, bulan, hari)
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

            // Ambil item pertama sebagai referensi
            $firstItem = $item->items->first();
            $item->sender = $firstItem->sender ?? '-';
            $item->owner = $firstItem->owner ?? '-';
            $item->item_name = $firstItem?->item?->name ?? '-';
            $item->item_code = $firstItem?->item?->code ?? '-';

            return $item;
        });

        // 🔹 Filter hanya data warning / expired
        $filtered = $data->filter(fn ($i) => in_array($i->status_expired, ['warning', 'expired']))->values();

        // 🔹 Pagination manual (karena kita sudah filter di Collection)
        $page = (int) $request->input('page', 1);
        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        $paginated = new LengthAwarePaginator(
            $filtered->slice($offset, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        // 🔹 Kirim ke Vue via Inertia
        return Inertia::render('LongStayCargo/Index', [
            'filters' => $filters,
            'checkins' => new Collection($paginated),
        ]);
    }
}
