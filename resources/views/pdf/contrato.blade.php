<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Contrato #{{ $contrato->id }}</title>
    <style>
        @page { margin: 28px 36px; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }

        .header { width: 100%; border-bottom: 2px solid #2563EB; padding-bottom: 10px; margin-bottom: 16px; }
        .header table { width: 100%; }
        .empresa-nome { font-size: 18px; font-weight: bold; color: #0F172A; }
        .doc-title { text-align: right; }
        .doc-title .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .doc-title .numero { font-size: 20px; font-weight: bold; color: #2563EB; }
        .doc-title .data { font-size: 9.5px; color: #64748b; margin-top: 2px; }

        h2.section {
            font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;
            color: #2563EB; margin: 18px 0 8px; padding-bottom: 3px; border-bottom: 1px solid #e2e8f0;
        }

        .grid { width: 100%; }
        .grid td { padding: 3px 0; vertical-align: top; width: 50%; }
        .grid .k { color: #64748b; display: block; font-size: 9px; text-transform: uppercase; }
        .grid .v { color: #0f172a; font-weight: bold; }

        .clausula { text-align: justify; margin-bottom: 8px; }
        .clausula b { color: #0f172a; }

        .assinaturas { margin-top: 50px; width: 100%; }
        .assinaturas td { text-align: center; padding-top: 30px; width: 50%; }
        .linha-assinatura { border-top: 1px solid #334155; margin: 0 24px; padding-top: 4px; font-size: 9.5px; color: #64748b; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 8.5px; color: #94a3b8; }
    </style>
</head>
<body>
    @include('pdf._aviso_demo')

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="empresa-nome">{{ \App\Models\Config::get('empresa_nome', config('app.name')) }}</div>
                </td>
                <td class="doc-title">
                    <div class="label">Contrato de Instalação Fotovoltaica</div>
                    <div class="numero">#{{ $contrato->id }}</div>
                    <div class="data">Gerado em {{ $contrato->created_at->format('d/m/Y') }} · Orçamento #{{ $contrato->orcamento_id }}</div>
                </td>
            </tr>
        </table>
    </div>

    <h2 class="section">Partes</h2>
    <table class="grid">
        <tr>
            <td>
                <span class="k">Contratante</span>
                <span class="v">{{ $contrato->nome_cliente }}</span>
            </td>
            <td>
                <span class="k">CPF/CNPJ</span>
                <span class="v">{{ $contrato->documento_cliente }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="k">Endereço de Instalação</span>
                <span class="v">{{ $contrato->endereco_instalacao }}</span>
            </td>
        </tr>
    </table>

    <h2 class="section">Objeto do Contrato</h2>
    <p class="clausula">
        O presente contrato tem como objeto o fornecimento e a instalação de sistema de geração de energia
        fotovoltaica com potência de <b>{{ number_format((float) $contrato->potencia_kwp, 2, ',', '.') }} kWp</b>,
        composto por {{ $contrato->qtd_paineis }} painel(éis) solar(es) e {{ $contrato->qtd_inversores }}
        inversor(es) modelo <b>{{ $contrato->modelo_inversor }}</b>, no endereço acima indicado.
    </p>

    <table class="grid">
        <tr>
            <td>
                <span class="k">Consumo Médio Mensal</span>
                <span class="v">{{ number_format($contrato->consumo_mensal, 0, ',', '.') }} kWh</span>
            </td>
            <td>
                <span class="k">Geração Estimada</span>
                <span class="v">{{ number_format($contrato->geracao_estimada, 0, ',', '.') }} kWh/mês</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="k">Garantia dos Painéis</span>
                <span class="v">{{ $contrato->garantia_paineis }}</span>
            </td>
            <td>
                <span class="k">Garantia dos Inversores</span>
                <span class="v">{{ $contrato->garantia_inversores }}</span>
            </td>
        </tr>
    </table>

    <h2 class="section">Valor e Forma de Pagamento</h2>
    <p class="clausula">
        O valor total dos serviços e equipamentos objeto deste contrato é de
        <b>R$ {{ number_format((float) $contrato->valor_total, 2, ',', '.') }}</b>, a ser pago conforme
        condições abaixo:
    </p>
    <p class="clausula" style="white-space: pre-wrap;">{{ $contrato->formas_pagamento }}</p>

    @if($contrato->clausulas_adicionais)
        <h2 class="section">Cláusulas Adicionais</h2>
        <p class="clausula" style="white-space: pre-wrap;">{{ $contrato->clausulas_adicionais }}</p>
    @endif

    <table class="assinaturas">
        <tr>
            <td>
                <div class="linha-assinatura">{{ $contrato->nome_cliente }}<br>Contratante</div>
            </td>
            <td>
                <div class="linha-assinatura">{{ $contrato->consultor?->name ?? \App\Models\Config::get('empresa_nome', config('app.name')) }}<br>Contratada</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento gerado eletronicamente em {{ now()->format('d/m/Y H:i') }} · Contrato #{{ $contrato->id }} · Status: {{ ucfirst($contrato->status) }}
    </div>

</body>
</html>
