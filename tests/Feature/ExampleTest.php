<?php

namespace Tests\Feature;

class ExampleTest extends FeatureTestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
