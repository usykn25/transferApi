<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'driver_id');
    }

    public function scopeAvailableBetween($query, $startDateTime, $finishDateTime)
    {
        return $query->whereHas('transfers', function ($query) use ($startDateTime, $finishDateTime) {
            $query->where('transfer_start_time', '<=', $finishDateTime)
                ->where('transfer_finish_time', '>=', $startDateTime);
        });
    }
}
