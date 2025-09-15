<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait RawAttributes
{
    protected function initializeRawAttributes()
    {
        $this->setAppends(array_unique(array_merge($this->getAppends(), ['date_raw'])));
    }

    protected function dateRaw(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getRawOriginal('date'),
        );
    }
}
