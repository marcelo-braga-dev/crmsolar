<?php

namespace Database\Seeders\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use SplPriorityQueue;

/**
 * Simulação no tempo: cada evento roda com o relógio da aplicação parado na data dele
 * (Carbon::setTestNow) e com o usuário que o executou autenticado. Assim created_at,
 * linha do tempo do orçamento, relógio da etapa do funil e Auditoria (causer + data) saem
 * exatamente como sairiam na operação real — sem ajustar datas depois.
 *
 * Os eventos rodam em ordem cronológica global (ids crescem com o tempo, como na vida
 * real). Eventos com data depois de "agora" não são executados: é isso que deixa o
 * presente com trabalho pendente.
 */
final class Agenda
{
    private const FUSO = 'America/Sao_Paulo';

    /** @var SplPriorityQueue<array{int, int}, array{CarbonImmutable, User|\Closure|null, callable}> */
    private SplPriorityQueue $fila;

    private int $sequencia = 0;

    private ?CarbonImmutable $relogio = null;

    private int $executados = 0;

    private int $futuros = 0;

    public function __construct(private readonly CarbonImmutable $agora)
    {
        $this->fila = new SplPriorityQueue;
    }

    public function agora(): CarbonImmutable
    {
        return $this->agora;
    }

    /** Data do evento em execução (ou "agora", fora da simulação). */
    public function relogio(): CarbonImmutable
    {
        return $this->relogio ?? $this->agora;
    }

    /**
     * @param  User|\Closure(): ?User|null  $ator  quem executa; uma closure é resolvida na hora (ex.: dono atual do orçamento)
     * @param  callable(CarbonImmutable): void  $acao
     */
    public function em(CarbonInterface $quando, User|\Closure|null $ator, callable $acao): void
    {
        $quando = CarbonImmutable::instance($quando)->utc();
        // Nada acontece antes do evento que o originou.
        if ($this->relogio && $quando->lessThan($this->relogio)) {
            $quando = $this->relogio->addMinutes(Sorteio::entre(1, 30));
        }

        // Menor data sai primeiro; empate respeita a ordem de agendamento.
        $this->fila->insert([$quando, $ator, $acao], [-$quando->getTimestamp(), -$this->sequencia++]);
    }

    /** Executa tudo o que já aconteceu; o que está no futuro fica sem executar. */
    public function executar(): void
    {
        try {
            while (! $this->fila->isEmpty()) {
                [$quando, $ator, $acao] = $this->fila->extract();

                if ($quando->greaterThan($this->agora)) {
                    $this->futuros++;

                    continue;
                }

                $this->relogio = $quando;
                Carbon::setTestNow($quando);
                $ator = $ator instanceof \Closure ? $ator() : $ator;
                $ator ? Auth::setUser($ator) : Auth::forgetUser();

                $acao($quando);
                $this->executados++;
            }
        } finally {
            $this->relogio = null;
            Carbon::setTestNow();
            Auth::forgetUser();
        }
    }

    /** @return array{executados: int, futuros: int} */
    public function contagem(): array
    {
        return ['executados' => $this->executados, 'futuros' => $this->futuros];
    }

    /**
     * Horário comercial no fuso de exibição (seg–sex 8h–18h; às vezes sábado de manhã),
     * devolvido em UTC como a aplicação grava.
     */
    public static function expediente(CarbonInterface $dia): CarbonImmutable
    {
        $local = CarbonImmutable::instance($dia)->setTimezone(self::FUSO);

        if ($local->isSunday() || ($local->isSaturday() && ! Sorteio::chance(0.3))) {
            $local = $local->next(CarbonInterface::MONDAY);
            $hora = Sorteio::entre(8, 17);
        } else {
            $hora = $local->isSaturday() ? Sorteio::entre(8, 11) : Sorteio::entre(8, 17);
        }

        return $local->setTime($hora, Sorteio::entre(0, 59), Sorteio::entre(0, 59))->utc();
    }

    /** Dia útil entre $min e $max dias depois de $base, em horário comercial. */
    public static function depois(CarbonInterface $base, int $min, int $max): CarbonImmutable
    {
        return self::expediente(CarbonImmutable::instance($base)->addDays(Sorteio::entre($min, $max)));
    }
}
