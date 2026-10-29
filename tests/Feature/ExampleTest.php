<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_sends_guests_to_admin_login(): void
    {
        $this->get('/')->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
