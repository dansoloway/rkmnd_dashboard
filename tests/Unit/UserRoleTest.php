<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_user_role_is_displayed_as_manager(): void
    {
        $this->assertSame('Manager', User::roleLabel(User::ROLE_USER));
        $this->assertSame('Admin', User::roleLabel(User::ROLE_ADMIN));
        $this->assertSame('Analytics only', User::roleLabel(User::ROLE_ANALYTICS));
    }
}
