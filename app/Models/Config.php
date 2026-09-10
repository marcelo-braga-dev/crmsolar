<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = 'configs';

    protected $fillable = ['grupo', 'chave', 'valor', 'descricao'];

    public static function get(string $chave, mixed $default = null): mixed
    {
        return static::where('chave', $chave)->value('valor') ?? $default;
    }

    public static function set(string $chave, mixed $valor, string $grupo = 'geral'): void
    {
        static::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor, 'grupo' => $grupo]
        );
    }
}
