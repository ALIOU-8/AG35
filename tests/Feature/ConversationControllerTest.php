<?php

namespace Tests\Feature;

use App\Http\Controllers\ConversationController;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_the_members_conversations(): void
    {
        $me = User::factory()->active()->create();
        Conversation::factory()->create(['name' => 'Equipe Foot'])->participants()->attach($me->id);
        Conversation::factory()->create(['name' => 'Groupe Prive']);

        $this->actingAs($me)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('Equipe Foot')
            ->assertDontSee('Groupe Prive');
    }

    public function test_direct_message_reuses_the_existing_conversation(): void
    {
        $me = User::factory()->active()->create();
        $other = User::factory()->active()->create();

        $this->actingAs($me)->post(route('conversations.direct', $other))->assertRedirect();
        $this->actingAs($other)->post(route('conversations.direct', $me))->assertRedirect();

        $conversation = Conversation::where('type', 'direct')->sole();
        $this->assertEqualsCanonicalizing([$me->id, $other->id], $conversation->participants()->pluck('users.id')->all());
    }

    public function test_cannot_start_a_conversation_with_a_pending_member(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->post(route('conversations.direct', User::factory()->create()))
            ->assertNotFound();
    }

    public function test_cannot_start_a_conversation_with_yourself(): void
    {
        $me = User::factory()->active()->create();

        $this->actingAs($me)->post(route('conversations.direct', $me))->assertNotFound();
    }

    public function test_non_participant_gets_404_on_a_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $this->actingAs(User::factory()->active()->create())
            ->get(route('conversations.show', $conversation))
            ->assertNotFound();
    }

    public function test_opening_a_conversation_shows_messages_and_marks_it_read(): void
    {
        $me = User::factory()->active()->create();
        $other = User::factory()->active()->create(['name' => 'Saliou Diallo']);
        $conversation = Conversation::factory()->direct()->create();
        $conversation->participants()->attach([$me->id, $other->id]);
        $message = $conversation->messages()->make(['body' => 'Salut, tu as le cours ?']);
        $message->user()->associate($other)->save();

        $this->actingAs($me)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Saliou Diallo')
            ->assertSee('Salut, tu as le cours ?', false);

        $this->assertNotNull($conversation->participants()->whereKey($me->id)->first()->pivot->last_read_at);
    }

    public function test_group_is_created_with_the_creator_and_chosen_active_members_only(): void
    {
        $me = User::factory()->active()->create();
        $friend = User::factory()->active()->create();
        $pending = User::factory()->create();

        $this->actingAs($me)
            ->post(route('conversations.store'), [
                'name' => 'Révisions L3',
                'members' => [encrypt($friend->id), encrypt($pending->id), 'tampered-value'],
            ])
            ->assertRedirect();

        $group = Conversation::where('type', 'group')->firstOrFail();
        $this->assertSame('Révisions L3', $group->name);
        $this->assertEqualsCanonicalizing([$me->id, $friend->id], $group->participants()->pluck('users.id')->all());
    }

    public function test_group_without_any_valid_member_is_rejected(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->post(route('conversations.store'), ['name' => 'Vide', 'members' => ['tampered-value']])
            ->assertSessionHasErrors(['members' => 'Choisissez au moins un membre.']);

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_member_can_leave_a_group(): void
    {
        $me = User::factory()->active()->create();
        $group = Conversation::factory()->create();
        $group->participants()->attach($me->id);

        $this->actingAs($me)
            ->post(route('conversations.leave', $group))
            ->assertRedirect(route('conversations.index'));

        $this->assertFalse($group->hasParticipant($me));
    }

    public function test_member_cannot_leave_a_direct_conversation(): void
    {
        $me = User::factory()->active()->create();
        $conversation = Conversation::factory()->direct()->create();
        $conversation->participants()->attach($me->id);

        $this->actingAs($me)
            ->post(route('conversations.leave', $conversation))
            ->assertNotFound();
    }

    public function test_sidebar_counts_unread_messages_from_others(): void
    {
        $me = User::factory()->active()->create();
        $other = User::factory()->active()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$me->id, $other->id]);
        foreach ([$other, $other, $me] as $author) {
            $conversation->messages()->make(['body' => 'Coucou'])->user()->associate($author)->save();
        }

        $this->assertSame(2, ConversationController::sidebar($me)->first()['unread']);
    }
}
