<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Kits solares pré-configurados — composto por painéis + inversor + outros componentes.
// Os componentes são definidos na tabela kit_componentes (relação N:N com produtos).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores');
            $table->foreignId('estrutura_id')->nullable()->constrained('estruturas')->nullOnDelete();

            // Identificação
            $table->string('nome');
            $table->string('modelo')->nullable();
            $table->string('sku', 60)->nullable()->index();

            // Especificações técnicas
            $table->decimal('potencia_kwp', 10, 3);
            $table->integer('tensao');          // 127, 220, 380 V
            $table->boolean('inclui_trafo')->default(false);

            // Preços
            $table->decimal('preco_custo', 12, 2)->default(0);
            $table->decimal('margem_padrao', 8, 3)->default(0); // margem específica do kit (opcional)

            // Status
            $table->boolean('ativo')->default(true);
            $table->boolean('ativo_fornecedor')->default(true); // sincronizado com fornecedor

            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kits');
    }
};
