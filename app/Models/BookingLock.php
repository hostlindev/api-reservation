<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingLock extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }

    protected $fillable = [
        'court_id',
        'session_id',
        'start_time',
        'end_time',
        'expires_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }
}
