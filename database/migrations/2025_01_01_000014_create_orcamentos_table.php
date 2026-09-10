<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultor_id')->constrained('users');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('cidade_id')->constrained('cidades_estados');

            $table->enum('status', [
                'novo',
                'aprovando',
                'aprovado',
                'aprovacao_reprovada',
                'instalando',
                'finalizado',
            ])->default('novo');

            // Totais calculados (desnormalizados para performance)
            $table->decimal('preco_total', 12, 2)->default(0);
            $table->integer('geracao_estimada')->default(0); // kWh/mês

            // Link público de visualização
            $table->string('token', 80)->unique();

            $table->text('anotacoes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('consultor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
