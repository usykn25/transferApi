<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transfer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function transferDetails(): HasMany
    {
        return $this->hasMany(TransferDetail::class, 'transfer_id');
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Passenger::class, 'transfer_details', 'passenger_id', 'transfer_id');
    }
}
