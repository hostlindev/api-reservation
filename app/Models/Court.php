<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }

    protected $fillable = [
        'local_id',
        'category',
        'name',
        'number',
        'price_per_hour',
        'description',
        'images',
        'status',
    ];

    protected $casts = [
        'price_per_hour' => 'decimal:2',
        'images' => 'array',
    ];

    public function local()
    {
        return $this->belongsTo(Local::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingLocks()
    {
        return $this->hasMany(BookingLock::class);
    }
}
