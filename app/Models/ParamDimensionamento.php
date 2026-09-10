<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParamDimensionamento extends Model
{
    protected $table = 'params_dimensionamento';

    protected $fillable = ['chave', 'nome', 'valor', 'unidade', 'descricao'];

    public static function get(string $chave, mixed $default = null): mixed
    {
        return static::where('chave', $chave)->value('valor') ?? $default;
    }
}
