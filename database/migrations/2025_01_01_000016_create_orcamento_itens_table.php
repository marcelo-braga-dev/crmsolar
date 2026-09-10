<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Itens flexíveis do orçamento — suporta kits solares, produtos avulsos (baterias, bombas,
// iluminação etc.), serviços e itens personalizados em um único modelo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();

            // Tipo do item
            $table->enum('tipo', ['kit', 'produto', 'servico', 'personalizado']);

            // Referências (nullable conforme o tipo)
            $table->foreignId('kit_id')->nullable()->constrained('kits')->nullOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();

            // Descrição customizada (usada em 'servico' e 'personalizado', ou como override)
            $table->string('descricao')->nullable();

            // Quantidade e preços
            $table->integer('quantidade')->default(1);
            $table->decimal('preco_custo_unitario', 12, 2)->default(0);
            $table->decimal('preco_venda_unitario', 12, 2)->default(0);
            $table->decimal('preco_venda_total', 12, 2)->default(0);     // quantidade × preco_venda_unitario
            $table->decimal('margem_percentual', 8, 3)->default(0);
            $table->decimal('comissao_percentual', 6, 3)->default(0);

            // Dados específicos de kit solar
            $table->integer('geracao_estimada')->nullable(); // kWh/mês, apenas para kits

            // Metadados extras (ex: nome_kit, nome_produto snapshots, specs)
            $table->json('metadados')->nullable();

            $table->integer('ordem')->default(0); // ordem de exibição na proposta

            $table->timestamps();

            $table->index(['orcamento_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
    }
};
