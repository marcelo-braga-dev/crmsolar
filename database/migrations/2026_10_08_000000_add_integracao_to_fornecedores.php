<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A integração encontrava o fornecedor pelo nome ("%edeltec%"). Com a coluna, o nome exibido
 * pode ser qualquer um — na demonstração o nome da distribuidora é confidencial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fornecedores', function (Blueprint $table) {
            $table->string('integracao', 30)->nullable()->unique()->after('nome');
        });

        $id = DB::table('fornecedores')->where('nome', 'like', '%edeltec%')->orderBy('id')->value('id');
        if ($id) {
            DB::table('fornecedores')->where('id', $id)->update(['integracao' => 'distribuidora']);
        }
    }

    public function down(): void
    {
        Schema::table('fornecedores', function (Blueprint $table) {
            $table->dropUnique(['integracao']);
            $table->dropColumn('integracao');
        });
    }
};
