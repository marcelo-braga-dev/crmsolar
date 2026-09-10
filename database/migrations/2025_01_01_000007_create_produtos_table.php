<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catálogo unificado de produtos — painéis, inversores, baterias, bombas, iluminação, etc.
// O campo `atributos` (JSON) permite specs específicas por categoria sem criar tabelas extras.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias_produtos');
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->foreignId('fornecedor_id')->nullable()->constrained('fornecedores')->nullOnDelete();

            // Identificação
            $table->string('nome');
            $table->string('modelo')->nullable();
            $table->string('sku', 60)->nullable()->index();
            $table->text('descricao')->nullable();

            // Specs técnicas (nullable — aplicáveis conforme categoria)
            $table->decimal('potencia', 10, 3)->nullable();           // kWp, kW, W — depende da categoria
            $table->string('unidade_potencia', 10)->nullable();       // kWp, kW, W, Wp
            $table->integer('tensao')->nullable();                     // Volts
            $table->string('unidade', 20)->default('un');             // un, m, m², kg, etc.

            // Preços
            $table->decimal('preco_custo', 12, 2)->default(0);

            // Informações do produto
            $table->string('garantia')->nullable();
            $table->string('imagem_url', 500)->nullable();
            $table->string('ficha_tecnica_url', 500)->nullable();

            // Atributos flexíveis por categoria (JSON):
            // Painel: {eficiencia, dimensoes, tipo_celula, voc, isc, vmp, imp}
            // Inversor: {fases, mppt_qty, rendimento, ip}
            // Bateria: {capacidade_ah, tipo, ciclos, dod_percentual, quimica}
            // Bomba: {vazao_m3h, altura_manometrica, corrente_a, tipo}
            // Iluminação: {lumens, cor_k, ip, potencia_w}
            $table->json('atributos')->nullable();

            // Status
            $table->boolean('ativo')->default(true);
            $table->boolean('ativo_fornecedor')->default(true); // sincronizado com fornecedor

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
