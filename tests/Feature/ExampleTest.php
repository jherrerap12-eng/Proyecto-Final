<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_public_homepage_is_available_without_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Babybu Toys')
            ->assertSee('Iniciar sesión')
            ->assertSee('Mira nuestros videos');
    }
}
