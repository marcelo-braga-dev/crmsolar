<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Remove a camada de margem "Por Estrutura" — precificação passa a usar
// apenas Margem Principal, Por Estado e Por Fornecedor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('margens_estruturas');
    }

    public function down(): void
    {
        Schema::create('margens_estruturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estrutura_id')->unique()->constrained('estruturas')->cascadeOnDelete();
            $table->decimal('margem', 8, 3)->default(0);
            $table->timestamps();
        });
    }
};
