<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_member_post_stays_members_only_even_when_public_is_requested(): void
    {
        $member = User::factory()->active()->create();

        $this->actingAs($member)
            ->post(route('posts.store'), ['body' => 'Bonjour à tous', 'visibility' => 'public'])
            ->assertRedirect(route('feed'));

        $this->assertDatabaseHas('posts', ['user_id' => $member->id, 'body' => 'Bonjour à tous', 'visibility' => 'members']);
    }

    public function test_bureau_member_can_publish_on_the_public_site(): void
    {
        $bureau = User::factory()->bureau()->create();

        $this->actingAs($bureau)
            ->post(route('posts.store'), ['body' => 'Inscriptions ouvertes', 'visibility' => 'public'])
            ->assertRedirect(route('feed'));

        $this->assertDatabaseHas('posts', ['user_id' => $bureau->id, 'visibility' => 'public']);
    }

    public function test_photos_and_videos_are_stored_in_order_and_ajax_upload_gets_a_redirect_url(): void
    {
        Storage::fake('public');
        $member = User::factory()->active()->create();

        $this->actingAs($member)
            ->postJson(route('posts.store'), [
                'media' => [
                    UploadedFile::fake()->image('photo.jpg'),
                    UploadedFile::fake()->create('clip.mp4', 2000, 'video/mp4'),
                ],
            ])
            ->assertOk()
            ->assertJson(['redirect' => route('feed')]);

        $media = Post::firstOrFail()->media;
        $this->assertSame(['image', 'video'], $media->pluck('type')->all());
        $media->each(fn ($item) => Storage::disk('public')->assertExists($item->path));
    }

    public function test_post_without_text_or_media_is_rejected_with_a_french_message(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->post(route('posts.store'), ['body' => ''])
            ->assertSessionHasErrors(['body' => 'Écrivez un texte ou ajoutez au moins une photo ou vidéo.']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_unsupported_file_type_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->active()->create())
            ->post(route('posts.store'), ['media' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]])
            ->assertSessionHasErrors(['media.0' => 'Formats acceptés : JPG, PNG, GIF, WEBP, MP4, WEBM, MOV.']);
    }

    public function test_member_cannot_delete_another_members_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->active()->create())
            ->delete(route('posts.destroy', $post))
            ->assertForbidden();

        $this->assertModelExists($post);
    }

    public function test_bureau_member_can_delete_any_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->bureau()->create())
            ->delete(route('posts.destroy', $post))
            ->assertRedirect();

        $this->assertModelMissing($post);
    }

    public function test_deleting_a_post_removes_its_files(): void
    {
        Storage::fake('public');
        $member = User::factory()->active()->create();
        $post = Post::factory()->for($member)->create();
        $path = UploadedFile::fake()->image('photo.jpg')->store('posts', 'public');
        $post->media()->create(['type' => 'image', 'path' => $path, 'mime' => 'image/jpeg', 'size' => 100]);

        $this->actingAs($member)->delete(route('posts.destroy', $post))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertModelMissing($post);
    }

    public function test_like_toggles_on_and_off_and_returns_the_count(): void
    {
        $member = User::factory()->active()->create();
        $post = Post::factory()->create();

        $this->actingAs($member)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertExactJson(['liked' => true, 'likes' => 1]);

        $this->actingAs($member)->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertExactJson(['liked' => false, 'likes' => 0]);
    }

    public function test_liking_notifies_the_author_once_and_unliking_withdraws_it(): void
    {
        $author = User::factory()->active()->create();
        $liker = User::factory()->active()->create();
        $post = Post::factory()->for($author)->create(['body' => 'Soirée de bienvenue']);

        $this->actingAs($liker)->postJson(route('posts.like', $post))->assertOk();
        $this->assertSame('aime votre publication', $author->notifications()->sole()->data['text']);

        $this->actingAs($liker)->postJson(route('posts.like', $post))->assertOk();
        $this->assertSame(0, $author->notifications()->count());

        $this->actingAs($liker)->postJson(route('posts.like', $post))->assertOk();
        $this->assertSame(1, $author->notifications()->count());
    }

    public function test_liking_your_own_post_sends_no_notification(): void
    {
        $author = User::factory()->active()->create();

        $this->actingAs($author)->postJson(route('posts.like', Post::factory()->for($author)->create()))->assertOk();

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_deleting_a_post_withdraws_its_notifications(): void
    {
        $author = User::factory()->active()->create();
        $post = Post::factory()->for($author)->create();
        $this->actingAs(User::factory()->active()->create())->postJson(route('posts.like', $post));

        $this->actingAs($author)->delete(route('posts.destroy', $post))->assertRedirect();

        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_post_page_shows_the_post_and_all_its_comments(): void
    {
        $post = Post::factory()->create(['body' => 'Covoiturage pour Nantes']);
        foreach (range(1, 4) as $number) {
            $post->comments()->make(['body' => "Commentaire {$number}"])->user()->associate(User::factory()->active()->create())->save();
        }

        $this->actingAs(User::factory()->active()->create())
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Covoiturage pour Nantes')
            ->assertSee('Commentaire 1')
            ->assertSee('\\u0022showAll\\u0022:true', false);
    }

    public function test_tampered_post_key_returns_404(): void
    {
        $this->actingAs(User::factory()->active()->create())
            ->postJson(route('posts.like', ['post' => 'not-an-encrypted-key']))
            ->assertNotFound();
    }
}
