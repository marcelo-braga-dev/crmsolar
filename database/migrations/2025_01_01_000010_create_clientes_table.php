<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Clientes com dados PF e PJ diretamente na tabela (sem EAV).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultor_id')->constrained('users');
            $table->foreignId('cidade_id')->nullable()->constrained('cidades_estados')->nullOnDelete();

            // Tipo e identificação
            $table->enum('tipo_pessoa', ['pf', 'pj'])->default('pf');
            $table->string('nome')->nullable();           // PF
            $table->string('razao_social')->nullable();   // PJ
            $table->string('cpf', 14)->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('rg', 20)->nullable();
            $table->date('data_nascimento')->nullable();

            // Contato
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();

            // Endereço (colunas diretas, sem EAV)
            $table->string('cep', 9)->nullable();
            $table->string('rua')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();

            // Status no pipeline
            $table->enum('status', ['novo', 'orcamento_gerado', 'visita_agendada', 'finalizado'])->default('novo');

            $table->text('anotacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
