<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_user_by_username(): void
    {
        $alice =
            User::factory()
                ->create([
                    'username' => 'alice',

                    'display_name' => 'Alice Nguyen',

                    'status' => 'active',
                ]);

        User::factory()
            ->create([
                'username' => 'bob',

                'display_name' => 'Bob',

                'status' => 'active',
            ]);

        $response =
            $this->getJson(
                '/api/search/users?q=alice'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.users.0.id',
                $alice->id
            )
            ->assertJsonPath(
                'data.users.0.username',
                'alice'
            );
    }

    public function test_it_searches_user_by_display_name(): void
    {
        $user =
            User::factory()
                ->create([
                    'username' => 'alice123',

                    'display_name' => 'Nguyen Alice',

                    'status' => 'active',
                ]);

        $response =
            $this->getJson(
                '/api/search/users?q=Nguyen'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.users.0.id',
                $user->id
            );
    }

    public function test_search_is_case_insensitive(): void
    {
        $user =
            User::factory()
                ->create([
                    'username' => 'Alice',

                    'display_name' => 'Alice',

                    'status' => 'active',
                ]);

        $response =
            $this->getJson(
                '/api/search/users?q=ALICE'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.users.0.id',
                $user->id
            );
    }

    public function test_inactive_users_are_not_returned(): void
    {
        User::factory()
            ->create([
                'username' => 'alice',

                'display_name' => 'Alice',

                'status' => 'inactive',
            ]);

        $response =
            $this->getJson(
                '/api/search/users?q=alice'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.users'
            );
    }

    public function test_exact_username_is_ranked_before_partial_match(): void
    {
        $partial =
            User::factory()
                ->create([
                    'username' => 'alice123',

                    'display_name' => 'Alice 123',

                    'status' => 'active',
                ]);

        $exact =
            User::factory()
                ->create([
                    'username' => 'alice',

                    'display_name' => 'Alice',

                    'status' => 'active',
                ]);

        $response =
            $this->getJson(
                '/api/search/users?q=alice'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.users.0.id',
                $exact->id
            );

        $this->assertNotSame(
            $partial->id,
            $response->json(
                'data.users.0.id'
            )
        );
    }

    public function test_search_returns_at_most_twenty_users(): void
    {
        User::factory()
            ->count(25)
            ->create([
                'display_name' => 'Search Person',

                'status' => 'active',
            ]);

        $response =
            $this->getJson(
                '/api/search/users?q=Search'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.users'
            );
    }

    public function test_query_is_required(): void
    {
        $this
            ->getJson(
                '/api/search/users'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }

    public function test_blank_query_is_rejected(): void
    {
        $this
            ->getJson(
                '/api/search/users?q=%20%20%20'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }
}
