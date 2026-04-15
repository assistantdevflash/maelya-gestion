<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class InstitutScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->role !== 'super_admin') {
            $institutId = session('current_institut_id', Auth::user()->institut_id);
            if ($institutId) {
                $builder->where($model->getTable() . '.institut_id', $institutId);
            }
        }
    }
}
