<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->role === 'local_admin' && Auth::user()->local_id) {
            if ($model instanceof \App\Models\Court) {
                $builder->where('local_id', Auth::user()->local_id);
            } elseif ($model instanceof \App\Models\Booking || $model instanceof \App\Models\BookingLock) {
                $builder->whereHas('court', function ($query) {
                    $query->where('local_id', Auth::user()->local_id);
                });
            }
        }
    }
}
