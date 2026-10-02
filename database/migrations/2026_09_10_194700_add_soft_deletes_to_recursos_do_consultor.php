<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cliente, orçamento, visita técnica e proposta de serviço podem ser excluídos
// pelo consultor/admin — soft delete evita perda definitiva de dados e mantém
// histórico para auditoria (ver spatie/laravel-activitylog).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('visitas_tecnicas', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('proposta_servicos', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('visitas_tecnicas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('proposta_servicos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
