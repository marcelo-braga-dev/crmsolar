<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidades_estados', function (Blueprint $table) {
            $table->id();
            $table->string('cidade', 100);
            $table->string('estado', 100);
            $table->char('sigla', 2);
            $table->index(['sigla', 'cidade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cidades_estados');
    }
};
