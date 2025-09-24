<?php

namespace App\Models;

use App\Casts\AppDate;
use App\Traits\OrderHelpers;
use App\Traits\RawAttributes;
use App\Traits\HasAttachments;
use App\Traits\HasManySyncable;

class Checkout extends Model
{
    use HasAttachments;
    use HasManySyncable;
    use OrderHelpers;
    use RawAttributes;

    public static $hasReference = true;

    public static $hasUser = true;

    protected $fillable = [
        'transaction_number', 'date', 'reference',  'draft', 'contact_id', 'warehouse_id', 'user_id',
        'hash', 'approved_by', 'account_id', 'details', 'extra_attributes', 'approved_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($checkout) {
            // tanggal transaksi
            $today = now();
            $year  = $today->format('y'); // 25
            $month = $today->format('m'); // 09
            $day   = $today->format('d'); // 23

            // ambil warehouse name/code
            // $warehouse = $checkout->warehouse ? strtoupper($checkout->warehouse->name) : 'XXX';
            if ($checkout->warehouse) {
                if ($checkout->warehouse->type === 'PLB') {
                    $warehouse = 'PLB';
                } else {
                    $warehouse = 'NPLB';
                }
            } else {
                $warehouse = 'XXX';
            }

            // random 3 digit
            $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

            $checkout->transaction_number = "EN/{$year}/{$month}/{$day}/{$warehouse}/{$random}";
        });
    }

    protected $setHash = true;

    protected $setUser = true;

    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
            'date'             => AppDate::class,
            'created_at'       => AppDate::class . ':time',
            'updated_at'       => AppDate::class . ':time',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function del()
    {
        $this->items->each->delete();

        return $this->delete();
    }

    public function delP()
    {
        log_activity(__choice('delete_text', ['record' => 'Item']), $this, $this, 'Item');
        $this->items->each->forceDelete();

        return $this->forceDelete();
    }

    public function items()
    {
        return $this->hasMany(CheckoutItem::class)->orderBy('id')->withTrashed();
    }

    public function scopeSearch($query, $s)
    {
        $query->where(
            fn ($q) => $q->where('id', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")
                ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('username', 'like', "%{$s}%"))
                ->orWhereHas('warehouse', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
