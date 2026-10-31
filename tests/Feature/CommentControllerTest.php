<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_comment_is_saved_and_returned_with_a_delete_link(): void
    {
        $member = User::factory()->active()->create(['name' => 'Ibrahima Sow']);
        $post = Post::factory()->create();

        $this->actingAs($member)
            ->postJson(route('comments.store', $post), ['body' => 'Je serai là !'])
            ->assertCreated()
            ->assertJsonPath('comment.body', 'Je serai là !')
            ->assertJsonPath('comment.author.name', 'Ibrahima Sow')
            ->assertJsonMissingPath('comment.author.id')
            ->assertJsonPath('comment.deleteUrl', fn ($url) => is_string($url));

        $this->assertDatabaseHas('comments', ['post_id' => $post->id, 'user_id' => $member->id, 'body' => 'Je serai là !']);
    }

    public function test_empty_comment_is_rejected(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->postJson(route('comments.store', Post::factory()->create()), ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_member_cannot_delete_another_members_comment(): void
    {
        $comment = $this->commentBy(User::factory()->active()->create());

        $this->actingAs(User::factory()->active()->create())
            ->deleteJson(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->assertModelExists($comment);
    }

    public function test_bureau_member_can_delete_any_comment(): void
    {
        $comment = $this->commentBy(User::factory()->active()->create());

        $this->actingAs(User::factory()->bureau()->create())
            ->deleteJson(route('comments.destroy', $comment))
            ->assertOk();

        $this->assertModelMissing($comment);
    }

    public function test_comment_notifies_the_author_and_earlier_commenters_but_not_the_commenter(): void
    {
        $author = User::factory()->active()->create(['name' => 'Fanta Touré']);
        $earlier = User::factory()->active()->create();
        $commenter = User::factory()->active()->create();
        $post = Post::factory()->for($author)->create();
        $post->comments()->make(['body' => 'Premier !'])->user()->associate($earlier)->save();

        $this->actingAs($commenter)->postJson(route('comments.store', $post), ['body' => 'Je viens aussi'])->assertCreated();

        $this->assertSame('a commenté votre publication', $author->notifications()->sole()->data['text']);
        $this->assertSame('a aussi commenté la publication de Fanta Touré', $earlier->notifications()->sole()->data['text']);
        $this->assertSame('Je viens aussi', $earlier->notifications()->sole()->data['excerpt']);
        $this->assertSame(0, $commenter->notifications()->count());
    }

    public function test_deleting_a_comment_withdraws_its_notifications(): void
    {
        $author = User::factory()->active()->create();
        $commenter = User::factory()->active()->create();
        $post = Post::factory()->for($author)->create();
        $this->actingAs($commenter)->postJson(route('comments.store', $post), ['body' => 'Oups']);

        $this->actingAs($commenter)->deleteJson(route('comments.destroy', Comment::sole()))->assertOk();

        $this->assertSame(0, $author->notifications()->count());
    }

    private function commentBy(User $author): Comment
    {
        $comment = Post::factory()->create()->comments()->make(['body' => 'Bravo']);
        $comment->user()->associate($author)->save();

        return $comment;
    }
}
