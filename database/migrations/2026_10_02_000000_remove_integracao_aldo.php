<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Integração Aldo descontinuada: remove a tabela de mapeamentos e o tipo "aldo"
 * do histórico de integrações. Só a Edeltec permanece.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('integracao_historicos')->where('tipo', 'aldo')->delete();

        Schema::dropIfExists('integracao_aldo_mapeamentos');

        $this->alterarTipos(['edeltec', 'excel', 'manual']);
    }

    public function down(): void
    {
        $this->alterarTipos(['aldo', 'edeltec', 'excel', 'manual']);

        Schema::create('integracao_aldo_mapeamentos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria', 60);
            $table->string('nome_aldo');
            $table->string('potencia')->nullable();
            $table->unsignedBigInteger('id_referencia');
            $table->unique(['categoria', 'nome_aldo']);
        });
    }

    /** @param  array<int, string>  $tipos */
    private function alterarTipos(array $tipos): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $lista = implode(',', array_map(fn ($t) => "'{$t}'", $tipos));
            DB::statement("ALTER TABLE integracao_historicos MODIFY COLUMN tipo ENUM({$lista}) NOT NULL");

            return;
        }

        // SQLite (testes): enum é um CHECK constraint, refeito pelo change().
        Schema::table('integracao_historicos', function (Blueprint $table) use ($tipos) {
            $table->enum('tipo', $tipos)->change();
        });
    }
};
