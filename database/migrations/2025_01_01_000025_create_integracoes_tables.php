<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mapeamento de referências Aldo → IDs locais
        Schema::create('integracao_aldo_mapeamentos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 60);    // painel, inversor, estrutura, trafo
            $table->string('nome_aldo');         // nome/chave que vem do XML da Aldo
            $table->string('potencia')->nullable();
            $table->unsignedBigInteger('id_referencia'); // ID local (produto_id ou estrutura_id)
            $table->unique(['categoria', 'nome_aldo']);
        });

        // Mapeamento de produtos Edeltec → IDs locais
        Schema::create('integracao_edeltec_mapeamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('produto_id');
            $table->string('categoria', 60);
            $table->string('nome');
            $table->integer('potencia')->nullable();
            $table->timestamps();
        });

        // Histórico de integrações (Aldo e Edeltec)
        Schema::create('integracao_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores');
            $table->enum('tipo', ['aldo', 'edeltec', 'excel', 'manual']);
            $table->enum('status', ['iniciado', 'concluido', 'erro']);
            $table->text('alertas')->nullable();
            $table->integer('itens_importados')->default(0);
            $table->integer('itens_atualizados')->default(0);
            $table->integer('itens_desativados')->default(0);
            $table->json('detalhes')->nullable();
            $table->timestamp('iniciado_em')->useCurrent();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integracao_historicos');
        Schema::dropIfExists('integracao_edeltec_mapeamentos');
        Schema::dropIfExists('integracao_aldo_mapeamentos');
    }
};
