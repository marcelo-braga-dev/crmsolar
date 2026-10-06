{{-- Só existe na base de demonstração (DemoDadosFicticiosSeeder grava a configuração demo_aviso). --}}
@if($avisoDemo = \App\Models\Config::get('demo_aviso'))
    <div style="margin-bottom: 14px; padding: 6px 10px; border: 1.5px dashed #dc2626; color: #b91c1c; font-size: 10px; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 0.5px;">
        {{ $avisoDemo }}
    </div>
@endif
