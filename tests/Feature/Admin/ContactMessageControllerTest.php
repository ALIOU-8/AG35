<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_the_inbox_shows_new_messages_once_and_marks_them_read(): void
    {
        $message = ContactMessage::create(['name' => 'Aminata', 'email' => 'aminata@example.com', 'subject' => 'Partenariat', 'body' => 'Bonjour']);
        $bureau = User::factory()->bureau()->create();

        $this->actingAs($bureau)
            ->get(route('admin.contacts.index'))
            ->assertOk()
            ->assertSee('Partenariat')
            ->assertSee('Nouveau');

        $this->assertNotNull($message->fresh()->read_at);

        $this->actingAs($bureau)
            ->get(route('admin.contacts.index'))
            ->assertOk()
            ->assertDontSee('Nouveau');
    }

    public function test_bureau_deletes_a_message(): void
    {
        $message = ContactMessage::create(['name' => 'Spam', 'email' => 'spam@example.com', 'subject' => 'Promo', 'body' => '…']);

        $this->actingAs(User::factory()->bureau()->create())
            ->delete(route('admin.contacts.destroy', $message))
            ->assertRedirect();

        $this->assertModelMissing($message);
    }
}
