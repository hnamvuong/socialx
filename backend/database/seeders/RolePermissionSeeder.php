<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'post.create' => 'Create posts',
            'post.delete.own' => 'Delete own posts',
            'report.create' => 'Create reports',

            'user.suspend' => 'Suspend users',
            'report.review' => 'Review reports',

            'user.ban' => 'Ban users',
            'user.restore' => 'Restore users',
            'post.delete.any' => 'Delete any post',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name],
                ['display_name' => $displayName]
            );
        }

        $userRole = Role::updateOrCreate(
            ['name' => 'user'],
            ['display_name' => 'User']
        );

        $moderatorRole = Role::updateOrCreate(
            ['name' => 'moderator'],
            ['display_name' => 'Moderator']
        );

        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator']
        );

        $userRole->permissions()->sync(
            Permission::query()
                ->whereIn('name', [
                    'post.create',
                    'post.delete.own',
                    'report.create',
                ])
                ->pluck('id')
        );

        $moderatorRole->permissions()->sync(
            Permission::query()
                ->whereIn('name', [
                    'post.create',
                    'post.delete.own',
                    'report.create',
                    'user.suspend',
                    'report.review',
                ])
                ->pluck('id')
        );

        $adminRole->permissions()->sync(
            Permission::query()
                ->pluck('id')
        );
    }
}
