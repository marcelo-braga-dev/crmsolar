<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_aprovacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->unique()->constrained('orcamentos')->cascadeOnDelete();
            $table->string('nome_assinante')->nullable();
            $table->string('documento_assinante', 20)->nullable();
            $table->string('ip_assinatura', 45)->nullable();
            $table->string('forma_pagamento')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('assinado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_aprovacoes');
    }
};
