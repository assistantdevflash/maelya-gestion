<?php

namespace App\Traits;

use App\Scopes\InstitutScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToInstitut
{
    protected static function bootBelongsToInstitut(): void
    {
        static::addGlobalScope(new InstitutScope());

        static::creating(function ($model) {
            if (Auth::check() && empty($model->institut_id)) {
                $model->institut_id = session('current_institut_id', Auth::user()->institut_id);
            }
        });
    }

    public function institut()
    {
        return $this->belongsTo(\App\Models\Institut::class, 'institut_id');
    }
}
