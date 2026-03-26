<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'min_booking_duration',
        'schedule_config',
    ];

    protected $casts = [
        'schedule_config' => 'array',
    ];

    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
