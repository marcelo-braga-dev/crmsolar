<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concessionarias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->char('estado', 2);
            $table->decimal('tarifa_convencional', 8, 5);
            $table->decimal('tarifa_ponta', 8, 5);
            $table->decimal('tarifa_intermediaria', 8, 5)->default(0);
            $table->decimal('tarifa_fora_ponta', 8, 5);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concessionarias');
    }
};
