<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LdapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        $this->withoutVite();
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
