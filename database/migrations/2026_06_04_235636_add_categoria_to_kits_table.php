<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            // Tipo do sistema solar
            $table->enum('categoria', ['ongrid', 'offgrid', 'hibrido', 'bomba', 'microinversor'])
                ->default('ongrid')
                ->after('sku');
        });
    }

    public function down(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
