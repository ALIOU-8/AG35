<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('feed'))->assertRedirect(route('login'));
    }

    public function test_pending_member_is_redirected_to_the_pending_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('feed'))
            ->assertRedirect(route('account.pending'));
    }

    public function test_suspended_member_sees_the_suspension_notice(): void
    {
        $this->actingAs(User::factory()->suspended()->create())
            ->get(route('account.pending'))
            ->assertOk()
            ->assertSee('Compte suspendu');
    }

    public function test_active_member_sees_posts_comments_and_upcoming_meetings(): void
    {
        $member = User::factory()->active()->create();
        $post = Post::factory()->create(['body' => 'Qui vient au match samedi ?']);
        $comment = $post->comments()->make(['body' => 'Moi, avec plaisir']);
        $comment->user()->associate($member)->save();
        $post->likers()->attach($member->id);
        Meeting::factory()->create(['title' => 'Réunion de rentrée']);

        $this->actingAs($member)
            ->get(route('feed'))
            ->assertOk()
            ->assertSee('Qui vient au match samedi ?')
            ->assertSee('Moi, avec plaisir')
            ->assertSee('Réunion de rentrée');
    }

    public function test_feed_reminds_a_member_whose_dues_are_unpaid(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));

        $this->actingAs(User::factory()->active()->create())
            ->get(route('feed'))
            ->assertOk()
            ->assertSee("Votre cotisation 2026-2027 n'est pas encore réglée", false)
            ->assertSee('Reste à régler : 20,00 €');
    }

    public function test_feed_has_no_dues_banner_once_paid(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31));
        $member = User::factory()->active()->create();
        $member->membershipFees()->make(['season' => '2026-2027', 'amount' => 20, 'paid_on' => now(), 'method' => 'Espèces'])->save();

        $this->actingAs($member)
            ->get(route('feed'))
            ->assertOk()
            ->assertDontSee('pas encore réglée');
    }

    public function test_ended_meetings_are_not_listed_in_the_feed_sidebar(): void
    {
        Meeting::factory()->ended()->create(['title' => 'Ancienne réunion']);

        $this->actingAs(User::factory()->active()->create())
            ->get(route('feed'))
            ->assertOk()
            ->assertDontSee('Ancienne réunion');
    }
}
