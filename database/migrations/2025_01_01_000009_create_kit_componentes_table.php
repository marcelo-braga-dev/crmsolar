<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Composição dos kits — lista de produtos (painéis, inversores, trafos, etc.) que formam um kit.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained('kits')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->integer('quantidade')->default(1);
            $table->string('observacao')->nullable();
            $table->unique(['kit_id', 'produto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_componentes');
    }
};
