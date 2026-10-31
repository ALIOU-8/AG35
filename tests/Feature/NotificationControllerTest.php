<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Notifications\PostLiked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bell_list_returns_presented_notifications_without_internal_references(): void
    {
        $author = User::factory()->active()->create();
        $liker = User::factory()->active()->create(['name' => 'Aïssatou Bah']);
        $author->notify(new PostLiked($liker, Post::factory()->for($author)->create(['body' => 'Photos de la fête'])));

        $response = $this->actingAs($author)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('notifications.0.kind', 'like')
            ->assertJsonPath('notifications.0.actor.name', 'Aïssatou Bah')
            ->assertJsonPath('notifications.0.text', 'aime votre publication')
            ->assertJsonPath('notifications.0.excerpt', 'Photos de la fête')
            ->assertJsonPath('notifications.0.read', false);

        $this->assertSame(['id', 'kind', 'actor', 'text', 'excerpt', 'url', 'time', 'read'], array_keys($response->json('notifications.0')));
        $this->assertSame(['name', 'initials', 'avatar'], array_keys($response->json('notifications.0.actor')));
    }

    public function test_opening_a_notification_marks_it_read_and_goes_to_its_target(): void
    {
        $author = User::factory()->active()->create();
        $post = Post::factory()->for($author)->create();
        $author->notify(new PostLiked(User::factory()->active()->create(), $post));
        $notification = $author->notifications()->sole();

        $this->actingAs($author)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect($notification->data['url']);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_member_cannot_open_another_members_notification(): void
    {
        $author = User::factory()->active()->create();
        $author->notify(new PostLiked(User::factory()->active()->create(), Post::factory()->for($author)->create()));

        $this->actingAs(User::factory()->active()->create())
            ->get(route('notifications.open', $author->notifications()->sole()->id))
            ->assertNotFound();
    }

    public function test_mark_all_as_read_clears_the_unread_count(): void
    {
        $author = User::factory()->active()->create();
        $post = Post::factory()->for($author)->create();
        User::factory(2)->active()->create()->each(fn (User $liker) => $author->notify(new PostLiked($liker, $post)));

        $this->actingAs($author)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertExactJson(['unread' => 0]);

        $this->assertSame(0, $author->unreadNotifications()->count());
    }

    public function test_notifications_page_lists_them_and_the_bell_shows_the_unread_count(): void
    {
        $author = User::factory()->active()->create();
        $author->notify(new PostLiked(User::factory()->active()->create(['name' => 'Moussa Keita']), Post::factory()->for($author)->create()));

        $this->actingAs($author)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Moussa Keita')
            ->assertSee('aime votre publication')
            ->assertSee('unreadNotifications: 1', false);
    }
}
