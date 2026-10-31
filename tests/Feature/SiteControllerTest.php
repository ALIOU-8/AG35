<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_public_posts_and_bureau_but_not_member_posts(): void
    {
        Post::factory()->public()->create(['body' => 'Soirée culturelle guinéenne']);
        Post::factory()->create(['body' => 'Message réservé aux membres']);
        User::factory()->bureau('Trésorière')->create(['name' => 'Aïssatou Diallo']);
        User::factory()->create(['name' => 'Inscrit En Attente', 'bureau_title' => 'Secrétaire']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Soirée culturelle guinéenne')
            ->assertDontSee('Message réservé aux membres')
            ->assertSee('Aïssatou Diallo')
            ->assertSee('Trésorière')
            ->assertDontSee('Inscrit En Attente');
    }

    public function test_news_page_lists_only_public_posts(): void
    {
        Post::factory()->public()->create(['body' => 'Assemblée générale annuelle']);
        Post::factory()->create(['body' => 'Covoiturage pour samedi']);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee('Assemblée générale annuelle')
            ->assertDontSee('Covoiturage pour samedi');
    }

    public function test_public_article_renders_its_full_text(): void
    {
        $post = Post::factory()->public()->create(['body' => "Bienvenue aux nouveaux étudiants\nRendez-vous jeudi à 18 h."]);

        $this->get(route('news.show', $post))
            ->assertOk()
            ->assertSee('Bienvenue aux nouveaux étudiants')
            ->assertSee('Rendez-vous jeudi à 18 h.');
    }

    public function test_members_only_article_returns_404(): void
    {
        $post = Post::factory()->create();

        $this->get(route('news.show', $post))->assertNotFound();
    }

    public function test_contact_form_stores_message_and_returns_to_contact_section(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Mariama Camara',
            'email' => 'mariama@example.com',
            'subject' => 'Arrivée en septembre',
            'body' => 'Bonjour, je cherche un logement.',
        ])
            ->assertRedirect(route('home').'#contact')
            ->assertSessionHas('contact_status');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'mariama@example.com',
            'subject' => 'Arrivée en septembre',
            'read_at' => null,
        ]);
    }

    public function test_contact_form_rejects_submissions_that_fill_the_honeypot(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Robot',
            'email' => 'robot@example.com',
            'subject' => 'Promo',
            'body' => 'Spam',
            'website' => 'https://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
    }
}
