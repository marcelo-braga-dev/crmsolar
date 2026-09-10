<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurações globais do sistema
        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 60)->default('geral'); // sistema, empresa, pdf, email, etc.
            $table->string('chave', 100)->unique();
            $table->text('valor')->nullable();
            $table->string('descricao')->nullable();
            $table->timestamps();
            $table->index('grupo');
        });

        // Parâmetros de dimensionamento solar (ajustáveis pelo admin)
        Schema::create('params_dimensionamento', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 60)->unique();
            $table->string('nome');
            $table->string('valor')->nullable();
            $table->string('unidade')->nullable();   // %, kWh, etc.
            $table->string('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('params_dimensionamento');
        Schema::dropIfExists('configs');
    }
};
