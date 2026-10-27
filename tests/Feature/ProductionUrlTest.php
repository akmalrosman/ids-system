<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class ProductionUrlTest extends TestCase
{
    public function test_production_builds_urls_from_app_url_including_the_port(): void
    {
        // Docker forwards the server's port 8080 to port 80, so the request only knows "localhost:80".
        config(['app.url' => 'http://10.65.200.30:8080']);
        $this->app->detectEnvironment(fn () => 'production');
        (new AppServiceProvider($this->app))->boot();

        $this->get('http://localhost/up')->assertOk();

        $this->assertSame('http://10.65.200.30:8080/login', route('login'));
        $this->assertSame('http://10.65.200.30:8080/favicon.svg', asset('favicon.svg'));
    }

    public function test_local_development_still_uses_the_request_url(): void
    {
        config(['app.url' => 'http://10.65.200.30:8080']);

        $this->get('http://127.0.0.1:8000/up')->assertOk();

        $this->assertSame('http://127.0.0.1:8000/login', route('login'));
    }
}
