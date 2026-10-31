<?php

namespace Tests\Feature\Events;

use App\Events\NotificationCreated;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostLiked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCreatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcasts_the_presented_notification_and_unread_count_on_the_recipients_channel(): void
    {
        $author = User::factory()->active()->create();
        $author->notify(new PostLiked(User::factory()->active()->create(), Post::factory()->for($author)->create()));

        $event = new NotificationCreated($author, $author->notifications()->sole());

        $this->assertSame('private-user.'.$author->uuid, $event->broadcastOn()[0]->name);
        $this->assertSame('notification.created', $event->broadcastAs());
        $this->assertSame(1, $event->broadcastWith()['unread']);
        $this->assertSame('like', $event->broadcastWith()['notification']['kind']);
        $this->assertArrayNotHasKey('post_id', $event->broadcastWith()['notification']);
    }
}
