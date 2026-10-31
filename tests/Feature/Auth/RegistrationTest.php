<?php

namespace Tests\Feature\Auth;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_first_account_becomes_an_active_administrator(): void
    {
        $response = $this->post('/register', $this->registration());

        $this->assertAuthenticated();
        $response->assertRedirect(route('feed', absolute: false));

        $user = User::sole();
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->isActive());
        $this->assertTrue(Conversation::general()->hasParticipant($user));
    }

    public function test_later_accounts_wait_for_bureau_approval(): void
    {
        User::factory()->admin()->create();

        $response = $this->post('/register', $this->registration());

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.pending', absolute: false));

        $user = User::where('email', 'test@example.com')->sole();
        $this->assertSame('membre', $user->role);
        $this->assertSame('pending', $user->status);
    }

    public function test_school_is_required(): void
    {
        $this->post('/register', [...$this->registration(), 'school' => ''])
            ->assertSessionHasErrors('school');

        $this->assertGuest();
    }

    /**
     * @return array<string, string>
     */
    private function registration(): array
    {
        return [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'school' => 'Université de Rennes',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
