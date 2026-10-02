<?php

namespace Tests\Feature\Consultor;

use App\Models\CidadeEstado;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Orçamentos só podem ser calculados/criados para clientes do próprio consultor.
 */
class ClienteEscopoOrcamentoTest extends TestCase
{
    use RefreshDatabase;

    private function consultor(string $email): User
    {
        return User::create([
            'name' => 'Consultor '.$email, 'email' => $email,
            'password' => 'senha', 'tipo' => 'consultor', 'status' => true,
        ]);
    }

    /** @return array<string, array{string, array<int, string>}> */
    public static function rotas(): array
    {
        return [
            'B1 calcular' => ['consultor.grupo.b1.calcular', []],
            'B1 store' => ['consultor.grupo.b1.store', []],
            'B2 calcular' => ['consultor.grupo.b2.calcular', []],
            'B2 store' => ['consultor.grupo.b2.store', []],
            'B3 calcular' => ['consultor.grupo.b3.calcular', []],
            'B3 store' => ['consultor.grupo.b3.store', []],
            'A4 calcular' => ['consultor.grupo.a.calcular', ['A4']],
            'A4 store' => ['consultor.grupo.a.store', ['A4']],
            'Convencional store' => ['consultor.dimensionamento.convencional.store', []],
            'Demanda store' => ['consultor.dimensionamento.demanda.store', []],
        ];
    }

    /** @param  array<int, string>  $params */
    #[DataProvider('rotas')]
    public function test_rejeita_cliente_de_outro_consultor(string $rota, array $params): void
    {
        $dono = $this->consultor('dono@teste.com');
        $outro = $this->consultor('outro@teste.com');
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        $clienteAlheio = Cliente::create([
            'consultor_id' => $dono->id, 'cidade_id' => $cidade->id,
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente do Dono', 'status' => 'novo',
        ]);

        $this->actingAs($outro)
            ->postJson(route($rota, $params), ['cliente_id' => $clienteAlheio->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cliente_id');
    }

    /** @param  array<int, string>  $params */
    #[DataProvider('rotas')]
    public function test_aceita_cliente_do_proprio_consultor(string $rota, array $params): void
    {
        $dono = $this->consultor('dono@teste.com');
        $cidade = CidadeEstado::create(['cidade' => 'Fortaleza', 'estado' => 'Ceará', 'sigla' => 'CE']);
        $cliente = Cliente::create([
            'consultor_id' => $dono->id, 'cidade_id' => $cidade->id,
            'tipo_pessoa' => 'pf', 'nome' => 'Cliente Próprio', 'status' => 'novo',
        ]);

        // Os outros campos obrigatórios faltam de propósito: só interessa que cliente_id passe.
        $this->actingAs($dono)
            ->postJson(route($rota, $params), ['cliente_id' => $cliente->id])
            ->assertUnprocessable()
            ->assertJsonMissingValidationErrors('cliente_id');
    }
}
