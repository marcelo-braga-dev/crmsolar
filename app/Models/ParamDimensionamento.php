<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ParamDimensionamento extends Model
{
    use LogsActivity;

    protected $table = 'params_dimensionamento';

    protected $fillable = ['chave', 'nome', 'valor', 'unidade', 'descricao'];

    public static function get(string $chave, mixed $default = null): mixed
    {
        return static::where('chave', $chave)->value('valor') ?? $default;
    }

    /** Parâmetros do motor de cálculo. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['valor'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
