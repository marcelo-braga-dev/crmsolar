<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_vistorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->unique()->constrained('orcamentos')->cascadeOnDelete();
            $table->decimal('largura_telhado', 8, 2)->nullable();
            $table->decimal('altura_telhado', 8, 2)->nullable();
            $table->string('tipo_disjuntor')->nullable();
            $table->string('padrao_energia')->nullable();
            $table->string('tipo_telhado')->nullable();
            $table->string('tipo_fiacao')->nullable();
            $table->string('tipo_medidor')->nullable();
            $table->text('outros')->nullable();
            $table->text('observacoes')->nullable();
            $table->json('imagens')->nullable(); // array de slugs/paths
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_vistorias');
    }
};
