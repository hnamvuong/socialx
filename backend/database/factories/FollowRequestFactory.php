<?php

namespace Database\Factories;

use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowRequest>
 */
class FollowRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),

            'target_id' => User::factory(),

            'status' => FollowRequest::STATUS_PENDING,
        ];
    }
}
