<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Testes não dependem de `npm run build` (o CI do backend não gera public/build).
        $this->withoutVite();
    }
}
