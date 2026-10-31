<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_subscription_is_saved_for_the_member(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs($member)
            ->postJson(route('push-subscriptions.store'), [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
                'contentEncoding' => 'aes128gcm',
            ])
            ->assertCreated();

        $subscription = $member->pushSubscriptions()->sole();
        $this->assertSame('https://fcm.googleapis.com/fcm/send/abc123', $subscription->endpoint);
        $this->assertSame('public-key', $subscription->public_key);
        $this->assertSame('auth-token', $subscription->auth_token);
    }

    #[TestWith(['https://169.254.169.254/latest/meta-data'])]
    #[TestWith(['https://intranet.local/push'])]
    #[TestWith(['http://fcm.googleapis.com/fcm/send/abc'])]
    public function test_endpoints_outside_browser_push_services_are_rejected(string $endpoint): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->postJson(route('push-subscriptions.store'), ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'k', 'auth' => 'a']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_device_can_unsubscribe(): void
    {
        $member = User::factory()->active()->create();
        $member->updatePushSubscription('https://web.push.apple.com/abc', 'key', 'token');

        $this->actingAs($member)
            ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => 'https://web.push.apple.com/abc'])
            ->assertOk();

        $this->assertSame(0, $member->pushSubscriptions()->count());
    }
}
