<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Funil de vendas (Kanban de orçamentos) — ver docs/funil-de-vendas.md.
 * Etapas e motivos padrão são inseridos aqui para a produção recebê-los sem depender de seeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funil_etapas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60);
            $table->string('cor', 7);
            $table->enum('tipo', ['aberta', 'aprovacao', 'ganho', 'perdido']);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->unsignedTinyInteger('probabilidade')->nullable();
            $table->unsignedSmallInteger('sla_dias')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'ativa', 'ordem']);
        });

        Schema::create('motivos_perda', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80);
            $table->boolean('reativavel')->default(false);
            $table->unsignedSmallInteger('reativar_apos_dias')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->foreignId('funil_etapa_id')->nullable()->after('status')->constrained('funil_etapas')->restrictOnDelete();
            $table->timestamp('etapa_entrou_em')->nullable()->after('funil_etapa_id');
            $table->dateTime('proximo_contato_em')->nullable()->after('etapa_entrou_em');
            $table->timestamp('perdido_em')->nullable()->after('proximo_contato_em');
            $table->foreignId('motivo_perda_id')->nullable()->after('perdido_em')->constrained('motivos_perda')->restrictOnDelete();
            $table->text('perda_observacao')->nullable()->after('motivo_perda_id');
            $table->unsignedTinyInteger('tentativas_reativacao')->default(0)->after('perda_observacao');

            $table->index(['status', 'perdido_em']);
            $table->index('proximo_contato_em');
        });

        Schema::table('orcamento_historicos', function (Blueprint $table) {
            $table->string('tipo', 20)->default('status')->after('usuario_id');
            // Eventos comerciais (etapa, contato, perda) não mudam o status operacional.
            $table->enum('status', ['novo', 'aprovando', 'aprovado', 'aprovacao_reprovada', 'instalando', 'finalizado'])
                ->nullable()->change();
        });

        $agora = now();

        DB::table('funil_etapas')->insert(array_map(fn ($e) => $e + ['ativa' => true, 'created_at' => $agora, 'updated_at' => $agora], [
            ['nome' => 'Primeiro contato', 'cor' => '#64748B', 'tipo' => 'aberta', 'ordem' => 1, 'probabilidade' => 10, 'sla_dias' => 2],
            ['nome' => 'Proposta apresentada', 'cor' => '#3B82F6', 'tipo' => 'aberta', 'ordem' => 2, 'probabilidade' => 30, 'sla_dias' => 5],
            ['nome' => 'Visita técnica', 'cor' => '#8B5CF6', 'tipo' => 'aberta', 'ordem' => 3, 'probabilidade' => 50, 'sla_dias' => 7],
            ['nome' => 'Negociação', 'cor' => '#F59E0B', 'tipo' => 'aberta', 'ordem' => 4, 'probabilidade' => 70, 'sla_dias' => 7],
            ['nome' => 'Financiamento', 'cor' => '#06B6D4', 'tipo' => 'aberta', 'ordem' => 5, 'probabilidade' => 80, 'sla_dias' => 15],
            ['nome' => 'Fechamento', 'cor' => '#10B981', 'tipo' => 'aberta', 'ordem' => 6, 'probabilidade' => 90, 'sla_dias' => 5],
            ['nome' => 'Em aprovação', 'cor' => '#6366F1', 'tipo' => 'aprovacao', 'ordem' => 90, 'probabilidade' => 95, 'sla_dias' => 2],
            ['nome' => 'Ganho', 'cor' => '#16A34A', 'tipo' => 'ganho', 'ordem' => 91, 'probabilidade' => 100, 'sla_dias' => null],
            ['nome' => 'Perdido', 'cor' => '#DC2626', 'tipo' => 'perdido', 'ordem' => 92, 'probabilidade' => 0, 'sla_dias' => null],
        ]));

        DB::table('motivos_perda')->insert(array_map(fn ($m) => $m + ['ativo' => true, 'created_at' => $agora, 'updated_at' => $agora], [
            ['nome' => 'Adiou a decisão', 'reativavel' => true, 'reativar_apos_dias' => 60, 'ordem' => 1],
            ['nome' => 'Preço alto', 'reativavel' => true, 'reativar_apos_dias' => 30, 'ordem' => 2],
            ['nome' => 'Sem retorno do cliente', 'reativavel' => true, 'reativar_apos_dias' => 15, 'ordem' => 3],
            ['nome' => 'Crédito/financiamento negado', 'reativavel' => true, 'reativar_apos_dias' => 90, 'ordem' => 4],
            ['nome' => 'Desistiu do projeto', 'reativavel' => true, 'reativar_apos_dias' => 120, 'ordem' => 5],
            ['nome' => 'Fechou com concorrente', 'reativavel' => false, 'reativar_apos_dias' => null, 'ordem' => 6],
            ['nome' => 'Inviabilidade técnica', 'reativavel' => false, 'reativar_apos_dias' => null, 'ordem' => 7],
        ]));

        // Reprovados voltam ao trabalho comercial na primeira etapa; os demais já caem
        // na coluna certa pela regra derivada do status (docs/funil-de-vendas.md, seção 6).
        $primeira = DB::table('funil_etapas')->where('tipo', 'aberta')->orderBy('ordem')->value('id');
        DB::table('orcamentos')->where('status', 'aprovacao_reprovada')->update(['funil_etapa_id' => $primeira]);
        DB::table('orcamentos')->update(['etapa_entrou_em' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orcamento_historicos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropIndex(['status', 'perdido_em']);
            $table->dropIndex(['proximo_contato_em']);
            $table->dropConstrainedForeignId('funil_etapa_id');
            $table->dropConstrainedForeignId('motivo_perda_id');
            $table->dropColumn(['etapa_entrou_em', 'proximo_contato_em', 'perdido_em', 'perda_observacao', 'tentativas_reativacao']);
        });

        Schema::dropIfExists('motivos_perda');
        Schema::dropIfExists('funil_etapas');
    }
};
