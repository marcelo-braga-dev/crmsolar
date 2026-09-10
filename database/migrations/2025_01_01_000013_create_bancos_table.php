<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->decimal('juros_mensal', 8, 4);
            $table->integer('qtd_parcelas');
            $table->integer('carencia')->default(0); // dias
            $table->boolean('ativo')->default(true);
            $table->string('url_logo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
