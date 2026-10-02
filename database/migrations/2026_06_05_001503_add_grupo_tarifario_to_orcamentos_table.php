<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grupo tarifário ANEEL — identifica o perfil tarifário do cliente.
 *
 * Grupo B (Baixa Tensão — até 2,3 kV):
 *   B1  — Residencial (tarifa convencional)
 *   B2  — Rural (tarifa subsidiada)
 *   B3  — Comercial / Industrial BT (tarifa convencional)
 *
 * Grupo A (Média e Alta Tensão — acima de 2,3 kV):
 *   A4  — 2,3 kV a 25 kV (mais comum comercial/industrial médio porte)
 *   A3a — 30 kV a 44 kV
 *   A3  — 69 kV
 *   A2  — 88 kV a 138 kV
 *   A1  — ≥ 230 kV (grandes indústrias)
 *
 * Modalidade tarifária (Grupo A):
 *   THS_VERDE — Tarifa Horo-Sazonal Verde  (demanda única + consumo P/FP)
 *   THS_AZUL  — Tarifa Horo-Sazonal Azul   (demanda P/FP + consumo P/FP)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->enum('grupo_tarifario', [
                // Grupo B
                'B1', 'B2', 'B3',
                // Grupo A — Média Tensão
                'A4', 'A3a', 'A3', 'A2', 'A1',
            ])->nullable()->after('status')
                ->comment('Grupo tarifário ANEEL do cliente');

            $table->enum('modalidade_tarifaria', [
                'convencional',   // Grupo B ou Grupo A com tarifa única
                'THS_VERDE',      // Horo-Sazonal Verde (Grupo A)
                'THS_AZUL',       // Horo-Sazonal Azul  (Grupo A)
            ])->default('convencional')->after('grupo_tarifario');

            $table->index('grupo_tarifario');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropIndex(['grupo_tarifario']);
            $table->dropColumn(['grupo_tarifario', 'modalidade_tarifaria']);
        });
    }
};
