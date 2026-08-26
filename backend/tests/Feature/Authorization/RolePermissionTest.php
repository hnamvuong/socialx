<?php

namespace Tests\Feature\Authorization;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_role(): void
    {
        $user = $this->createUser();

        $role = Role::create([
            'name' => 'moderator',
            'display_name' => 'Moderator',
        ]);

        $user->roles()->attach(
            $role->id
        );

        $this->assertTrue(
            $user->hasRole('moderator')
        );

        $this->assertFalse(
            $user->hasRole('admin')
        );
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);
    }

    public function test_user_receives_permission_through_role(): void
    {
        $user = $this->createUser();

        $role = Role::create([
            'name' => 'moderator',
            'display_name' => 'Moderator',
        ]);

        $permission = Permission::create([
            'name' => 'report.review',
            'display_name' => 'Review reports',
        ]);

        $role->permissions()->attach(
            $permission->id
        );

        $user->roles()->attach(
            $role->id
        );

        $this->assertTrue(
            $user->hasPermission(
                'report.review'
            )
        );

        $this->assertFalse(
            $user->hasPermission(
                'user.ban'
            )
        );
    }

    public function test_permission_allows_gate(): void
    {
        $user = $this->createUser();

        $role = Role::create([
            'name' => 'moderator',
            'display_name' => 'Moderator',
        ]);

        $permission = Permission::create([
            'name' => 'report.review',
            'display_name' => 'Review reports',
        ]);

        $role->permissions()->attach(
            $permission->id
        );

        $user->roles()->attach(
            $role->id
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'report.review'
            )
        );
    }

    public function test_admin_bypasses_permission_checks(): void
    {
        $user = $this->createUser();

        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
        ]);

        $user->roles()->attach(
            $admin->id
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'permission-that-does-not-exist'
            )
        );
    }
}
