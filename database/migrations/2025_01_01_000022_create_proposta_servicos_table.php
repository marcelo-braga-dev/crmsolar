<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposta_servicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultor_id')->constrained('users');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('titulo');
            $table->text('descricao');
            $table->decimal('valor', 12, 2);
            $table->date('prazo_final')->nullable();
            $table->enum('status', ['rascunho', 'enviado', 'aprovado', 'recusado'])->default('rascunho');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposta_servicos');
    }
};
