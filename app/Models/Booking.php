<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\TenantScope);
    }

    protected $fillable = [
        'court_id',
        'name',
        'lastname',
        'id_card',
        'email',
        'start_time',
        'end_time',
        'qr_token',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }
}
