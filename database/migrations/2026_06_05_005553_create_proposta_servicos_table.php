<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabela já existe via 2025_01_01_000022 — apenas adiciona/altera colunas
        Schema::table('proposta_servicos', function (Blueprint $table) {
            // Adiciona campo de conteúdo completo (substitui 'descricao' curta)
            if (!\Illuminate\Support\Facades\Schema::hasColumn('proposta_servicos', 'conteudo')) {
                $table->longText('conteudo')->nullable()->after('titulo');
            }
            // Validade da proposta (substitui/complementa prazo_final)
            if (!\Illuminate\Support\Facades\Schema::hasColumn('proposta_servicos', 'validade')) {
                $table->date('validade')->nullable()->after('valor');
            }
            // Observações internas (não exibidas ao cliente)
            if (!\Illuminate\Support\Facades\Schema::hasColumn('proposta_servicos', 'observacoes')) {
                $table->text('observacoes')->nullable()->after('conteudo');
            }
            // Token para link público
            if (!\Illuminate\Support\Facades\Schema::hasColumn('proposta_servicos', 'token')) {
                $table->string('token', 80)->unique()->nullable()->after('observacoes');
            }
        });

        // Ajusta o enum de status para incluir os novos valores
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE proposta_servicos
            MODIFY COLUMN status ENUM('rascunho','enviada','aceita','recusada','expirada','enviado','aprovado')
            NOT NULL DEFAULT 'rascunho'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposta_servicos', function (Blueprint $table) {
            foreach (['conteudo', 'validade', 'observacoes', 'token'] as $col) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('proposta_servicos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
