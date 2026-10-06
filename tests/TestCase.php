<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Com `php artisan optimize` (config em cache) o phpunit.xml é ignorado: os testes rodariam
     * no MySQL de verdade e o RefreshDatabase apagaria o banco. Aborta antes de qualquer migration.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || $app['config']->get('database.default') !== 'sqlite') {
            fwrite(STDERR, PHP_EOL.'ABORTADO: configuração em cache ou banco diferente de SQLite. Rode `php artisan optimize:clear` antes dos testes.'.PHP_EOL);
            exit(1);
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Testes não dependem de `npm run build` (o CI do backend não gera public/build).
        $this->withoutVite();
    }
}
