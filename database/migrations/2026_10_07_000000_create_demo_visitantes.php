<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Visitantes do modo demonstração (DEMO.md): cada acesso vira um lead para a equipe comercial. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_visitantes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('email')->nullable()->index();
            $table->string('telefone', 20)->nullable()->index(); // só dígitos
            $table->string('empresa', 120)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('referer', 255)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->unsignedInteger('visitas')->default(0);
            $table->unsignedInteger('telas_vistas')->default(0);
            $table->json('perfis_vistos')->nullable();
            $table->string('ultimo_perfil', 20)->nullable();
            $table->timestamp('primeiro_acesso_em')->nullable();
            $table->timestamp('ultimo_acesso_em')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_visitantes');
    }
};
