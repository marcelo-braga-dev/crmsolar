<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Campos técnicos e tarifários adicionados ao orcamento_infos para suportar
 * todos os grupos tarifários ANEEL: B1/B2/B3 (Baixa Tensão) e A4/A3a/A3/A2/A1 (Média/Alta Tensão).
 */

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orcamento_infos', function (Blueprint $table) {
            // ── Tarifas de energia ────────────────────────────────────────
            $table->decimal('tarifa_kwh', 8, 5)->nullable()->after('demanda_contratada');
            $table->decimal('tarifa_kwh_ponta', 8, 5)->nullable()->after('tarifa_kwh');
            $table->decimal('tarifa_kwh_fp', 8, 5)->nullable()->after('tarifa_kwh_ponta');
            $table->decimal('tarifa_demanda_ponta', 8, 2)->nullable()->after('tarifa_kwh_fp');
            $table->decimal('tarifa_demanda_fp', 8, 2)->nullable()->after('tarifa_demanda_ponta');
            $table->decimal('valor_conta_mensal', 10, 2)->nullable()->after('tarifa_demanda_fp');

            // ── Ligação elétrica ──────────────────────────────────────────
            $table->enum('fases', ['monofasico', 'bifasico', 'trifasico'])->nullable()->after('valor_conta_mensal');
            $table->smallInteger('disponibilidade_kwh')->nullable()->after('fases');

            // ── Objetivo e perfil ─────────────────────────────────────────
            $table->tinyInteger('objetivo_percentual')->default(100)->after('disponibilidade_kwh');
            $table->tinyInteger('percentual_autoconsumo')->default(80)->after('objetivo_percentual');

            // ── Demanda (Grupo A) ─────────────────────────────────────────
            $table->decimal('demanda_ponta_kw', 10, 2)->nullable()->after('percentual_autoconsumo');
            $table->decimal('demanda_fora_ponta_kw', 10, 2)->nullable()->after('demanda_ponta_kw');
            $table->string('subgrupo_tensao', 20)->nullable()->after('demanda_fora_ponta_kw');

            // ── B2 Rural ──────────────────────────────────────────────────
            $table->string('tipo_instalacao', 40)->nullable()->after('subgrupo_tensao');
            $table->boolean('possui_bombeamento')->nullable()->after('tipo_instalacao');
            $table->decimal('consumo_bombeamento', 10, 2)->nullable()->after('possui_bombeamento');

            // ── B3 / Grupo A Comercial ────────────────────────────────────
            $table->tinyInteger('horario_funcionamento')->nullable()->after('consumo_bombeamento');

            // ── Resultados ────────────────────────────────────────────────
            $table->json('analise_economica')->nullable()->after('horario_funcionamento');
            $table->json('metadados')->nullable()->after('analise_economica');
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_infos', function (Blueprint $table) {
            $table->dropColumn([
                'tarifa_kwh', 'tarifa_kwh_ponta', 'tarifa_kwh_fp',
                'tarifa_demanda_ponta', 'tarifa_demanda_fp', 'valor_conta_mensal',
                'fases', 'disponibilidade_kwh', 'objetivo_percentual', 'percentual_autoconsumo',
                'demanda_ponta_kw', 'demanda_fora_ponta_kw', 'subgrupo_tensao',
                'tipo_instalacao', 'possui_bombeamento', 'consumo_bombeamento',
                'horario_funcionamento', 'analise_economica', 'metadados',
            ]);
        });
    }
};
