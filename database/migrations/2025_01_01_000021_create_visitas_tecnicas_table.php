<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas_tecnicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultor_id')->constrained('users');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->dateTime('data_agendada');
            $table->enum('status', ['agendada', 'realizada', 'cancelada'])->default('agendada');
            $table->text('anotacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_tecnicas');
    }
};
