<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_raiz_redireciona_para_o_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_tela_de_login_carrega(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_health_check_responde(): void
    {
        $this->get('/up')->assertOk();
    }
}
