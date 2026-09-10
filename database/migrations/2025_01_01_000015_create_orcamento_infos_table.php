<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dados técnicos do dimensionamento vinculados ao orçamento (1:1).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->unique()->constrained('orcamentos')->cascadeOnDelete();
            $table->foreignId('estrutura_id')->nullable()->constrained('estruturas')->nullOnDelete();
            $table->foreignId('concessionaria_id')->nullable()->constrained('concessionarias')->nullOnDelete();

            // Tipo de dimensionamento utilizado
            $table->enum('tipo_dimensionamento', ['convencional', 'demanda', 'off_grid'])->default('convencional');

            // Consumo (kWh/mês)
            $table->decimal('consumo', 10, 2)->nullable();
            $table->decimal('consumo_ponta', 10, 2)->nullable();
            $table->decimal('consumo_fora_ponta', 10, 2)->nullable();
            $table->decimal('demanda_contratada', 10, 2)->nullable();

            // Instalação
            $table->integer('tensao')->nullable();     // 127, 220, 380 V
            $table->enum('orientacao', [
                'norte',
                'nordeste_noroeste',
                'leste_oeste',
                'sudeste_sudoeste',
                'sul',
            ])->default('norte');

            $table->boolean('bloquear_edicao')->default(false);
            $table->text('anotacoes_tecnicas')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_infos');
    }
};
