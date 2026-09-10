<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MargemEstado extends Model
{
    protected $table = 'margens_estados';

    protected $fillable = ['estado', 'nome_estado', 'margem'];

    protected $casts = ['margem' => 'decimal:3'];
}
