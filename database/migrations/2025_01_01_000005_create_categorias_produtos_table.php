<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Categorias flexíveis de produto — suporta solar, baterias, bombas, iluminação, etc.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('descricao')->nullable();
            $table->string('icone')->nullable(); // nome do ícone MUI
            $table->boolean('eh_componente_kit')->default(false); // se pode ser usado como componente de kit
            $table->boolean('exige_potencia')->default(false);    // se o produto deve ter campo potência
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_produtos');
    }
};
