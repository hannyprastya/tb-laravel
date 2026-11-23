<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Uji tidak butuh CSS/JS hasil build Vite.
        $this->withoutVite();

        // APP_KEY tidak pernah ditulis di repo; uji memakai kunci acak.
        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
        }
    }
}
