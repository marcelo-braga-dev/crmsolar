<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Orçamento #{{ $orcamento->id }}</title>
    <style>
        @page { margin: 28px 36px; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1e293b; }

        .header { width: 100%; border-bottom: 2px solid #2563EB; padding-bottom: 10px; margin-bottom: 16px; }
        .header table { width: 100%; }
        .empresa-nome { font-size: 18px; font-weight: bold; color: #0F172A; }
        .empresa-info { color: #64748b; font-size: 9.5px; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title .numero { font-size: 20px; font-weight: bold; color: #2563EB; }
        .doc-title .data { font-size: 9.5px; color: #64748b; margin-top: 2px; }

        .section-title {
            font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;
            color: #2563EB; margin: 16px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0;
        }

        .info-table { width: 100%; }
        .info-table td { padding: 2.5px 0; vertical-align: top; }
        .info-table .k { color: #64748b; width: 130px; }
        .info-table .v { font-weight: bold; color: #0f172a; }

        table.itens { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.itens th {
            background: #F1F5F9; color: #334155; text-align: left; font-size: 9.5px;
            text-transform: uppercase; padding: 7px 8px; border-bottom: 1px solid #cbd5e1;
        }
        table.itens td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; }
        table.itens .num { text-align: right; }
        table.itens .center { text-align: center; }
        .total-row td { border-top: 2px solid #2563EB; font-weight: bold; font-size: 12px; padding-top: 10px; }
        .total-row .total-label { text-align: right; color: #64748b; text-transform: uppercase; font-size: 9px; }
        .total-row .total-valor { text-align: right; color: #15803D; font-size: 15px; }

        .kpi-box { width: 32%; display: inline-block; background: #F8FAFC; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; margin-right: 1.3%; }
        .kpi-box .label { font-size: 8.5px; color: #64748b; text-transform: uppercase; }
        .kpi-box .valor { font-size: 15px; font-weight: bold; color: #0f172a; margin-top: 2px; }

        .anotacoes { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 6px; padding: 10px 12px; font-size: 10.5px; white-space: pre-wrap; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>
    @include('pdf._aviso_demo')

    <div class="header">
        <table>
            <tr>
                <td>
                    @if($logoPdf = \App\Services\IdentidadeVisual::logoParaPdf())
                        <img src="{{ $logoPdf }}" alt="" style="max-height: 46px; max-width: 190px; margin-bottom: 6px;"><br>
                    @endif
                    <div class="empresa-nome">{{ \App\Models\Config::get('empresa_nome', config('app.name')) }}</div>
                    <div class="empresa-info">
                        @if($tel = \App\Models\Config::get('empresa_telefone'))
                            {{ $tel }}
                        @endif
                        @if($email = \App\Models\Config::get('empresa_email'))
                            &nbsp;·&nbsp;{{ $email }}
                        @endif
                    </div>
                </td>
                <td class="doc-title">
                    <div class="label">Proposta Comercial</div>
                    <div class="numero">#{{ $orcamento->id }}</div>
                    <div class="data">Emitido em {{ $orcamento->created_at->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Cliente</div>
    <table class="info-table">
        <tr>
            <td class="k">Nome</td>
            <td class="v">
                {{ $orcamento->cliente?->tipo_pessoa === 'pj' ? $orcamento->cliente?->razao_social : $orcamento->cliente?->nome }}
            </td>
            <td class="k">Documento</td>
            <td class="v">{{ $orcamento->cliente?->tipo_pessoa === 'pj' ? $orcamento->cliente?->cnpj : $orcamento->cliente?->cpf }}</td>
        </tr>
        <tr>
            <td class="k">Contato</td>
            <td class="v">{{ $orcamento->cliente?->celular ?? $orcamento->cliente?->telefone ?? '—' }}</td>
            <td class="k">Cidade</td>
            <td class="v">{{ $orcamento->cidade ? "{$orcamento->cidade->cidade} / {$orcamento->cidade->estado}" : '—' }}</td>
        </tr>
    </table>

    @php
        $kitItem = $orcamento->itens->firstWhere('tipo', 'kit');
        $potenciaKwp = $kitItem?->metadados['potencia_kwp'] ?? null;
    @endphp
    <div class="section-title">Resumo do Sistema</div>
    <div>
        <div class="kpi-box">
            <div class="label">Potência do Sistema</div>
            <div class="valor">{{ $potenciaKwp ? number_format($potenciaKwp, 2, ',', '.') . ' kWp' : '—' }}</div>
        </div>
        <div class="kpi-box">
            <div class="label">Geração Estimada</div>
            <div class="valor">{{ number_format($orcamento->geracao_estimada ?? 0, 0, ',', '.') }} kWh/mês</div>
        </div>
        <div class="kpi-box">
            <div class="label">Investimento Total</div>
            <div class="valor">R$ {{ number_format((float) $orcamento->preco_total, 2, ',', '.') }}</div>
        </div>
    </div>

    <table class="info-table" style="margin-top: 10px;">
        <tr>
            <td class="k">Tipo de Sistema</td>
            <td class="v">{{ $orcamento->info?->tipo_dimensionamento === 'convencional' ? 'Convencional' : 'Por Demanda' }}</td>
            <td class="k">Estrutura</td>
            <td class="v">{{ $orcamento->info?->estrutura?->nome ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Itens da Proposta</div>
    <table class="itens">
        <thead>
            <tr>
                <th>Descrição</th>
                <th class="center">Qtd.</th>
                <th class="num">Valor Unit.</th>
                <th class="num">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orcamento->itens as $item)
                <tr>
                    <td>{{ $item->descricao ?? ucfirst($item->tipo) }}</td>
                    <td class="center">{{ $item->quantidade }}</td>
                    <td class="num">R$ {{ number_format((float) $item->preco_venda_unitario, 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format((float) $item->preco_venda_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="total-label">Total da Proposta</td>
                <td class="total-valor">R$ {{ number_format((float) $orcamento->preco_total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($orcamento->anotacoes)
        <div class="section-title">Observações</div>
        <div class="anotacoes">{{ $orcamento->anotacoes }}</div>
    @endif

    <div class="footer">
        Proposta gerada por {{ $orcamento->consultor?->name ?? 'consultor' }}
        @if($orcamento->consultor?->email) ({{ $orcamento->consultor->email }}) @endif
        em {{ now()->format('d/m/Y H:i') }} · Ref. {{ substr($orcamento->token, 0, 12) }}
    </div>

</body>
</html>
