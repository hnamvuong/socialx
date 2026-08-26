<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'user',
            'display_name' => 'User',
        ]);
    }

    public function test_verification_email_is_sent_after_register(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $user = User::query()
            ->where(
                'email',
                'alice@example.com'
            )
            ->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_email_can_be_verified(): void
    {
        Event::fake([
            Verified::class,
        ]);

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $verificationUrl =
            URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->id,
                    'hash' => sha1(
                        $user->getEmailForVerification()
                    ),
                ]
            );

        $this
            ->getJson($verificationUrl)
            ->assertOk();

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );

        Event::assertDispatched(
            Verified::class
        );
    }

    public function test_invalid_signature_cannot_verify_email(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1(
                    $user->getEmailForVerification()
                ),
            ]
        );

        $url = str_replace(
            '/'.$user->id.'/',
            '/999999/',
            $url
        );

        $this
            ->getJson($url)
            ->assertForbidden();

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_unverified_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $token = $user
            ->createToken('test-token')
            ->plainTextToken;

        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->postJson(
                '/api/auth/email/verification-notification'
            )
            ->assertOk();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_verified_user_does_not_receive_another_verification_email(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $user->markEmailAsVerified();

        $token = $user
            ->createToken('test-token')
            ->plainTextToken;

        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->postJson(
                '/api/auth/email/verification-notification'
            )
            ->assertOk();

        Notification::assertNothingSent();
    }
}
