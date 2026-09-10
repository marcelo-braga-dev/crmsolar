<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('cidade')->nullable();
            $table->char('estado', 2)->nullable();
            $table->decimal('consumo_mensal', 10, 2)->nullable(); // kWh/mês

            $table->string('origem')->nullable(); // site, indicação, facebook, etc.
            $table->json('dados_extras')->nullable(); // dados adicionais do formulário de origem

            $table->enum('status', ['novo', 'contatado', 'encaminhado', 'convertido', 'perdido'])->default('novo');
            $table->text('anotacoes')->nullable();

            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
