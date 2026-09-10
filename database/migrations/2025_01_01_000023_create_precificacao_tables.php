<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Substituição do EAV de precificação por tabelas tipadas e explícitas.
return new class extends Migration
{
    public function up(): void
    {
        // Margem principal por faixa de potência (kWp)
        Schema::create('margens_principal', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->decimal('potencia_min', 10, 3)->default(0);   // kWp >= this
            $table->decimal('potencia_max', 10, 3)->nullable();    // kWp < this (null = sem limite)
            $table->decimal('margem', 8, 3);                       // percentual
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });

        // Margem adicional por estado (UF)
        Schema::create('margens_estados', function (Blueprint $table) {
            $table->id();
            $table->char('estado', 2)->unique();
            $table->string('nome_estado', 50);
            $table->decimal('margem', 8, 3)->default(0);
            $table->timestamps();
        });

        // Margem adicional por tipo de estrutura
        Schema::create('margens_estruturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estrutura_id')->unique()->constrained('estruturas')->cascadeOnDelete();
            $table->decimal('margem', 8, 3)->default(0);
            $table->timestamps();
        });

        // Margem adicional por fornecedor
        Schema::create('margens_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->unique()->constrained('fornecedores')->cascadeOnDelete();
            $table->decimal('margem', 8, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margens_fornecedores');
        Schema::dropIfExists('margens_estruturas');
        Schema::dropIfExists('margens_estados');
        Schema::dropIfExists('margens_principal');
    }
};
