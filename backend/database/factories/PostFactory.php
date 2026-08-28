<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'content' => fake()->sentence(),
        ];
    }

    public function replyTo(
        Post $parent
    ): static {
        return $this->state(
            fn () => [
                'parent_post_id' => $parent->id,
                'root_post_id' => $parent->root_post_id
                    ?? $parent->id,
            ]
        );
    }
}
