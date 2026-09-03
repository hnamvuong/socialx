<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'follower_id' => User::factory(),

            'following_id' => User::factory(),
        ];
    }
}
