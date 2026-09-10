<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos');
            $table->foreignId('consultor_id')->constrained('users');

            // Dados do cliente no momento da assinatura (snapshot)
            $table->string('nome_cliente');
            $table->string('documento_cliente', 20);
            $table->string('endereco_instalacao');

            // Dados técnicos do projeto
            $table->decimal('potencia_kwp', 10, 3);
            $table->integer('qtd_paineis');
            $table->integer('qtd_inversores');
            $table->string('modelo_inversor');
            $table->integer('consumo_mensal'); // kWh
            $table->integer('geracao_estimada'); // kWh/mês
            $table->string('garantia_paineis');
            $table->string('garantia_inversores');

            // Financeiro
            $table->decimal('valor_total', 12, 2);
            $table->text('formas_pagamento');

            // Texto do contrato
            $table->text('clausulas_adicionais')->nullable();
            $table->json('produtos_snapshot'); // snapshot dos produtos no momento da geração

            $table->enum('status', ['gerado', 'assinado', 'cancelado'])->default('gerado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
