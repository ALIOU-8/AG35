<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_upcoming_and_past_meetings(): void
    {
        Meeting::factory()->create(['title' => 'Réunion du bureau']);
        Meeting::factory()->ended()->create(['title' => 'Assemblée générale 2025']);

        $this->actingAs(User::factory()->active()->create())
            ->get(route('meetings.index'))
            ->assertOk()
            ->assertSeeInOrder(['Réunion du bureau', 'Réunions passées', 'Assemblée générale 2025']);
    }

    public function test_scheduled_meeting_is_saved_for_its_creator(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs($member)
            ->post(route('meetings.store'), ['title' => 'Révisions', 'scheduled_at' => '2026-11-20 18:30'])
            ->assertRedirect(route('meetings.index'));

        $this->assertDatabaseHas('meetings', ['title' => 'Révisions', 'created_by' => $member->id, 'ended_at' => null]);
        $this->assertSame('2026-11-20 18:30', Meeting::firstOrFail()->scheduled_at->format('Y-m-d H:i'));
    }

    public function test_new_meeting_notifies_active_members_except_its_creator(): void
    {
        $creator = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $pending = User::factory()->create();

        $this->actingAs($creator)->post(route('meetings.store'), ['title' => 'Assemblée générale', 'scheduled_at' => '2026-11-20 18:30']);

        $notification = $member->notifications()->sole();
        $this->assertSame('a planifié une réunion : Assemblée générale', $notification->data['text']);
        $this->assertSame('Vendredi 20 novembre à 18:30', $notification->data['excerpt']);
        $this->assertSame(0, $pending->notifications()->count());
        $this->assertSame(0, $creator->notifications()->count());
    }

    public function test_deleting_a_meeting_withdraws_its_notifications(): void
    {
        $creator = User::factory()->active()->create();
        $member = User::factory()->active()->create();
        $this->actingAs($creator)->post(route('meetings.store'), ['title' => 'Point rapide', 'start_now' => '1']);

        $this->actingAs($creator)->delete(route('meetings.destroy', Meeting::sole()))->assertRedirect();

        $this->assertSame(0, $member->notifications()->count());
    }

    public function test_scheduled_meeting_requires_a_date(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->post(route('meetings.store'), ['title' => 'Sans date'])
            ->assertSessionHasErrors(['scheduled_at' => "Indiquez la date et l'heure de la réunion."]);
    }

    public function test_start_now_opens_the_room_immediately(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->post(route('meetings.store'), ['title' => 'Point rapide', 'start_now' => '1'])
            ->assertRedirect();

        $meeting = Meeting::firstOrFail();
        $this->assertNull($meeting->scheduled_at);
        $this->assertSame('open', $meeting->status());
    }

    public function test_room_renders_with_public_identifiers_only(): void
    {
        $member = User::factory()->active()->create();
        $meeting = Meeting::factory()->create();

        $this->actingAs($member)
            ->get(route('meetings.show', $meeting))
            ->assertOk()
            ->assertViewHas('config', fn (array $config) => $config['channel'] === $meeting->uuid
                && $config['me'] === $member->toPublicArray()
                && $config['canEnd'] === false);
    }

    public function test_room_embeds_jitsi_when_it_is_the_configured_driver(): void
    {
        config(['visio.driver' => 'jitsi', 'visio.jitsi_domain' => 'visio.ag35.fr']);

        $this->actingAs(User::factory()->active()->create())
            ->get(route('meetings.show', Meeting::factory()->create()))
            ->assertOk()
            ->assertSee('jitsiRoom', false)
            ->assertDontSee('meetingRoom', false);
    }

    public function test_ended_meeting_redirects_to_the_list_with_an_error(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->get(route('meetings.show', Meeting::factory()->ended()->create()))
            ->assertRedirect(route('meetings.index'))
            ->assertSessionHas('error', 'Cette réunion est terminée.');
    }

    public function test_member_cannot_end_someone_elses_meeting(): void
    {
        $meeting = Meeting::factory()->create();

        $this->actingAs(User::factory()->active()->create())
            ->postJson(route('meetings.end', $meeting))
            ->assertForbidden();

        $this->assertFalse($meeting->fresh()->isEnded());
    }

    public function test_creator_ends_the_meeting(): void
    {
        $creator = User::factory()->active()->create();
        $meeting = Meeting::factory()->create(['created_by' => $creator->id]);

        $this->actingAs($creator)
            ->postJson(route('meetings.end', $meeting))
            ->assertOk()
            ->assertExactJson(['ended' => true]);

        $this->assertTrue($meeting->fresh()->isEnded());
    }

    public function test_bureau_member_can_delete_any_meeting(): void
    {
        $meeting = Meeting::factory()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->delete(route('meetings.destroy', $meeting))
            ->assertRedirect(route('meetings.index'));

        $this->assertModelMissing($meeting);
    }
}
