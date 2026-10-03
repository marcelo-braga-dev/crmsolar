<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrcamentoInfo extends Model
{
    public $timestamps = false;

    protected $table = 'orcamento_infos';

    protected $fillable = [
        'orcamento_id', 'estrutura_id', 'concessionaria_id',
        'tipo_dimensionamento',
        'consumo', 'consumo_ponta', 'consumo_fora_ponta', 'demanda_contratada',
        'tensao', 'orientacao', 'bloquear_edicao', 'anotacoes_tecnicas',
        // Campos tarifários (todos os grupos)
        'tarifa_kwh', 'tarifa_kwh_ponta', 'tarifa_kwh_fp',
        'tarifa_demanda_ponta', 'tarifa_demanda_fp',
        'valor_conta_mensal', 'fases', 'disponibilidade_kwh',
        'objetivo_percentual', 'percentual_autoconsumo',
        // Grupo A
        'demanda_ponta_kw', 'demanda_fora_ponta_kw', 'subgrupo_tensao',
        // B2 Rural
        'tipo_instalacao', 'possui_bombeamento', 'consumo_bombeamento',
        // B3 / Grupo A comercial
        'horario_funcionamento',
        // Resultados
        'analise_economica', 'metadados',
    ];

    protected $casts = [
        'consumo' => 'decimal:2',
        'consumo_ponta' => 'decimal:2',
        'consumo_fora_ponta' => 'decimal:2',
        'demanda_contratada' => 'decimal:2',
        'demanda_ponta_kw' => 'decimal:2',
        'demanda_fora_ponta_kw' => 'decimal:2',
        'tarifa_kwh' => 'decimal:5',
        'tarifa_kwh_ponta' => 'decimal:5',
        'tarifa_kwh_fp' => 'decimal:5',
        'tarifa_demanda_ponta' => 'decimal:2',
        'tarifa_demanda_fp' => 'decimal:2',
        'valor_conta_mensal' => 'decimal:2',
        'consumo_bombeamento' => 'decimal:2',
        'possui_bombeamento' => 'boolean',
        'bloquear_edicao' => 'boolean',
        'analise_economica' => 'array',
        'metadados' => 'array',
    ];

    /** @return BelongsTo<Orcamento, $this> */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }

    /** @return BelongsTo<Estrutura, $this> */
    public function estrutura(): BelongsTo
    {
        return $this->belongsTo(Estrutura::class);
    }

    /** @return BelongsTo<Concessionaria, $this> */
    public function concessionaria(): BelongsTo
    {
        return $this->belongsTo(Concessionaria::class);
    }
}
