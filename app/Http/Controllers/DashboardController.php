<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Inertia\Inertia;
use App\Models\Checkin;
use App\Models\Contact;
use App\Models\Activity;
use App\Models\Checkout;
use App\Models\Warehouse;
use App\Models\StockTrail;
use Illuminate\Http\Request;
use App\Actions\Tec\ChartData;
use App\Http\Resources\Collection;
use Carbon\Carbon;
use App\Models\CheckinItem;

class DashboardController extends Controller
{
    public function activity(Request $request)
    {
        return Inertia::render('Activity/Index', [
            'filters'    => $request->all('search'),
            'activities' => new Collection(Activity::filter($request->only('search'))->orderByDesc('id')->paginate()),
        ]);
    }

    public function form(Request $request)
    {
        return $request->validate([
            'month' => 'nullable|integer|date_format:n',
            'year'  => 'nullable|integer|date_format:Y',
        ]);
    }

    public function index(Request $request)
    {
        $this->form($request);
        // $topProducts = StockTrail::select(['item_id'])->selectRaw('SUM(quantity) as y')
        //     ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
        //     ->with('item:id,code')->orderByRaw('SUM(quantity) desc')->groupBy('item_id')
        //     ->take(10)->get()->transform(fn ($i) => ['name' => $i->item->code, 'y' => +$i->y]);
        $data = Item::selectRaw('COUNT(*) as items')
            ->addSelect(['checkins' => Checkin::selectRaw('COUNT(*) as checkins')->active()
                ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]), ])
            ->addSelect(['checkouts' => Checkout::selectRaw('COUNT(*) as checkouts')->active()
                ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]), ])
            ->addSelect(['previous_checkins' => Checkin::selectRaw('COUNT(*) as checkins')->active()
                ->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]), ])
            ->addSelect(['previous_checkouts' => Checkout::selectRaw('COUNT(*) as checkouts')->active()
                ->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]), ])
            ->addSelect(['contacts' => Contact::selectRaw('COUNT(*) as contacts')])->first();
        $chart = new ChartData($request->get('month'), $request->get('year'));

        $alertInbound = Checkin::with([
            'user',
            'items' => function ($q) {
                $q->select('id', 'checkin_id', 'sender', 'owner');
            }
        ])
        ->select('id', 'reference', 'date_receive', 'no_receive', 'user_id')
        ->whereNotNull('date_receive')
        ->get()
        ->map(function ($item) {
            $receive = Carbon::parse($item->date_receive);
            $expired = $receive->copy()->addMonths(33);
            $diffMonths = $receive->diffInMonths(now());

            $item->date_expired = $expired->format('Y-m-d');
            $item->status_expired = $diffMonths >=33 ? 'expired' : ($diffMonths >= 6? 'warning' : 'normal');

            $item->sender = $item->items->first()->sender ?? '-';
            $item->owner  = $item->items->first()->owner ?? '-';

            return $item;
        })
        ->filter(fn($i) => in_array($i->status_expired, ['warning', 'expired']))
        ->values();

        return Inertia::render('Dashboard/Index', [
            'data'         => $data,
            'top_products' => $chart->topProducts(),
            'chart'        => ['year' => $chart->year(), 'month' => $chart->month()],
            'alert_inbound' => $alertInbound,
        ]);
    } 

}
