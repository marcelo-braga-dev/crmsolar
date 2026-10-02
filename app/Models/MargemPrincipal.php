<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class MargemPrincipal extends Model
{
    use LogsActivity;

    protected $table = 'margens_principal';

    protected $fillable = ['nome', 'potencia_min', 'potencia_max', 'margem', 'ordem'];

    protected $casts = [
        'potencia_min' => 'decimal:3',
        'potencia_max' => 'decimal:3',
        'margem' => 'decimal:3',
    ];

    /** Camada 1 da precificação. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
}
