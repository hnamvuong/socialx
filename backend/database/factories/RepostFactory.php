<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repost>
 */
class RepostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'post_id' => Post::factory(),
        ];
    }
}
