<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_sends_a_message_and_gets_the_public_chat_payload(): void
    {
        [$me, $conversation] = $this->conversationWithMe();

        $this->actingAs($me)
            ->postJson(route('messages.store', $conversation), ['body' => 'Bonsoir !'])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Bonsoir !')
            ->assertJsonPath('message.conversation', $conversation->uuid)
            ->assertJsonPath('message.author.uuid', $me->uuid)
            ->assertJsonMissingPath('message.id')
            ->assertJsonMissingPath('message.author.id');

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'user_id' => $me->id, 'body' => 'Bonsoir !']);
        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_message_is_pushed_only_to_other_participants_with_a_subscribed_device(): void
    {
        config(['webpush.vapid.public_key' => 'public', 'webpush.vapid.private_key' => 'private']);
        Notification::fake();
        $me = User::factory()->active()->create();
        $me->updatePushSubscription('https://fcm.googleapis.com/fcm/send/me', 'key', 'token');
        $subscribed = User::factory()->active()->create();
        $subscribed->updatePushSubscription('https://fcm.googleapis.com/fcm/send/other', 'key', 'token');
        $unsubscribed = User::factory()->active()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$me->id, $subscribed->id, $unsubscribed->id]);

        $this->actingAs($me)->postJson(route('messages.store', $conversation), ['body' => 'Rendez-vous à 18 h'])->assertCreated();

        Notification::assertSentTo($subscribed, NewMessage::class, fn (NewMessage $notification, array $channels) => $channels === [WebPushChannel::class]
            && $notification->message->body === 'Rendez-vous à 18 h');
        Notification::assertNotSentTo($unsubscribed, NewMessage::class);
        Notification::assertNotSentTo($me, NewMessage::class);
    }

    public function test_attachment_is_stored_and_exposed_with_its_name(): void
    {
        Storage::fake('public');
        [$me, $conversation] = $this->conversationWithMe();

        $response = $this->actingAs($me)
            ->postJson(route('messages.store', $conversation), ['attachment' => UploadedFile::fake()->create('cours.pdf', 200, 'application/pdf')])
            ->assertCreated()
            ->assertJsonPath('message.attachment.name', 'cours.pdf')
            ->assertJsonPath('message.attachment.isImage', false);

        Storage::disk('public')->assertExists(Message::firstOrFail()->attachment_path);
        $this->assertNotEmpty($response->json('message.attachment.url'));
    }

    public function test_empty_message_is_rejected(): void
    {
        [$me, $conversation] = $this->conversationWithMe();

        $this->actingAs($me)
            ->postJson(route('messages.store', $conversation), ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body' => 'Le message est vide.']);
    }

    public function test_non_participant_cannot_post_or_read_messages(): void
    {
        $conversation = Conversation::factory()->create();
        $stranger = User::factory()->active()->create();

        $this->actingAs($stranger)->postJson(route('messages.store', $conversation), ['body' => 'Intrus'])->assertNotFound();
        $this->actingAs($stranger)->getJson(route('messages.poll', $conversation))->assertNotFound();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_pending_member_gets_403_on_json_requests(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('messages.store', Conversation::factory()->create()), ['body' => 'Bonjour'])
            ->assertForbidden();
    }

    public function test_poll_returns_only_messages_after_the_given_uuid(): void
    {
        [$me, $conversation] = $this->conversationWithMe();
        $seen = $this->messageIn($conversation, $me, 'Déjà lu');
        $this->messageIn($conversation, $me, 'Nouveau');

        $this->actingAs($me)
            ->getJson(route('messages.poll', $conversation).'?after='.$seen->uuid)
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.body', 'Nouveau');
    }

    public function test_older_returns_the_previous_page_and_whether_more_exist(): void
    {
        [$me, $conversation] = $this->conversationWithMe();
        $messages = collect(range(1, 32))->map(fn ($number) => $this->messageIn($conversation, $me, "Message {$number}"));

        $this->actingAs($me)
            ->getJson(route('messages.older', $conversation).'?before='.$messages->last()->uuid)
            ->assertOk()
            ->assertJsonCount(30, 'messages')
            ->assertJsonPath('messages.0.body', 'Message 2')
            ->assertJsonPath('messages.29.body', 'Message 31')
            ->assertJsonPath('hasMore', true);
    }

    /**
     * @return array{0: User, 1: Conversation}
     */
    private function conversationWithMe(): array
    {
        $me = User::factory()->active()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$me->id, User::factory()->active()->create()->id]);

        return [$me, $conversation];
    }

    private function messageIn(Conversation $conversation, User $author, string $body): Message
    {
        $message = $conversation->messages()->make(['body' => $body]);
        $message->user()->associate($author)->save();

        return $message;
    }
}
