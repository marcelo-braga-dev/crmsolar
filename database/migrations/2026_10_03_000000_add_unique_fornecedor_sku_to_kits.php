<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A sincronização Edeltec faz upsert de kits por (fornecedor, sku). Sem índice único,
 * o ON DUPLICATE KEY do MySQL nunca dispara e cada sincronização duplicaria o catálogo.
 * Composto com fornecedor_id: o mesmo código pode existir em distribuidores diferentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->unique(['fornecedor_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->dropUnique(['fornecedor_id', 'sku']);
        });
    }
};
