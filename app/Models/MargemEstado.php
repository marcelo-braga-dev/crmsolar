<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class MargemEstado extends Model
{
    use LogsActivity;

    protected $table = 'margens_estados';

    protected $fillable = ['estado', 'nome_estado', 'margem'];

    protected $casts = ['margem' => 'decimal:3'];

    /** Camada 2 da precificação. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
