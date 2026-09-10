<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('irradiacao_solar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cidade_id')->constrained('cidades_estados')->cascadeOnDelete();
            $table->decimal('media', 5, 3);
            $table->decimal('jan', 5, 3);
            $table->decimal('fev', 5, 3);
            $table->decimal('mar', 5, 3);
            $table->decimal('abr', 5, 3);
            $table->decimal('mai', 5, 3);
            $table->decimal('jun', 5, 3);
            $table->decimal('jul', 5, 3);
            $table->decimal('ago', 5, 3);
            $table->decimal('set', 5, 3);
            $table->decimal('out', 5, 3);
            $table->decimal('nov', 5, 3);
            $table->decimal('dez', 5, 3);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('irradiacao_solar');
    }
};
